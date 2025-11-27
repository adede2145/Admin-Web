<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\DTRReport;
use App\Models\DTRReportDetail;
use App\Models\DTRReportSummary;
use App\Models\Department;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class DTRService
{
    public function generateDTRReport($request, $admin)
    {
        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $reportType = $request->report_type;
            $departmentId = $request->department_id;

            \Log::info("DTR Generation started: {$startDate->toDateString()} to {$endDate->toDateString()}, Department: {$departmentId}");

            // Validate permissions
            if ($admin->role->role_name !== 'super_admin' && $departmentId != $admin->department_id) {
                throw new \Exception('You can only generate reports for your department.');
            }

            // Get employees for the report (respect selected checklist if provided)
            $employeeQuery = Employee::with('department');
            if ($departmentId) {
                $employeeQuery->where('department_id', $departmentId);
            }
            if ($request->filled('employee_ids')) {
                $employeeQuery->whereIn('employee_id', $request->employee_ids);
            }
            $employees = $employeeQuery->get();

            \Log::info("Found {$employees->count()} employees for DTR generation");

            if ($employees->isEmpty()) {
                throw new \Exception('No employees found for the selected department.');
            }

            // Check if there's any attendance data in the date range (ignore invalid '0000-00-00 00:00:00')
            $attendanceCount = AttendanceLog::whereBetween('time_in', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->where('time_in', '>=', '1900-01-01 00:00:00')
                ->count();
            \Log::info("Found {$attendanceCount} attendance records in date range");

            if ($attendanceCount == 0) {
                throw new \Exception("No attendance data found for the selected date range ({$startDate->toDateString()} to {$endDate->toDateString()}). Please check if you selected the correct dates.");
            }

            // Create DTR report record
            $reportTitle = $this->generateReportTitle($reportType, $startDate, $endDate, $departmentId);
            
            $dtrReport = DTRReport::create([
                'admin_id' => $admin->admin_id,
                'department_id' => $departmentId,
                'report_type' => $reportType,
                'report_title' => $reportTitle,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'generated_on' => now(),
                'total_employees' => $employees->count(),
                'total_days' => $startDate->diffInDays($endDate) + 1,
                'status' => 'generated'
            ]);

            $totalHours = 0;
            $reportDetails = [];
            $reportSummaries = [];

            // Process each employee
            foreach ($employees as $employee) {
                \Log::info("Processing employee: {$employee->full_name} (ID: {$employee->employee_id})");
                $employeeData = $this->processEmployeeAttendance($employee, $startDate, $endDate);
                
                // Store details
                foreach ($employeeData['details'] as $detail) {
                    $detail['report_id'] = $dtrReport->report_id;
                    $reportDetails[] = $detail;
                }

                // Store summary
                $summary = $employeeData['summary'];
                $summary['report_id'] = $dtrReport->report_id;
                $summary['employee_id'] = $employee->employee_id;
                $reportSummaries[] = $summary;

                $totalHours += $summary['total_hours'];
            }

            \Log::info("Generated " . count($reportDetails) . " details and " . count($reportSummaries) . " summaries");

            // Bulk insert details and summaries
            if (!empty($reportDetails)) {
                DTRReportDetail::insert($reportDetails);
            }
            if (!empty($reportSummaries)) {
                DTRReportSummary::insert($reportSummaries);
            }

            // Update total hours in report
            $dtrReport->update(['total_hours' => $totalHours]);

            \Log::info("DTR Report generated successfully: {$dtrReport->report_id}");

            return $dtrReport;
            
        } catch (\Exception $e) {
            \Log::error('DTR Generation Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    private function processEmployeeAttendance($employee, $startDate, $endDate)
    {
        $period = CarbonPeriod::create($startDate, $endDate);
        $details = [];
        $summary = [
            'total_days' => 0,
            'present_days' => 0,
            'absent_days' => 0,
            'incomplete_days' => 0,
            'total_hours' => 0,
            'overtime_hours' => 0,
            'average_hours_per_day' => 0,
            'attendance_rate' => 0
        ];

        foreach ($period as $date) {
            $dayData = $this->processDayAttendance($employee, $date);
            $details[] = $dayData;
            
            // Update summary
            $summary['total_days']++;
            $summary['total_hours'] += $dayData['total_hours'];
            $summary['overtime_hours'] += $dayData['overtime_hours'];

            switch ($dayData['status']) {
                case 'present':
                    $summary['present_days']++;
                    break;
                case 'absent':
                    $summary['absent_days']++;
                    break;
                case 'incomplete':
                    $summary['incomplete_days']++;
                    break;
            }
        }

        // Calculate averages and rates
        if ($summary['total_days'] > 0) {
            $summary['average_hours_per_day'] = $summary['total_hours'] / $summary['total_days'];
            $summary['attendance_rate'] = $summary['present_days'] / $summary['total_days'] * 100;
        }

        return [
            'details' => $details,
            'summary' => $summary
        ];
    }

    private function processDayAttendance($employee, $date)
    {
        // Check if it's weekend
        if ($date->isWeekend()) {
            return [
                'employee_id' => $employee->employee_id,
                'date' => $date->toDateString(),
                'time_in' => null,
                'time_out' => null,
                'total_hours' => 0,
                'overtime_hours' => 0,
                'status' => 'weekend',
                'remarks' => 'Weekend'
            ];
        }

        // Get attendance logs for this day (excluding rejected RFID records)
        $logs = AttendanceLog::where('employee_id', $employee->employee_id)
            ->whereDate('time_in', $date)
            ->where('time_in', '>=', '1900-01-01 00:00:00')
            ->verifiedOrNotRfid() // Exclude rejected RFID records
            ->orderBy('time_in')
            ->get();

        // Debug logging
        \Log::info("Processing attendance for employee {$employee->employee_id} on {$date->toDateString()}, found {$logs->count()} logs");

        if ($logs->isEmpty()) {
            return [
                'employee_id' => $employee->employee_id,
                'date' => $date->toDateString(),
                'time_in' => null,
                'time_out' => null,
                'total_hours' => 0,
                'overtime_hours' => 0,
                'status' => 'absent',
                'remarks' => 'No attendance record'
            ];
        }

        $firstLog = $logs->first();
        $lastLog = $logs->last();
        
        $timeIn = $firstLog->time_in;
        $timeOut = $lastLog->time_out;
        
        $totalHours = 0;
        $overtimeHours = 0;
        $status = 'present';
        $remarks = 'Regular work day';

        if ($timeIn && $timeOut) {
            $totalHours = $timeIn->diffInHours($timeOut, false);
            $overtimeHours = max(0, $totalHours - 8); // Assuming 8 hours is regular work day
            $remarks = "Worked {$totalHours} hours";
        } else {
            $status = 'incomplete';
            $remarks = $timeIn ? 'No time out recorded' : 'No time in recorded';
        }

        return [
            'employee_id' => $employee->employee_id,
            'date' => $date->toDateString(),
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'total_hours' => $totalHours,
            'overtime_hours' => $overtimeHours,
            'status' => $status,
            'remarks' => $remarks
        ];
    }

    private function generateReportTitle($reportType, $startDate, $endDate, $departmentId)
    {
        $department = $departmentId ? Department::find($departmentId) : null;
        $departmentName = $department ? $department->department_name : 'All Departments';
        
        $period = $startDate->format('M d') . ' - ' . $endDate->format('M d, Y');
        
        return "{$departmentName} " . ucfirst($reportType) . " Report - {$period}";
    }

    public function getDTRReportHistory($admin, $filters = [])
    {
        $query = DTRReport::with(['admin', 'department'])
            ->where('status', '!=', 'deleted')
            ->orderBy('generated_on', 'desc');

        // Apply department filter for non-super admins
        if ($admin->role->role_name !== 'super_admin') {
            $query->where('department_id', $admin->department_id);
        }

        // Apply additional filters
        if (!empty($filters['report_type'])) {
            $query->where('report_type', $filters['report_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }

        return $query->paginate(15);
    }

    public function getDTRReportDetails($reportId, $admin)
    {
        $report = DTRReport::with(['admin', 'department', 'summaries.employee', 'details.employee'])
            ->findOrFail($reportId);

        // Check permissions
        if ($admin->role->role_name !== 'super_admin' && $report->department_id != $admin->department_id) {
            abort(403, 'You can only view reports from your department.');
        }

        return $report;
    }

    public function deleteDTRReport($reportId, $admin)
    {
        $report = DTRReport::findOrFail($reportId);

        // Check permissions
        if ($admin->role->role_name !== 'super_admin' && $report->department_id != $admin->department_id) {
            abort(403, 'You can only delete reports from your department.');
        }

        // Soft delete by updating status
        $report->update(['status' => 'deleted']);

        return true;
    }
}
