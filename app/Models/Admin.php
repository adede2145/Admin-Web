<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        'employee_id'
    ];

    protected $hidden = [
        'password_hash',
        'fingerprint_hash'
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
