<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'time_in',
        'time_out',
        'status', // present, absent, late, half-day
        'notes'
    ];

    protected $casts = [
        'date' => 'date',
        'time_in' => 'datetime',
        'time_out' => 'datetime'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public static function getReportByDateRange($startDate, $endDate, $departmentId = null)
    {
        $query = self::whereBetween('date', [$startDate, $endDate])
            ->with(['employee.department']);

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        return $query->orderBy('date', 'desc')
                    ->orderBy('time_in', 'desc')
                    ->get();
    }
}
