<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLoginAttempt extends Model
{
    protected $table = 'admin_login_attempts';
    public $timestamps = false;

    protected $fillable = [
        'admin_id', 'username', 'successful', 'ip_address', 'user_agent', 'created_at'
    ];
}


