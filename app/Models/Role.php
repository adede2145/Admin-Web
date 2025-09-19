<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'role_id';

    protected $fillable = [
        'role_name',
    ];

    public $timestamps = false;

    // Relationship: Role has many Admins
    public function admins()
    {
        return $this->hasMany(Admin::class, 'role_id');
    }
}
