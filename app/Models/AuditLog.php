<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB as DBFacade;

class AuditLog extends Model
{
    protected $fillable = [
        'admin_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'read_by',
        'first_read_at'
    ];

    protected $casts = [
        // Store old/new values as raw JSON text to avoid encoding issues; decode lazily if needed
        'old_values' => 'string',
        'new_values' => 'string',
        'read_by' => 'array',
        'first_read_at' => 'datetime',
    ];

    // Accessor to decode JSON strings when accessing the attributes
    public function getOldValuesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function getNewValuesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function getChangesAttribute()
    {
        $changes = [];
        $oldValues = $this->old_values;
        $newValues = $this->new_values;
        
        if ($oldValues && $newValues) {
            foreach ($newValues as $key => $value) {
                $oldValue = $oldValues[$key] ?? null;
                // Compare values, treating null explicitly
                if ($oldValue !== $value) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $value
                    ];
                }
            }
        }
        return $changes;
    }

    // Check if this audit log is unread by the given admin
    public function isUnreadBy($adminId)
    {
        $readBy = $this->read_by ?? [];
        return !in_array($adminId, $readBy);
    }

    // Mark this audit as read by the given admin
    public function markAsReadBy($adminId)
    {
        $readBy = $this->read_by ?? [];
        if (!in_array($adminId, $readBy)) {
            $readBy[] = $adminId;
            $this->read_by = $readBy;
            if (!$this->first_read_at) {
                $this->first_read_at = now();
            }
            $this->save();
        }
    }

    // Scope for RBAC - department admins see only their department's audits
    public function scopeForAdmin($query, $admin)
    {
        if ($admin->role->role_name === 'super_admin') {
            return $query; // Super admin sees everything
        }

        // Department admin sees only audits related to their department
        return $query->where(function ($q) use ($admin) {
            $q->whereHas('admin', function ($adminQuery) use ($admin) {
                $adminQuery->where('department_id', $admin->department_id);
            })
            ->orWhere(function ($modelQuery) use ($admin) {
                // For Employee model audits, check if employee belongs to admin's department
                $modelQuery->where('model_type', 'App\\Models\\Employee')
                    ->whereExists(function ($employeeQuery) use ($admin) {
                        $employeeQuery->select(DBFacade::raw(1))
                            ->from('employees')
                            ->whereColumn('employees.employee_id', 'audit_logs.model_id')
                            ->where('employees.department_id', $admin->department_id);
                    });
            })
            ->orWhere(function ($modelQuery) use ($admin) {
                // For AttendanceLog model audits, check if employee belongs to admin's department
                $modelQuery->where('model_type', 'App\\Models\\AttendanceLog')
                    ->whereExists(function ($attendanceQuery) use ($admin) {
                        $attendanceQuery->select(DBFacade::raw(1))
                            ->from('attendance_logs')
                            ->join('employees', 'employees.employee_id', '=', 'attendance_logs.employee_id')
                            ->whereColumn('attendance_logs.log_id', 'audit_logs.model_id')
                            ->where('employees.department_id', $admin->department_id);
                    });
            });
        });
    }
}