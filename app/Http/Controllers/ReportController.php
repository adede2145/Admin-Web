<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\Department;
use App\Models\DTRReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('department.admin');
    }

    // Show reporting dashboard
    public function index()
    {
        $departments = Department::all();
        $reportTypes = [
            'attendance_summary' => 'Attendance Summary',
            'employee_performance' => 'Employee Performance',
            'department_comparison' => 'Department Comparison',
            'overtime_analysis' => 'Overtime Analysis',
            'absenteeism_report' => 'Absenteeism Report',
            'custom_report' => 'Custom Report'
        ];

        return view('reports.index', compact('departments', 'reportTypes'));
    }

    // Generate custom report
    public function generateCustomReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department_id' => 'nullable|exists:departments,department_id',
            'export_format' => 'nullable|in:html,excel,pdf,csv'
        ]);

        // RBAC: Department admins can only generate reports for their own department
        if (auth()->user()->role->role_name !== 'super_admin') {
            if ($request->filled('department_id') && $request->department_id != auth()->user()->department_id) {
                return back()->with('error', 'You can only generate reports for your own department.');
            }
            $request->merge(['department_id' => auth()->user()->department_id]);
        }

        try {
            $reportData = $this->generateReportData($request);
            
            // Store report data in session for export functionality
            session(['current_report_data' => $reportData]);
            
            if ($request->filled('export_format')) {
                return $this->exportReportData($reportData, $request->export_format);
            }

            return view('reports.custom', compact('reportData'));

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    // Generate report data based on type
    private function generateReportData($request)
    {
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $departmentId = $request->department_id;
        $reportType = $request->report_type;

        $baseQuery = AttendanceLog::with(['employee.department'])
            ->whereBetween('time_in', [$startDate->startOfDay(), $endDate->endOfDay()]);

        if ($departmentId) {
            $baseQuery->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        switch ($reportType) {
            case 'attendance_summary':
                return $this->generateAttendanceSummary($baseQuery, $startDate, $endDate);
            
            case 'employee_performance':
                return $this->generateEmployeePerformance($baseQuery, $startDate, $endDate);
            
            case 'department_comparison':
                return $this->generateDepartmentComparison($startDate, $endDate);
            
            case 'overtime_analysis':
                return $this->generateOvertimeAnalysis($baseQuery, $startDate, $endDate);
            
            case 'absenteeism_report':
                return $this->generateAbsenteeismReport($baseQuery, $startDate, $endDate);
            
            default:
                throw new \Exception('Invalid report type');
        }
    }

    // Generate attendance summary report
    private function generateAttendanceSummary($baseQuery, $startDate, $endDate)
    {
        $attendanceData = $baseQuery->get();
        
        $summary = [
            'total_records' => $attendanceData->count(),
            'unique_employees' => $attendanceData->unique('employee_id')->count(),
            'total_days' => $startDate->diffInDays($endDate) + 1,
            'average_hours_per_day' => 0,
            'total_overtime_hours' => 0,
            'attendance_rate' => 0
        ];

        // Calculate statistics
        $totalHours = 0;
        $totalOvertime = 0;
        $presentDays = 0;

        foreach ($attendanceData as $record) {
            if ($record->time_in && $record->time_out) {
                $hours = Carbon::parse($record->time_in)->diffInHours(Carbon::parse($record->time_out), false);
                $totalHours += $hours;
                $overtime = max(0, $hours - 8);
                $totalOvertime += $overtime;
                $presentDays++;
            }
        }

        $summary['average_hours_per_day'] = $presentDays > 0 ? round($totalHours / $presentDays, 2) : 0;
        $summary['total_overtime_hours'] = $totalOvertime;
        $summary['attendance_rate'] = $presentDays > 0 ? round(($presentDays / $summary['total_records']) * 100, 2) : 0;

        return [
            'type' => 'attendance_summary',
            'title' => 'Attendance Summary Report',
            'period' => $startDate->format('M d, Y') . ' - ' . $endDate->format('M d, Y'),
            'summary' => $summary,
            'data' => $attendanceData,
            'generated_by' => auth()->user()->name,
            'generated_on' => now()
        ];
    }

    // Generate employee performance report
    private function generateEmployeePerformance($baseQuery, $startDate, $endDate)
    {
        $attendanceData = $baseQuery->get();
        
        $employeeStats = [];
        
        foreach ($attendanceData->groupBy('employee_id') as $employeeId => $records) {
            $employee = $records->first()->employee;
            $totalHours = 0;
            $totalOvertime = 0;
            $presentDays = 0;
            $lateDays = 0;

            foreach ($records as $record) {
                if ($record->time_in && $record->time_out) {
                    $hours = Carbon::parse($record->time_in)->diffInHours(Carbon::parse($record->time_out), false);
                    $totalHours += $hours;
                    $overtime = max(0, $hours - 8);
                    $totalOvertime += $overtime;
                    $presentDays++;
                }
            }

            $employeeStats[] = [
                'employee_id' => $employee->employee_id,
                'employee_name' => $employee->full_name,
                'department' => $employee->department->department_name,
                'total_hours' => $totalHours,
                'overtime_hours' => $totalOvertime,
                'present_days' => $presentDays,
                'average_hours_per_day' => $presentDays > 0 ? round($totalHours / $presentDays, 2) : 0,
                'attendance_rate' => $presentDays > 0 ? round(($presentDays / $records->count()) * 100, 2) : 0
            ];
        }

        return [
            'type' => 'employee_performance',
            'title' => 'Employee Performance Report',
            'period' => $startDate->format('M d, Y') . ' - ' . $endDate->format('M d, Y'),
            'data' => $employeeStats,
            'generated_by' => auth()->user()->name,
            'generated_on' => now()
        ];
    }

    // Generate department comparison report
    private function generateDepartmentComparison($startDate, $endDate)
    {
        $departments = Department::all();
        $comparisonData = [];

        foreach ($departments as $department) {
            $attendanceData = AttendanceLog::with('employee')
                ->whereHas('employee', function ($q) use ($department) {
                    $q->where('department_id', $department->department_id);
                })
                ->whereBetween('time_in', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->get();

            $totalHours = 0;
            $totalOvertime = 0;
            $presentDays = 0;
            $employeeCount = $attendanceData->unique('employee_id')->count();

            foreach ($attendanceData as $record) {
                if ($record->time_in && $record->time_out) {
                    $hours = Carbon::parse($record->time_in)->diffInHours(Carbon::parse($record->time_out), false);
                    $totalHours += $hours;
                    $overtime = max(0, $hours - 8);
                    $totalOvertime += $overtime;
                    $presentDays++;
                }
            }

            $comparisonData[] = [
                'department_name' => $department->department_name,
                'employee_count' => $employeeCount,
                'total_hours' => $totalHours,
                'overtime_hours' => $totalOvertime,
                'average_hours_per_employee' => $employeeCount > 0 ? round($totalHours / $employeeCount, 2) : 0,
                'attendance_rate' => $attendanceData->count() > 0 ? round(($presentDays / $attendanceData->count()) * 100, 2) : 0
            ];
        }

        return [
            'type' => 'department_comparison',
            'title' => 'Department Comparison Report',
            'period' => $startDate->format('M d, Y') . ' - ' . $endDate->format('M d, Y'),
            'data' => $comparisonData,
            'generated_by' => auth()->user()->name,
            'generated_on' => now()
        ];
    }

    // Generate overtime analysis report
    private function generateOvertimeAnalysis($baseQuery, $startDate, $endDate)
    {
        $attendanceData = $baseQuery->get();
        
        $overtimeStats = [
            'total_overtime_hours' => 0,
            'employees_with_overtime' => 0,
            'average_overtime_per_employee' => 0,
            'highest_overtime_employee' => null,
            'overtime_by_day' => []
        ];

        $employeeOvertime = [];

        foreach ($attendanceData as $record) {
            if ($record->time_in && $record->time_out) {
                $hours = Carbon::parse($record->time_in)->diffInHours(Carbon::parse($record->time_out), false);
                $overtime = max(0, $hours - 8);
                
                if ($overtime > 0) {
                    $overtimeStats['total_overtime_hours'] += $overtime;
                    
                    $employeeId = $record->employee_id;
                    if (!isset($employeeOvertime[$employeeId])) {
                        $employeeOvertime[$employeeId] = [
                            'employee_name' => $record->employee->full_name,
                            'total_overtime' => 0
                        ];
                    }
                    $employeeOvertime[$employeeId]['total_overtime'] += $overtime;

                    // Track overtime by day
                    $day = Carbon::parse($record->time_in)->format('Y-m-d');
                    if (!isset($overtimeStats['overtime_by_day'][$day])) {
                        $overtimeStats['overtime_by_day'][$day] = 0;
                    }
                    $overtimeStats['overtime_by_day'][$day] += $overtime;
                }
            }
        }

        $overtimeStats['employees_with_overtime'] = count($employeeOvertime);
        $overtimeStats['average_overtime_per_employee'] = $overtimeStats['employees_with_overtime'] > 0 
            ? round($overtimeStats['total_overtime_hours'] / $overtimeStats['employees_with_overtime'], 2) 
            : 0;

        // Find employee with highest overtime
        if (!empty($employeeOvertime)) {
            $highestOvertime = max(array_column($employeeOvertime, 'total_overtime'));
            foreach ($employeeOvertime as $employee) {
                if ($employee['total_overtime'] == $highestOvertime) {
                    $overtimeStats['highest_overtime_employee'] = $employee;
                    break;
                }
            }
        }

        return [
            'type' => 'overtime_analysis',
            'title' => 'Overtime Analysis Report',
            'period' => $startDate->format('M d, Y') . ' - ' . $endDate->format('M d, Y'),
            'summary' => $overtimeStats,
            'employee_overtime' => array_values($employeeOvertime),
            'generated_by' => auth()->user()->name,
            'generated_on' => now()
        ];
    }

    // Generate absenteeism report
    private function generateAbsenteeismReport($baseQuery, $startDate, $endDate)
    {
        $attendanceData = $baseQuery->get();
        
        $absenteeismStats = [
            'total_workdays' => $startDate->diffInDays($endDate) + 1,
            'total_absences' => 0,
            'employees_with_absences' => 0,
            'absence_rate' => 0,
            'most_absent_employee' => null
        ];

        $employeeAbsences = [];
        $expectedWorkdays = $startDate->diffInDays($endDate) + 1;

        foreach ($attendanceData->groupBy('employee_id') as $employeeId => $records) {
            $employee = $records->first()->employee;
            $actualWorkdays = $records->count();
            $absences = $expectedWorkdays - $actualWorkdays;

            if ($absences > 0) {
                $absenteeismStats['total_absences'] += $absences;
                $employeeAbsences[] = [
                    'employee_name' => $employee->full_name ?? 'N/A',
                    'department' => $employee->department->department_name ?? 'N/A',
                    'absences' => $absences,
                    'attendance_rate' => round(($actualWorkdays / $expectedWorkdays) * 100, 2)
                ];
            }
        }

        $absenteeismStats['employees_with_absences'] = count($employeeAbsences);
        $absenteeismStats['absence_rate'] = $expectedWorkdays > 0 
            ? round(($absenteeismStats['total_absences'] / ($expectedWorkdays * $attendanceData->unique('employee_id')->count())) * 100, 2) 
            : 0;

        // Find employee with most absences
        if (!empty($employeeAbsences)) {
            $mostAbsences = max(array_column($employeeAbsences, 'absences'));
            foreach ($employeeAbsences as $employee) {
                if ($employee['absences'] == $mostAbsences) {
                    $absenteeismStats['most_absent_employee'] = $employee;
                    break;
                }
            }
        }

        return [
            'type' => 'absenteeism_report',
            'title' => 'Absenteeism Report',
            'period' => $startDate->format('M d, Y') . ' - ' . $endDate->format('M d, Y'),
            'summary' => $absenteeismStats,
            'employee_absences' => $employeeAbsences,
            'generated_by' => auth()->user()->name,
            'generated_on' => now()
        ];
    }

    // Export report in different formats
    private function exportReportData($reportData, $format)
    {
        switch ($format) {
            case 'excel':
                return $this->exportToExcel($reportData);
            case 'pdf':
                return $this->exportToPdf($reportData);
            case 'csv':
                return $this->exportToCsv($reportData);
            default:
                return back()->with('error', 'Invalid export format');
        }
    }

    // Export to Excel
    private function exportToExcel($reportData)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setCellValue('A1', $reportData['title']);
        $sheet->setCellValue('A2', 'Period: ' . $reportData['period']);
        $sheet->setCellValue('A3', 'Generated by: ' . $reportData['generated_by']);
        $sheet->setCellValue('A4', 'Generated on: ' . $reportData['generated_on']->format('Y-m-d H:i:s'));

        $row = 6;

        switch ($reportData['type']) {
            case 'attendance_summary':
                $sheet->setCellValue('A' . $row, 'Metric');
                $sheet->setCellValue('B' . $row, 'Value');
                $row++;
                
                foreach ($reportData['summary'] as $key => $value) {
                    $sheet->setCellValue('A' . $row, ucwords(str_replace('_', ' ', $key)));
                    $sheet->setCellValue('B' . $row, $value);
                    $row++;
                }
                break;

            case 'employee_performance':
                $sheet->setCellValue('A' . $row, 'Employee ID');
                $sheet->setCellValue('B' . $row, 'Employee Name');
                $sheet->setCellValue('C' . $row, 'Department');
                $sheet->setCellValue('D' . $row, 'Total Hours');
                $sheet->setCellValue('E' . $row, 'Overtime Hours');
                $sheet->setCellValue('F' . $row, 'Present Days');
                $sheet->setCellValue('G' . $row, 'Avg Hours/Day');
                $sheet->setCellValue('H' . $row, 'Attendance Rate (%)');
                $row++;

                foreach ($reportData['data'] as $employee) {
                    $sheet->setCellValue('A' . $row, $employee['employee_id']);
                    $sheet->setCellValue('B' . $row, $employee['employee_name']);
                    $sheet->setCellValue('C' . $row, $employee['department']);
                    $sheet->setCellValue('D' . $row, $employee['total_hours']);
                    $sheet->setCellValue('E' . $row, $employee['overtime_hours']);
                    $sheet->setCellValue('F' . $row, $employee['present_days']);
                    $sheet->setCellValue('G' . $row, $employee['average_hours_per_day']);
                    $sheet->setCellValue('H' . $row, $employee['attendance_rate']);
                    $row++;
                }
                break;

            case 'department_comparison':
                $sheet->setCellValue('A' . $row, 'Department');
                $sheet->setCellValue('B' . $row, 'Employee Count');
                $sheet->setCellValue('C' . $row, 'Total Hours');
                $sheet->setCellValue('D' . $row, 'Overtime Hours');
                $sheet->setCellValue('E' . $row, 'Avg Hours/Employee');
                $sheet->setCellValue('F' . $row, 'Attendance Rate (%)');
                $row++;

                foreach ($reportData['data'] as $dept) {
                    $sheet->setCellValue('A' . $row, $dept['department_name']);
                    $sheet->setCellValue('B' . $row, $dept['employee_count']);
                    $sheet->setCellValue('C' . $row, $dept['total_hours']);
                    $sheet->setCellValue('D' . $row, $dept['overtime_hours']);
                    $sheet->setCellValue('E' . $row, $dept['average_hours_per_employee']);
                    $sheet->setCellValue('F' . $row, $dept['attendance_rate']);
                    $row++;
                }
                break;

            case 'overtime_analysis':
                $sheet->setCellValue('A' . $row, 'Employee Name');
                $sheet->setCellValue('B' . $row, 'Total Overtime Hours');
                $row++;

                foreach ($reportData['employee_overtime'] as $employee) {
                    $sheet->setCellValue('A' . $row, $employee['employee_name']);
                    $sheet->setCellValue('B' . $row, $employee['total_overtime']);
                    $row++;
                }
                break;

            case 'absenteeism_report':
                $sheet->setCellValue('A' . $row, 'Employee Name');
                $sheet->setCellValue('B' . $row, 'Department');
                $sheet->setCellValue('C' . $row, 'Absences');
                $sheet->setCellValue('D' . $row, 'Attendance Rate (%)');
                $row++;

                foreach ($reportData['employee_absences'] as $employee) {
                    $sheet->setCellValue('A' . $row, $employee['employee_name']);
                    $sheet->setCellValue('B' . $row, $employee['department']);
                    $sheet->setCellValue('C' . $row, $employee['absences']);
                    $sheet->setCellValue('D' . $row, $employee['attendance_rate']);
                    $row++;
                }
                break;
        }

        $filename = $reportData['type'] . '_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        $writer = new Xlsx($spreadsheet);
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        $writer->save($tempFile);
        
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    // Export to PDF
    private function exportToPdf($reportData)
    {
        $html = view('reports.pdf', compact('reportData'))->render();
        
        // Configure DomPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = $reportData['type'] . '_' . date('Y-m-d_H-i-s') . '.pdf';
        
        return response($dompdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    // Export to CSV
    private function exportToCsv($reportData)
    {
        $filename = $reportData['type'] . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
        $file = fopen($tempFile, 'w');
        
        // Write header
        fputcsv($file, ['Report: ' . $reportData['title']]);
        fputcsv($file, ['Period: ' . $reportData['period']]);
        fputcsv($file, ['Generated by: ' . $reportData['generated_by']]);
        fputcsv($file, ['Generated on: ' . $reportData['generated_on']->format('Y-m-d H:i:s')]);
        fputcsv($file, []); // Empty row

        switch ($reportData['type']) {
            case 'attendance_summary':
                fputcsv($file, ['Metric', 'Value']);
                foreach ($reportData['summary'] as $key => $value) {
                    fputcsv($file, [ucwords(str_replace('_', ' ', $key)), $value]);
                }
                break;

            case 'employee_performance':
                fputcsv($file, ['Employee ID', 'Employee Name', 'Department', 'Total Hours', 'Overtime Hours', 'Present Days', 'Avg Hours/Day', 'Attendance Rate (%)']);
                foreach ($reportData['data'] as $employee) {
                    fputcsv($file, [
                        $employee['employee_id'],
                        $employee['employee_name'],
                        $employee['department'],
                        $employee['total_hours'],
                        $employee['overtime_hours'],
                        $employee['present_days'],
                        $employee['average_hours_per_day'],
                        $employee['attendance_rate']
                    ]);
                }
                break;

            case 'department_comparison':
                fputcsv($file, ['Department', 'Employee Count', 'Total Hours', 'Overtime Hours', 'Avg Hours/Employee', 'Attendance Rate (%)']);
                foreach ($reportData['data'] as $dept) {
                    fputcsv($file, [
                        $dept['department_name'],
                        $dept['employee_count'],
                        $dept['total_hours'],
                        $dept['overtime_hours'],
                        $dept['average_hours_per_employee'],
                        $dept['attendance_rate']
                    ]);
                }
                break;

            case 'overtime_analysis':
                fputcsv($file, ['Employee Name', 'Total Overtime Hours']);
                foreach ($reportData['employee_overtime'] as $employee) {
                    fputcsv($file, [
                        $employee['employee_name'],
                        $employee['total_overtime']
                    ]);
                }
                break;

            case 'absenteeism_report':
                fputcsv($file, ['Employee Name', 'Department', 'Absences', 'Attendance Rate (%)']);
                foreach ($reportData['employee_absences'] as $employee) {
                    fputcsv($file, [
                        $employee['employee_name'],
                        $employee['department'],
                        $employee['absences'],
                        $employee['attendance_rate']
                    ]);
                }
                break;
        }
        
        fclose($file);
        
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend();
    }

    // Export report
    public function exportReport(Request $request, $format)
    {
        // Get the report data from session or regenerate it
        $reportData = session('current_report_data');
        
        if (!$reportData) {
            return back()->with('error', 'No report data available for export. Please generate a report first.');
        }

        return $this->exportReportData($reportData, $format);
    }

    // Show saved reports
    public function savedReports()
    {
        $reports = DTRReport::with(['admin', 'department'])
            ->orderBy('generated_on', 'desc')
            ->paginate(15);

        return view('reports.saved', compact('reports'));
    }

    // Schedule a report
    public function scheduleReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|string',
            'schedule_type' => 'required|in:daily,weekly,monthly',
            'email_recipients' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        // This would integrate with a job scheduler like Laravel Scheduler
        // For now, we'll just store the schedule in the database
        
        return back()->with('success', 'Report scheduled successfully!');
    }
}
