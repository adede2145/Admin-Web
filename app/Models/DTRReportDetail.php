<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DTRReportDetail extends Model
{
    protected $table = 'dtr_report_details';
    protected $primaryKey = 'detail_id';
    
    // Disable timestamps since the table doesn't have created_at/updated_at columns
    public $timestamps = false;

    protected $fillable = [
        'report_id',
        'employee_id',
        'date',
        'time_in',
        'time_out',
        'total_hours',
        'overtime_hours',
        'status',
        'remarks'
    ];

    protected $casts = [
        'date' => 'date',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
        'total_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2'
    ];

    public function report()
    {
        return $this->belongsTo(DTRReport::class, 'report_id', 'report_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    // Scopes for status
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }



    public function scopeIncomplete($query)
    {
        return $query->where('status', 'incomplete');
    }

    // Helper methods
    public function getFormattedTimeInAttribute()
    {
        return $this->time_in ? $this->time_in->format('h:i A') : '-';
    }

    public function getFormattedTimeOutAttribute()
    {
        return $this->time_out ? $this->time_out->format('h:i A') : '-';
    }

    public function getFormattedDateAttribute()
    {
        return $this->date->format('M d, Y');
    }

    public function getStatusBadgeClassAttribute()
    {
        switch ($this->status) {
            case 'present':
                return 'bg-success';
            case 'absent':
                return 'bg-danger';
            case 'incomplete':
                return 'bg-info';
            case 'holiday':
                return 'bg-secondary';
            case 'weekend':
                return 'bg-secondary';
            default:
                return 'bg-secondary';
        }
    }
}
