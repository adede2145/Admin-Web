<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DTRReportSummary extends Model
{
    protected $table = 'dtr_report_summaries';
    protected $primaryKey = 'summary_id';
    
    // Disable timestamps since the table doesn't have created_at/updated_at columns
    public $timestamps = false;

    protected $fillable = [
        'report_id',
        'employee_id',
        'total_days',
        'present_days',
        'absent_days',
        'incomplete_days',
        'total_hours',
        'overtime_hours',
        'average_hours_per_day',
        'attendance_rate'
    ];

    protected $casts = [
        'total_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'average_hours_per_day' => 'decimal:2',
        'attendance_rate' => 'decimal:2',
        'total_days' => 'integer',
        'present_days' => 'integer',
        'absent_days' => 'integer',
        'incomplete_days' => 'integer'
    ];

    public function report()
    {
        return $this->belongsTo(DTRReport::class, 'report_id', 'report_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    // Helper methods
    public function getAttendanceRatePercentageAttribute()
    {
        return number_format($this->attendance_rate, 1) . '%';
    }

    public function getTotalHoursFormattedAttribute()
    {
        return number_format($this->total_hours, 2);
    }

    public function getOvertimeHoursFormattedAttribute()
    {
        return number_format($this->overtime_hours, 2);
    }

    public function getAverageHoursFormattedAttribute()
    {
        return number_format($this->average_hours_per_day, 2);
    }

    public function getAttendanceStatusAttribute()
    {
        if ($this->attendance_rate >= 95) {
            return 'Excellent';
        } elseif ($this->attendance_rate >= 90) {
            return 'Good';
        } elseif ($this->attendance_rate >= 80) {
            return 'Fair';
        } else {
            return 'Poor';
        }
    }

    public function getAttendanceStatusClassAttribute()
    {
        if ($this->attendance_rate >= 95) {
            return 'text-success';
        } elseif ($this->attendance_rate >= 90) {
            return 'text-primary';
        } elseif ($this->attendance_rate >= 80) {
            return 'text-warning';
        } else {
            return 'text-danger';
        }
    }
}
