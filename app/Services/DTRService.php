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
            $employmentType = $request->employment_type;

            \Log::info("DTR Generation started: {$startDate->toDateString()} to {$endDate->toDateString()}, Employment Type: {$employmentType}");

            // Validate permissions using canManageEmployee method
            // This will be checked per employee in the query

            // Get employees for the report (respect selected checklist if provided)
            $employeeQuery = Employee::with('department');
            
            // Filter by employment type
            if ($employmentType) {
                $employeeQuery->where('employment_type', $employmentType);
            }
            
            // For non-super admins and non-HR admins, also filter by department
            if (!$admin->isSuperAdmin()) {
                $deptName = $admin->department ? $admin->department->department_name : null;
                $normalizedDept = $deptName ? mb_strtolower($deptName) : null;
                
                // If not HR admin, also apply department filter
                if (!in_array($normalizedDept, ['hr', 'office hr'], true)) {
                    $employeeQuery->where('department_id', $admin->department_id);
                }
            }
            
            if ($request->filled('employee_ids')) {
                $employeeQuery->whereIn('employee_id', $request->employee_ids);
            }
            $employees = $employeeQuery->get();
            
            // Filter employees using canManageEmployee for additional validation
            $employees = $employees->filter(function($employee) use ($admin) {
                return $admin->canManageEmployee($employee);
            });

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
            $reportTitle = $this->generateReportTitle($reportType, $startDate, $endDate, $employmentType);
            
            $dtrReport = DTRReport::create([
                'admin_id' => $admin->admin_id,
                'department_id' => $admin->department_id, // Store admin's department for reference
                'employment_type' => $employmentType,
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

        $capCalc = Carbon::parse($date)->setTime(18, 0, 0);       // cap regular time at 6:00 PM for calculations
        $capDisplay = Carbon::parse($date)->setTime(17, 59, 0);   // do not display 6:00 PM

        // Identify AM/PM punches
        $amIn = null;
        $amOut = null;
        $pmIn = null;
        $pmOut = null;
        foreach ($logs as $log) {
            if ($log->time_in) {
                $hourIn = (int)$log->time_in->format('H');
                if ($hourIn < 12) {
                    if (!$amIn || $log->time_in->lessThan($amIn)) {
                        $amIn = $log->time_in;
                    }
                } else {
                    if (!$pmIn || $log->time_in->lessThan($pmIn)) {
                        $pmIn = $log->time_in;
                    }
                }
            }
            if ($log->time_out) {
                $hourOut = (int)$log->time_out->format('H');
                if ($hourOut < 12) {
                    if (!$amOut || $log->time_out->greaterThan($amOut)) {
                        $amOut = $log->time_out;
                    }
                } else {
                    if (!$pmOut || $log->time_out->greaterThan($pmOut)) {
                        $pmOut = $log->time_out;
                    }
                }
            }
        }

        // Fallbacks if only one side present
        $timeIn = $amIn ?: ($pmIn ?: $logs->first()->time_in);
        $timeOutRaw = $pmOut ?: ($amOut ?: $logs->last()->time_out);

        // Calculate worked minutes (AM + PM), excluding lunch
        $lunchStart = Carbon::parse($date)->setTime(12, 0, 0);
        $lunchEnd   = Carbon::parse($date)->setTime(13, 0, 0);

        $workedMinutes = 0;
        // AM segment: up to lunch start
        if ($amIn && $amOut && $amOut->greaterThan($amIn)) {
            $amSegmentEnd = $amOut->lessThan($lunchStart) ? $amOut : $lunchStart;
            if ($amIn->lessThan($amSegmentEnd)) {
                $workedMinutes += $amIn->diffInMinutes($amSegmentEnd);
            }
        }
        // PM segment: from first PM in to pmOut capped at 6 PM
        $timeOutForCalc = null;
        if ($timeOutRaw) {
            $timeOutForCalc = $timeOutRaw->lessThan($capCalc) ? $timeOutRaw : $capCalc;
        }
        if ($pmIn && $timeOutForCalc && $timeOutForCalc->greaterThan($pmIn)) {
            $pmSegmentStart = $pmIn->lessThan($lunchEnd) ? $lunchEnd : $pmIn;
            if ($pmSegmentStart->lessThan($timeOutForCalc)) {
                $workedMinutes += $pmSegmentStart->diffInMinutes($timeOutForCalc);
            }
        }

        $workedMinutes = max(0, $workedMinutes);
        $totalHours = round($workedMinutes / 60, 2);

        // Overtime: anything beyond 6:00 PM based on raw time-out
        $overtimeHours = 0;
        if ($timeOutRaw && $timeOutRaw->greaterThan($capCalc)) {
            $overtimeMinutes = $capCalc->diffInMinutes($timeOutRaw);
            $overtimeHours = round($overtimeMinutes / 60, 2);
        }

        // Determine the display time-out
        $timeOutDisplay = null;
        if ($timeIn && $timeOutRaw) {
            if ($timeOutRaw->lessThanOrEqualTo($capDisplay)) {
                $timeOutDisplay = $timeOutRaw;
            } else {
                // completion after 8 work hours (AM+PM, lunch excluded)
                $workMinutesNeeded = 8 * 60;
                $remaining = $workMinutesNeeded;

                // AM contribution
                if ($amIn && $amOut) {
                    $amSegmentEnd = $amOut->lessThan($lunchStart) ? $amOut : $lunchStart;
                    if ($amIn->lessThan($amSegmentEnd)) {
                        $amMinutes = $amIn->diffInMinutes($amSegmentEnd);
                        $remaining -= min($remaining, $amMinutes);
                    }
                }

                // PM contribution
                if ($remaining > 0 && $pmIn) {
                    $pmStart = $pmIn->lessThan($lunchEnd) ? $lunchEnd : $pmIn;
                    $pmLimit = $timeOutRaw->lessThan($capDisplay) ? $timeOutRaw : $capDisplay;
                    if ($pmStart->lessThan($pmLimit)) {
                        $pmCompletion = $pmStart->copy()->addMinutes($remaining);
                        if ($pmCompletion->greaterThan($pmLimit)) {
                            $pmCompletion = $pmLimit;
                        }
                        $timeOutDisplay = $pmCompletion;
                    }
                }

                if (!$timeOutDisplay) {
                    $timeOutDisplay = $timeOutRaw->lessThan($capDisplay) ? $timeOutRaw : $capDisplay;
                }
            }
            if ($timeOutDisplay && $timeOutDisplay->lessThanOrEqualTo($timeIn)) {
                $timeOutDisplay = null;
            }
        }

        $totalHours = round($workedMinutes / 60, 2);
        $status = ($timeIn && $timeOutRaw) ? 'present' : 'incomplete';
        $remarks = $status === 'present'
            ? ("Worked {$totalHours} hours" . ($overtimeHours > 0 ? " (+{$overtimeHours} OT)" : ''))
            : ($timeIn ? 'No time out recorded' : 'No time in recorded');

        return [
            'employee_id' => $employee->employee_id,
            'date' => $date->toDateString(),
            'time_in' => $timeIn,
            'time_out' => $timeOutDisplay, // display the time when 8 hours are completed, capped before 6 PM
            'total_hours' => $totalHours,
            'overtime_hours' => $overtimeHours,
            'status' => $status,
            'remarks' => $remarks
        ];
    }

    private function generateReportTitle($reportType, $startDate, $endDate, $employmentType)
    {
        // Map employment types to readable names
        $employmentTypeLabels = [
            'full_time' => 'Full-Time',
            'part_time' => 'Part-Time',
            'cos' => 'COS',
            'admin' => 'Admin',
            'faculty with designation' => 'Faculty'
        ];
        
        $employmentTypeName = $employmentTypeLabels[$employmentType] ?? ucfirst($employmentType);
        
        // Format period as "Month YYYY"
        $period = $startDate->format('F Y');
        
        return "{$employmentTypeName} Employees " . ucfirst($reportType) . " Report - {$period}";
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
        $report = DTRReport::with(['admin', 'department', 'summaries.employee.department', 'details.employee.department'])
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
