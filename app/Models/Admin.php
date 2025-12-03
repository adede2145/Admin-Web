<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Employee;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $table = 'admins';
    protected $primaryKey = 'admin_id';
    public $timestamps = false;

    protected $fillable = [
        'username',
        'password_hash',
        'fingerprint_hash',
        'rfid_code',
        'role_id',
        'department_id',
        'employee_id',
        'employment_type_access',
    ];

    protected $hidden = [
        'password_hash',
        'fingerprint_hash'
    ];

    protected $casts = [
        'employment_type_access' => 'array',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    // Add employee relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function dtrReports()
    {
        return $this->hasMany(DTRReport::class, 'admin_id', 'admin_id');
    }

    // Helper methods for roles
    public function isSuperAdmin()
    {
        return $this->role && $this->role->role_name === 'super_admin';
    }

    public function isDepartmentAdmin()
    {
        return $this->role && $this->role->role_name === 'admin' && !is_null($this->department_id);
    }

    /**
     * Check if this admin can manage the given employee based on role,
     * department, and configured employment type access.
     */
    public function canManageEmployee(Employee $employee): bool
    {
        // Super admin can manage everyone
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Must be an admin
        if (!$this->role || $this->role->role_name !== 'admin') {
            return false;
        }

        // If no employment_type_access configured, fall back to department only
        if (empty($this->employment_type_access) || !is_array($this->employment_type_access)) {
            return $this->department_id && $this->department_id === $employee->department_id;
        }

        $deptName = $this->department ? $this->department->department_name : null;
        $normalizedDept = $deptName ? mb_strtolower($deptName) : null;

        // HR / Office HR admins: ignore department, only filter by employment type (case-insensitive office name)
        if (in_array($normalizedDept, ['hr', 'office hr'], true)) {
            return in_array($employee->employment_type, $this->employment_type_access, true);
        }

        // Non‑HR admins: must match department AND allowed employment type
        return $this->department_id === $employee->department_id
            && in_array($employee->employment_type, $this->employment_type_access, true);
    }

    // Get admin's full name from employee record
    public function getFullNameAttribute()
    {
        return $this->employee ? $this->employee->full_name : $this->username;
    }

    // Check if admin can track attendance
    public function canTrackAttendance()
    {
        return $this->employee_id !== null;
    }
}
