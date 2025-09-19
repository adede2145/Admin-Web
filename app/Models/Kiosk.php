<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kiosk extends Model
{
    protected $table = 'kiosks';
    protected $primaryKey = 'kiosk_id';
    public $timestamps = false; // Disable Laravel's automatic timestamps since we don't have created_at/updated_at

    protected $fillable = [
        'location',
        'is_active',
        'last_seen'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen' => 'datetime'
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

    // Scope for online kiosks (seen within last 5 minutes)
    public function scopeOnline($query, $minutes = 5)
    {
        $fiveMinutesAgo = now()->subMinutes($minutes);
        $now = now();

        return $query->where('is_active', true)
            ->where('last_seen', '>=', $fiveMinutesAgo)
            ->where('last_seen', '<=', $now); // Don't count future timestamps as online
    }

    // Scope for offline kiosks (active but not seen recently)
    public function scopeOffline($query, $minutes = 5)
    {
        return $query->where('is_active', true)
            ->where(function ($q) use ($minutes) {
                $q->whereNull('last_seen')
                    ->orWhere('last_seen', '<', now()->subMinutes($minutes));
            });
    }

    // Method to update heartbeat
    public function updateHeartbeat()
    {
        $this->update(['last_seen' => now()]);
        return $this;
    }

    // Check if kiosk is currently online
    public function isOnline($minutes = 5)
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->last_seen) {
            return false;
        }

        $now = now();
        $lastSeen = $this->last_seen;

        // Check if last_seen is not in the future and within the time window
        return $lastSeen <= $now && $lastSeen->diffInMinutes($now) <= $minutes;
    }

    // Get status string
    public function getStatusAttribute()
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        return $this->isOnline() ? 'online' : 'offline';
    }

    // Get time since last seen in human readable format
    public function getLastSeenHumanAttribute()
    {
        if (!$this->last_seen) {
            return 'Never';
        }

        return $this->last_seen->diffForHumans();
    }
}
