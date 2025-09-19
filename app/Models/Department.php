<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
    use HasFactory;

    protected $primaryKey = 'department_id';

    protected $fillable = [
        'department_name',
        'description'
    ];

    public $timestamps = false;

    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id', 'department_id');
    }

    public function admins()
    {
        return $this->hasMany(Admin::class, 'department_id', 'department_id');
    }

    public function dtrReports()
    {
        return $this->hasMany(DTRReport::class, 'department_id', 'department_id');
    }
}
