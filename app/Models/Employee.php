<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Auditable;

class Employee extends Model
{
    use HasFactory, Auditable;

    protected $primaryKey = 'employee_id';

    // Disable timestamps since the table doesn't have created_at/updated_at columns
    public $timestamps = false;

    protected $fillable = [
        'employee_code',
        'full_name',
        'employment_type',
        'fingerprint_hash',
        'rfid_code',
        'department_id',
        'photo_path',
        'photo_data',
        'photo_content_type'
    ];

    protected $hidden = [
        'fingerprint_hash',
        'photo_data'  // Exclude binary photo data from JSON serialization
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, 'employee_id', 'employee_id');
    }

    // Add admin relationship
    public function admin()
    {
        return $this->hasOne(Admin::class, 'employee_id', 'employee_id');
    }

    public function role()
    {
        return $this->hasOneThrough(
            Role::class,
            Admin::class,
            'employee_id', // Foreign key on admins table
            'role_id',     // Foreign key on roles table
            'employee_id', // Local key on employees table
            'role_id'      // Local key on admins table
        );
    }

    public function fingerprintTemplates()
    {
        return $this->hasMany(EmployeeFingerprintTemplate::class, 'employee_id', 'employee_id');
    }

    // Helper method to check if employee is an admin
    public function isAdmin()
    {
        return $this->admin !== null;
    }

    // Get admin role name if employee is admin
    public function getAdminRoleAttribute()
    {
        return $this->admin ? $this->admin->role->role_name : null;
    }

    // Accessors
    public function getPhotoUrlAttribute()
    {
        $path = $this->attributes['photo_path'] ?? null;
        if (!$path) {
            return null;
        }
        // If absolute URL or absolute path, return as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }
        // Default: treat as storage public path
        return asset('storage/' . ltrim($path, '/'));
    }
}
