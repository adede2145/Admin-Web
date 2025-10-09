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
        'photo_filename',
        'rfid_reason',
        'is_verified',
        'verification_status',
        'verified_by',
        'verified_at',
        'verification_notes'
    ];

    protected $casts = [
        'time_in' => 'datetime',
        'time_out' => 'datetime',
        'photo_captured_at' => 'datetime',
        'verified_at' => 'datetime',
        'is_verified' => 'boolean'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function kiosk()
    {
        return $this->belongsTo(Kiosk::class, 'kiosk_id', 'kiosk_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(Admin::class, 'verified_by', 'admin_id');
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
        // Ensure we are returning base64 for embedding even if stored as binary
        $raw = $this->photo_data;
        // If it's already base64-like, keep it; otherwise base64-encode binary
        $isBase64 = is_string($raw) && base64_encode(base64_decode($raw, true)) === $raw;
        $base64 = $isBase64 ? $raw : base64_encode($raw);
        return "data:{$contentType};base64,{$base64}";
    }

    public function isRfidWithPhoto()
    {
        return $this->method === 'rfid' && $this->hasPhoto();
    }

    // RFID verification helper methods
    public function isRfidPending()
    {
        return $this->method === 'rfid' && $this->verification_status === 'pending';
    }

    public function isRfidVerified()
    {
        return $this->method === 'rfid' && $this->verification_status === 'verified';
    }

    public function isRfidRejected()
    {
        return $this->method === 'rfid' && $this->verification_status === 'rejected';
    }

    public function needsVerification()
    {
        return $this->method === 'rfid' && $this->verification_status === 'pending';
    }

    public function getVerificationStatusBadge()
    {
        $badges = [
            'pending' => '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Pending</span>',
            'verified' => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Verified</span>',
            'rejected' => '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>'
        ];

        return $badges[$this->verification_status] ?? '';
    }
}
