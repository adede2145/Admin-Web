<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DTRDetailOverride extends Model
{
    protected $table = 'dtr_detail_overrides';
    protected $primaryKey = 'override_id';
    public $timestamps = false;

    protected $fillable = [
        'report_id',
        'employee_id',
        'date',
        'status_override',
        'remarks'
    ];

    protected $casts = [
        'date' => 'date'
    ];
}
