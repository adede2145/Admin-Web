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

            AuditLog::create([
                'admin_id' => auth()->user()->admin_id,
                'action' => 'edit',
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'old_values' => json_encode($oldValuesToStore, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                'new_values' => json_encode($newValuesToStore, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        });

        static::created(function ($model) {
            if (!auth()->check()) return;

            $newValues = $model->sanitizeAuditArray($model->getAttributes());

            AuditLog::create([
                'admin_id' => auth()->user()->admin_id,
                'action' => 'create',
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'new_values' => json_encode($newValues, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        });

        static::deleted(function ($model) {
            if (!auth()->check()) return;

            $oldValues = $model->sanitizeAuditArray($model->getAttributes());

            AuditLog::create([
                'admin_id' => auth()->user()->admin_id,
                'action' => 'delete',
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'old_values' => json_encode($oldValues, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        });
    }
}