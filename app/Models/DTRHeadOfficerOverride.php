<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DTRHeadOfficerOverride extends Model
{
    protected $table = 'dtr_head_officer_overrides';
    protected $primaryKey = 'override_id';

    protected $fillable = [
        'report_id',
        'employee_id',
        'head_officer_name',
        'head_officer_office'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the DTR report that owns this head officer override
     */
    public function report()
    {
        return $this->belongsTo(DTRReport::class, 'report_id', 'report_id');
    }

    /**
     * Get the employee that this head officer override belongs to
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
