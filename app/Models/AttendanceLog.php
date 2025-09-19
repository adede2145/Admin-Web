<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Auditable;

class AttendanceLog extends Model
{
    use HasFactory, Auditable;

    protected $table = 'attendance_logs';
    protected $primaryKey = 'log_id';
    
    // Disable timestamps since the table doesn't have created_at/updated_at columns
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'time_in',
        'time_out',
        'method',
        'kiosk_id',
        'photo_data',
        'photo_content_type',
        'photo_captured_at',
        'photo_filename'
    ];

    protected $casts = [
        'time_in' => 'datetime',
        'time_out' => 'datetime',
        'photo_captured_at' => 'datetime'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function kiosk()
    {
        return $this->belongsTo(Kiosk::class, 'kiosk_id', 'kiosk_id');
    }

    // Scopes for attendance methods
    public function scopeRfid($query)
    {
        return $query->where('method', 'rfid');
    }

    public function scopeFingerprint($query)
    {
        return $query->where('method', 'fingerprint');
    }

    // Scope for date range
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('time_in', [$startDate, $endDate]);
    }

    // Photo-related helper methods
    public function hasPhoto()
    {
        return !empty($this->photo_data);
    }

    public function getPhotoUrl()
    {
        if (!$this->hasPhoto()) {
            return null;
        }
        
        return route('attendance.photo', $this->log_id);
    }

    public function getPhotoDataUri()
    {
        if (!$this->hasPhoto()) {
            return null;
        }
        
        $contentType = $this->photo_content_type ?: 'image/jpeg';
        return "data:{$contentType};base64,{$this->photo_data}";
    }

    public function isRfidWithPhoto()
    {
        return $this->method === 'rfid' && $this->hasPhoto();
    }
}
