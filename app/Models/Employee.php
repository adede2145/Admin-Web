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
        'fingerprint_hash'
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, 'employee_id', 'employee_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function fingerprintTemplates()
    {
        return $this->hasMany(EmployeeFingerprintTemplate::class, 'employee_id', 'employee_id');
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
