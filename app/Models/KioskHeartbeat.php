<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KioskHeartbeat extends Model
{
    protected $table = 'kiosk_heartbeats';

    protected $fillable = [
        'kiosk_id',
        'last_seen',
        'location'
    ];

    protected $casts = [
        'last_seen' => 'datetime:Y-m-d H:i:s'
    ];
}
