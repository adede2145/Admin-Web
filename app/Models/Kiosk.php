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
        'last_seen' => 'datetime:Y-m-d H:i:s'
    ];

    // Override the last_seen accessor to handle UTC to Manila conversion
    public function getLastSeenAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        // Create Carbon instance from UTC and convert to Manila
        return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $value, 'UTC')
            ->setTimezone('Asia/Manila');
    }

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
        $fiveMinutesAgo = now('Asia/Manila')->subMinutes($minutes);
        $now = now('Asia/Manila');

        return $query->where('is_active', true)
            ->whereNotNull('last_seen')
            ->where('last_seen', '>=', $fiveMinutesAgo->utc()->format('Y-m-d H:i:s'))
            ->where('last_seen', '<=', $now->utc()->format('Y-m-d H:i:s'));
    }

    // Scope for offline kiosks (active but not seen recently)
    public function scopeOffline($query, $minutes = 5)
    {
        $fiveMinutesAgo = now('Asia/Manila')->subMinutes($minutes);
        
        return $query->where('is_active', true)
            ->where(function ($q) use ($fiveMinutesAgo) {
                $q->whereNull('last_seen')
                    ->orWhere('last_seen', '<', $fiveMinutesAgo->utc()->format('Y-m-d H:i:s'));
            });
    }

    // Method to update heartbeat
    public function updateHeartbeat()
    {
        // Store time in UTC format in the database
        $this->update(['last_seen' => now('Asia/Manila')->utc()->format('Y-m-d H:i:s')]);
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

        $now = now('Asia/Manila');
        // last_seen is already converted to Manila timezone by the accessor
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

        // last_seen is already in Manila timezone from the accessor
        $currentManilaTime = now('Asia/Manila');
        
        // Get the human readable difference and ensure it says "ago" instead of "before"
        $diff = $this->last_seen->diffForHumans($currentManilaTime);
        
        // Replace "before" with "ago" if it exists
        return str_replace('before', 'ago', $diff);
    }
}
