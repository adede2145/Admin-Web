<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'username',
        'password_hash',
        'role_id',
    ];

    protected $hidden = [
        'password_hash',
    ];

    // Role constants
    const ROLE_SUPER_ADMIN = 1;
    const ROLE_ADMIN = 2;
    const ROLE_EMPLOYEE = 3;

    // Tell Laravel to use password_hash for authentication
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // Relationship: User belongs to a Role
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // Helper methods for role checking
    public function isSuperAdmin()
    {
        return $this->role_id === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin()
    {
        return $this->role_id === self::ROLE_ADMIN;
    }

    public function isEmployee()
    {
        return $this->role_id === self::ROLE_EMPLOYEE;
    }

    // Check if user has admin privileges (either super admin or admin)
    public function hasAdminAccess()
    {
        return in_array($this->role_id, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN]);
    }

    // Check if user can create admin accounts (only super admin)
    public function canCreateAdmins()
    {
        return $this->isSuperAdmin();
    }
}
