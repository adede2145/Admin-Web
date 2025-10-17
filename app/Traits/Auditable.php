<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    /**
     * Convert model attributes into safe, UTF-8 encodable values for audit storage.
     * - Replaces binary/non-UTF-8 strings with a placeholder
     * - Avoids storing very large payloads (e.g., images, blobs)
     */
    protected function sanitizeAuditArray(array $values): array
    {
        $maxPreview = 5000; // avoid extremely large text
        $binaryKeys = ['photo_data', 'fingerprint_hash', 'template_data'];

        $clean = [];
        foreach ($values as $key => $value) {
            // Skip known binary/blob fields entirely
            if (in_array($key, $binaryKeys, true)) {
                $clean[$key] = '[BINARY_OMITTED]';
                continue;
            }

            if (is_string($value)) {
                // Fast UTF-8 check; preg_match('//u', ...) returns false for invalid UTF-8
                if (!@preg_match('//u', $value)) {
                    $clean[$key] = '[BINARY_STRING_OMITTED]';
                } else {
                    $clean[$key] = mb_strlen($value, 'UTF-8') > $maxPreview
                        ? mb_substr($value, 0, $maxPreview, 'UTF-8') . '...'
                        : $value;
                }
            } elseif (is_resource($value)) {
                $clean[$key] = '[RESOURCE]';
            } elseif (is_array($value)) {
                $clean[$key] = $this->sanitizeAuditArray($value);
            } else {
                $clean[$key] = $value;
            }
        }
        return $clean;
    }

    /**
     * Generate context information for audit logs
     */
    protected function generateAuditContext($action)
    {
        $modelName = class_basename($this);
        
        switch ($modelName) {
            case 'AttendanceLog':
                $employeeName = $this->employee ? $this->employee->full_name : 'Unknown Employee';
                $timeIn = $this->time_in ? $this->time_in->format('M d, Y h:i A') : 'N/A';
                return "Attendance record for {$employeeName} ({$timeIn})";
                
            case 'Employee':
                $departmentName = $this->department ? $this->department->department_name : 'Unknown Department';
                return "Employee: {$this->full_name} from {$departmentName}";
                
            case 'Department':
                return "Department: {$this->department_name}";
                
            default:
                return ucfirst($action) . ' ' . $modelName . ' (ID: ' . $this->getKey() . ')';
        }
    }

    /**
     * Generate summary for audit logs
     */
    protected function generateAuditSummary($action, $data = [])
    {
        $modelName = class_basename($this);
        
        switch ($modelName) {
            case 'AttendanceLog':
                $employeeName = $this->employee ? $this->employee->full_name : 'Unknown Employee';
                $timeIn = $this->time_in ? $this->time_in->format('M d, Y h:i A') : 'N/A';
                $method = ucfirst($this->method ?? 'Unknown');
                
                switch ($action) {
                    case 'delete':
                        return "Deleted {$method} attendance record for {$employeeName} on {$timeIn}";
                    case 'create':
                        return "Created {$method} attendance record for {$employeeName} on {$timeIn}";
                    case 'edit':
                        return "Updated {$method} attendance record for {$employeeName} on {$timeIn}";
                    default:
                        return ucfirst($action) . "d {$method} attendance record for {$employeeName}";
                }
                
            case 'Employee':
                $departmentName = $this->department ? $this->department->department_name : 'Unknown Department';
                
                switch ($action) {
                    case 'delete':
                        return "Deleted employee {$this->full_name} from {$departmentName}";
                    case 'create':
                        return "Created employee {$this->full_name} in {$departmentName}";
                    case 'edit':
                        return "Updated employee {$this->full_name} in {$departmentName}";
                    default:
                        return ucfirst($action) . "d employee {$this->full_name}";
                }
                
            case 'Department':
                switch ($action) {
                    case 'delete':
                        return "Deleted department {$this->department_name}";
                    case 'create':
                        return "Created department {$this->department_name}";
                    case 'edit':
                        return "Updated department {$this->department_name}";
                    default:
                        return ucfirst($action) . "d department {$this->department_name}";
                }
                
            default:
                return ucfirst($action) . 'd ' . $modelName . ' record';
        }
    }

    protected static function bootAuditable()
    {
        static::updating(function ($model) {
            if (!auth()->check()) return;

            $oldValues = $model->getOriginal();
            $newValues = $model->getAttributes();
            
            // Only store changed attributes
            $changedAttributes = array_diff_assoc($newValues, $oldValues);
            if (empty($changedAttributes)) return;

            $oldValuesToStore = array_intersect_key($oldValues, $changedAttributes);
            $newValuesToStore = array_intersect_key($newValues, $changedAttributes);


            $oldValuesToStore = $model->sanitizeAuditArray($oldValuesToStore);
            $newValuesToStore = $model->sanitizeAuditArray($newValuesToStore);

            // Generate context information and summary
            $contextInfo = $model->generateAuditContext('edit');
            $summary = $model->generateAuditSummary('edit', $newValuesToStore);
            
            // Determine related model information
            $relatedModelType = null;
            $relatedModelId = null;
            
            if (method_exists($model, 'getRelatedModelForAudit')) {
                $related = $model->getRelatedModelForAudit();
                if ($related) {
                    $relatedModelType = get_class($related);
                    $relatedModelId = $related->getKey();
                }
            }

            AuditLog::create([
                'admin_id' => auth()->user()->admin_id,
                'action' => 'edit',
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'context_info' => $contextInfo,
                'related_model_type' => $relatedModelType,
                'related_model_id' => $relatedModelId,
                'summary' => $summary,
                'old_values' => json_encode($oldValuesToStore, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                'new_values' => json_encode($newValuesToStore, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        });

        static::created(function ($model) {
            if (!auth()->check()) return;

            $newValues = $model->sanitizeAuditArray($model->getAttributes());

            // Generate context information and summary
            $contextInfo = $model->generateAuditContext('create');
            $summary = $model->generateAuditSummary('create', $newValues);
            
            // Determine related model information
            $relatedModelType = null;
            $relatedModelId = null;
            
            if (method_exists($model, 'getRelatedModelForAudit')) {
                $related = $model->getRelatedModelForAudit();
                if ($related) {
                    $relatedModelType = get_class($related);
                    $relatedModelId = $related->getKey();
                }
            }

            AuditLog::create([
                'admin_id' => auth()->user()->admin_id,
                'action' => 'create',
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'context_info' => $contextInfo,
                'related_model_type' => $relatedModelType,
                'related_model_id' => $relatedModelId,
                'summary' => $summary,
                'new_values' => json_encode($newValues, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        });

        static::deleted(function ($model) {
            if (!auth()->check()) return;

            $oldValues = $model->sanitizeAuditArray($model->getAttributes());
            
            // Generate context information and summary
            $contextInfo = $model->generateAuditContext('delete');
            $summary = $model->generateAuditSummary('delete', $oldValues);
            
            // Determine related model information
            $relatedModelType = null;
            $relatedModelId = null;
            
            if (method_exists($model, 'getRelatedModelForAudit')) {
                $related = $model->getRelatedModelForAudit();
                if ($related) {
                    $relatedModelType = get_class($related);
                    $relatedModelId = $related->getKey();
                }
            }

            AuditLog::create([
                'admin_id' => auth()->user()->admin_id,
                'action' => 'delete',
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'context_info' => $contextInfo,
                'related_model_type' => $relatedModelType,
                'related_model_id' => $relatedModelId,
                'summary' => $summary,
                'old_values' => json_encode($oldValues, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        });
    }
}