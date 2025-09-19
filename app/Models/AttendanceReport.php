<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceReport extends Model
{
    use HasFactory;

    protected $table = 'attendance_reports';

    protected $fillable = [
        'emp_id',
        'period_start',
        'period_end',
        'report_data',
        'generated_at'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
        'report_data' => 'json'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }
}
