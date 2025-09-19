<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DTRReport extends Model
{
    protected $table = 'dtr_reports';
    protected $primaryKey = 'report_id';
    
    // Disable timestamps since the table doesn't have created_at/updated_at columns
    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'department_id',
        'report_type',
        'report_title',
        'start_date',
        'end_date',
        'generated_on',
        'file_path',
        'total_employees',
        'total_days',
        'total_hours',
        'status',
        'notes'
    ];

    protected $casts = [
        'generated_on' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'total_hours' => 'decimal:2',
        'total_employees' => 'integer',
        'total_days' => 'integer'
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function details()
    {
        return $this->hasMany(DTRReportDetail::class, 'report_id', 'report_id');
    }

    public function summaries()
    {
        return $this->hasMany(DTRReportSummary::class, 'report_id', 'report_id');
    }

    // Scopes for report types
    public function scopeWeekly($query)
    {
        return $query->where('report_type', 'weekly');
    }

    public function scopeMonthly($query)
    {
        return $query->where('report_type', 'monthly');
    }

    public function scopeCustom($query)
    {
        return $query->where('report_type', 'custom');
    }

    public function scopeGenerated($query)
    {
        return $query->where('status', 'generated');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    // Helper methods
    public function getFormattedPeriodAttribute()
    {
        return $this->start_date->format('M d, Y') . ' - ' . $this->end_date->format('M d, Y');
    }

    public function getFormattedGeneratedOnAttribute()
    {
        return $this->generated_on->format('M d, Y h:i A');
    }

    public function getDepartmentNameAttribute()
    {
        return $this->department ? $this->department->department_name : 'All Departments';
    }

    public function getAdminNameAttribute()
    {
        return $this->admin ? $this->admin->username : 'Unknown';
    }
}
