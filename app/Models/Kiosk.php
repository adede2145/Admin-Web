<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kiosk extends Model
{
    protected $table = 'kiosks';
    protected $primaryKey = 'kiosk_id';

    protected $fillable = [
        'location',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, 'kiosk_id', 'kiosk_id');
    }

    // Scope for active kiosks
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
