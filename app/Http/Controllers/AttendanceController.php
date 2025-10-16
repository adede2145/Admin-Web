<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Kiosk;
use App\Models\DTRReport;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Allow authenticated users to fetch photos regardless of department to ensure images render in UI
        $this->middleware('department.admin')->except(['store', 'verifyFingerprint', 'verifyRFID', 'showPhoto']);
    }

    // Show attendance management page
    public function index(Request $request)
    {
        $query = AttendanceLog::with(['employee.department']);

        // Apply filters
        if ($request->filled('start_date')) {
            $query->whereDate('time_in', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('time_in', '<=', $request->end_date);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }
        if ($request->filled('login_method')) {
            $query->where('method', $request->login_method);
        }
        if ($request->filled('status')) {
            if ($request->status === 'late') {
                // Assuming late is when time_in is after 8:00 AM (adjust as needed)
                $query->whereTime('time_in', '>', '08:00:00');
            } elseif ($request->status === 'on_time') {
                // Assuming on time is when time_in is at or before 8:00 AM (adjust as needed)
                $query->whereTime('time_in', '<=', '08:00:00');
            }
        }

        // RFID verification status filter
        if ($request->filled('rfid_status')) {
            $query->where('method', 'rfid')->where('verification_status', $request->rfid_status);
        }

        // Department restriction for non-super admins
        if (auth()->user()->role->role_name !== 'super_admin' && auth()->user()->department_id) {
            $query->whereHas('employee', function ($q) {
                $q->where('department_id', auth()->user()->department_id);
            });
        }

        $attendanceLogs = $query->latest('time_in')->paginate(20);

        // RBAC: Only show departments that the user has access to
        if (auth()->user()->role->role_name === 'super_admin') {
            $departments = Department::all();
        } else {
            $departments = Department::where('department_id', auth()->user()->department_id)->get();
        }

        // Employees list for DTR modal (scoped by role/department)
        $employeesForDTR = Employee::when(
            auth()->user()->role->role_name !== 'super_admin',
            function ($q) {
                $q->where('department_id', auth()->user()->department_id);
            }
        )
            ->orderBy('full_name')
            ->get();

        return view('attendance.index', compact('attendanceLogs', 'departments', 'employeesForDTR'));
    }

    // Update attendance record
    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'time_in' => 'required|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i|after:time_in',
            'method' => 'required|in:rfid,fingerprint',
        ]);

        $attendanceLog = AttendanceLog::findOrFail($id);

        // Check if user can edit this record
        if (
            auth()->user()->role->role_name !== 'super_admin' &&
            auth()->user()->department_id !== $attendanceLog->employee->department_id
        ) {
            abort(403, 'You can only edit attendance records from your department.');
        }

        // Store old values before making any changes
        $oldValues = [
            'time_in' => $attendanceLog->time_in,
            'time_out' => $attendanceLog->time_out,
            'method' => $attendanceLog->method
        ];

        // Prepare update data
        $timeIn = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . $request->time_in)
            ->format('Y-m-d H:i:s');

        $updateData = [
            'time_in' => $timeIn,
            'method' => $request->method,
        ];

        if ($request->filled('time_out')) {
            $timeOut = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . $request->time_out)
                ->format('Y-m-d H:i:s');
            $updateData['time_out'] = $timeOut;
        }

        // Update the attendance log
        $attendanceLog->update($updateData);

        // Save new values for audit after the update
        $newValues = $attendanceLog->fresh()->only(['time_in', 'time_out', 'method']);

        try {
            // Create audit log entry
            $log = \App\Models\AuditLog::create([
                'admin_id'    => auth()->user()->admin_id,
                'action'      => 'edit',
                'model_type'  => 'AttendanceLog',
                'model_id'    => $attendanceLog->attendance_id,
                'old_values'  => json_encode($oldValues),
                'new_values'  => json_encode($newValues),
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ]);
            Log::info('Audit log created successfully', [
                'log_id' => $log->id,
                'attendance_id' => $attendanceLog->attendance_id,
                'changes' => [
                    'old' => $oldValues,
                    'new' => $newValues
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create audit log', [
                'error' => $e->getMessage(),
                'attendance_id' => $attendanceLog->attendance_id
            ]);
        }

        return back()->with('success', 'Attendance record updated successfully!');
    }

    // Delete attendance record
    public function destroy($id)
    {
        $attendanceLog = AttendanceLog::findOrFail($id);

        // Check if user can delete this record
        if (
            auth()->user()->role->role_name !== 'super_admin' &&
            auth()->user()->department_id !== $attendanceLog->employee->department_id
        ) {
            abort(403, 'You can only delete attendance records from your department.');
        }

        $attendanceLog->delete();

        return back()->with('success', 'Attendance record deleted successfully!');
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,employee_id',
            'method' => 'required|in:rfid,fingerprint',
            'kiosk_id' => 'required|exists:kiosks,kiosk_id',
            'photo_data' => 'nullable|string', // Base64 encoded image data
            'photo_content_type' => 'nullable|string'
        ]);

        // Prepare attendance log data
        $logData = [
            'employee_id' => $request->employee_id,
            'time_in' => now('Asia/Manila')->utc()->format('Y-m-d H:i:s'),
            'method' => $request->method,
            'kiosk_id' => $request->kiosk_id
        ];

        // Handle photo data if provided
        if ($request->filled('photo_data')) {
            // Remove data URL prefix if present (e.g., "data:image/jpeg;base64,")
            $photoData = $request->photo_data;
            if (strpos($photoData, 'base64,') !== false) {
                $photoData = substr($photoData, strpos($photoData, 'base64,') + 7);
            }

            // Decode base64 photo data
            $decodedPhoto = base64_decode($photoData);

            if ($decodedPhoto !== false) {
                $logData['photo_data'] = $decodedPhoto;
                $logData['photo_content_type'] = $request->photo_content_type ?: 'image/jpeg';
                $logData['photo_captured_at'] = now('Asia/Manila')->utc()->format('Y-m-d H:i:s');
                $logData['photo_filename'] = 'attendance_' . time() . '.jpg';
            }
        }

        // Create attendance log
        $log = AttendanceLog::create($logData);

        // Update kiosk heartbeat to mark it as online
        $kiosk = Kiosk::find($request->kiosk_id);
        if ($kiosk) {
            $kiosk->updateHeartbeat();
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance logged successfully',
            'log' => $log
        ]);
    }

    public function verifyFingerprint(Request $request)
    {
        $request->validate([
            'fingerprint_hash' => 'required|string'
        ]);

        $employee = Employee::where('fingerprint_hash', $request->fingerprint_hash)->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid fingerprint'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'employee_id' => $employee->employee_id
        ]);
    }

    public function verifyRFID(Request $request)
    {
        $request->validate([
            'rfid_code' => 'required|string'
        ]);

        $employee = Employee::where('rfid_code', $request->rfid_code)->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid RFID'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'employee_id' => $employee->employee_id
        ]);
    }

    public function heartbeat(Request $request)
    {
        $request->validate([
            'kiosk_id' => 'required|exists:kiosks,kiosk_id'
        ]);

        $kiosk = Kiosk::find($request->kiosk_id);
        if (!$kiosk) {
            return response()->json([
                'success' => false,
                'message' => 'Kiosk not found'
            ], 404);
        }

        // Update kiosk heartbeat
        $kiosk->updateHeartbeat();

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat updated successfully',
            'timestamp' => $kiosk->last_seen
        ]);
    }

    /**
     * Debug endpoint to test photo data transmission from kiosk
     */
    public function debugPhoto(Request $request)
    {
        $response = [
            'success' => true,
            'debug_info' => [
                'request_method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'has_photo_data' => $request->has('photo_data'),
                'photo_data_length' => $request->filled('photo_data') ? strlen($request->photo_data) : 0,
                'photo_content_type' => $request->photo_content_type,
                'timestamp' => now('Asia/Manila')->toISOString()
            ]
        ];

        if ($request->filled('photo_data')) {
            $photoData = $request->photo_data;

            // Check if it's base64
            $isBase64 = base64_encode(base64_decode($photoData, true)) === $photoData;
            $response['debug_info']['appears_base64'] = $isBase64;

            if ($isBase64) {
                $decoded = base64_decode($photoData);
                $response['debug_info']['decoded_length'] = strlen($decoded);

                // Check first 4 bytes
                if (strlen($decoded) >= 4) {
                    $firstBytes = substr($decoded, 0, 4);
                    $hex = bin2hex($firstBytes);
                    $response['debug_info']['first_4_bytes_hex'] = $hex;

                    // Identify image format
                    if (substr($hex, 0, 4) === 'ffd8') {
                        $response['debug_info']['detected_format'] = 'JPEG';
                    } elseif (substr($hex, 0, 8) === '89504e47') {
                        $response['debug_info']['detected_format'] = 'PNG';
                    } else {
                        $response['debug_info']['detected_format'] = 'Unknown/Corrupted';
                    }
                }
            } else {
                // Not base64, check raw data
                $response['debug_info']['first_10_chars'] = substr($photoData, 0, 10);
                $response['debug_info']['first_4_bytes_hex'] = bin2hex(substr($photoData, 0, 4));
            }
        }

        return response()->json($response);
    }

    public function generateDTR(Request $request)
    {
        $request->validate([
            'department_id' => 'nullable|exists:departments,department_id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'report_type' => 'required|in:weekly,monthly,custom',
            'employee_ids' => 'sometimes|array',
            'employee_ids.*' => 'integer|exists:employees,employee_id'
        ]);

        // RBAC: Department admins can only generate reports for their own department
        if (auth()->user()->role->role_name !== 'super_admin') {
            if ($request->filled('department_id') && $request->department_id != auth()->user()->department_id) {
                return back()->with('error', 'You can only generate DTR reports for your own department.');
            }
            // Force department_id to user's department for non-super admins
            $request->merge(['department_id' => auth()->user()->department_id]);
        }

        try {
            $dtrService = new \App\Services\DTRService();
            $dtrReport = $dtrService->generateDTRReport($request, auth()->user());

            // Redirect to the generated DTR report details page so user sees the summary immediately
            return redirect()->route('dtr.details', $dtrReport->report_id)
                ->with('success', 'DTR report generated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate DTR report: ' . $e->getMessage());
        }
    }

    public function dtrHistory(Request $request)
    {
        $filters = $request->only(['report_type', 'status', 'start_date', 'end_date']);

        $dtrService = new \App\Services\DTRService();
        $reports = $dtrService->getDTRReportHistory(auth()->user(), $filters);

        return view('dtr.history', compact('reports', 'filters'));
    }

    public function dtrHistoryModal(Request $request)
    {
        $filters = $request->only(['report_type', 'status', 'start_date', 'end_date']);
        $dtrService = new \App\Services\DTRService();
        $reports = $dtrService->getDTRReportHistory(auth()->user(), $filters);
        // Return only the table part for modal
        return view('dtr.partials.history_table', compact('reports', 'filters'));
    }

    public function dtrDetails($reportId)
    {
        $dtrService = new \App\Services\DTRService();
        $report = $dtrService->getDTRReportDetails($reportId, auth()->user());

        // Load overrides for this report (keyed: employee_id|Y-m-d => override)
        $overrides = \App\Models\DTRDetailOverride::where('report_id', $reportId)
            ->get()
            ->keyBy(function ($o) {
                return $o->employee_id . '|' . $o->date->toDateString();
            });

        return view('dtr.details', compact('report', 'overrides'));
    }

    public function deleteDTR($reportId)
    {
        try {
            Log::info('Delete DTR attempt', ['report_id' => $reportId, 'user_id' => auth()->id()]);

            $dtrService = new \App\Services\DTRService();
            $dtrService->deleteDTRReport($reportId, auth()->user());

            Log::info('DTR report deleted successfully', ['report_id' => $reportId]);
            return back()->with('success', 'DTR report deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to delete DTR report', ['report_id' => $reportId, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to delete DTR report: ' . $e->getMessage());
        }
    }

    public function downloadDTR($reportId, $format = 'html')
    {
        try {
            Log::info('Download DTR attempt', ['report_id' => $reportId, 'format' => $format, 'user_id' => auth()->id()]);

            $dtrService = new \App\Services\DTRService();
            $report = $dtrService->getDTRReportDetails($reportId, auth()->user());

            // Load overrides for this report (keyed: employee_id|Y-m-d => override)
            $overrides = \App\Models\DTRDetailOverride::where('report_id', $reportId)
                ->get()
                ->keyBy(function ($o) {
                    return $o->employee_id . '|' . $o->date->toDateString();
                });

            $filename = 'DTR_Report_' . $report->report_id . '_' . $report->start_date . '_to_' . $report->end_date;

            Log::info('DTR download processing', ['filename' => $filename, 'format' => $format]);

            switch ($format) {
                case 'pdf':
                    return $this->downloadAsPDF($report, $overrides, $filename);
                case 'csv':
                    return $this->downloadAsCSV($report, $overrides, $filename);
                case 'excel':
                    return $this->downloadAsExcel($report, $overrides, $filename);
                default:
                    return $this->downloadAsHTML($report, $overrides, $filename);
            }
        } catch (\Exception $e) {
            Log::error('Failed to download DTR report', ['report_id' => $reportId, 'format' => $format, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to download DTR report: ' . $e->getMessage());
        }
    }

    private function downloadAsHTML($report, $overrides, $filename)
    {
        $htmlContent = $this->generateHTMLContent($report, $overrides);

        return response($htmlContent)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '.html"');
    }

    private function downloadAsPDF($report, $overrides, $filename)
    {
        $htmlContent = $this->generateHTMLContent($report, $overrides);

        // Use Dompdf to convert HTML to PDF
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"');
    }

    private function downloadAsCSV($report, $overrides, $filename)
    {
        $csvData = [];

        // Add headers
        $csvData[] = ['DTR Report: ' . $report->report_title];
        $csvData[] = ['Generated on: ' . $report->formatted_generated_on];
        $csvData[] = ['Department: ' . $report->department_name];
        $csvData[] = ['Period: ' . $report->formatted_period];
        $csvData[] = [];

        // Employee Summary
        $csvData[] = ['Employee Summary'];
        $csvData[] = ['Employee ID', 'Name', 'Department', 'Present Days', 'Absent Days', 'Total Hours', 'Overtime Hours', 'Attendance Rate'];

        foreach ($report->summaries as $summary) {
            $csvData[] = [
                $summary->employee_id,
                $summary->employee->full_name,
                $summary->employee->department->department_name,
                $summary->present_days,
                $summary->absent_days,
                number_format($summary->total_hours, 2),
                number_format($summary->overtime_hours, 2),
                number_format($summary->attendance_rate, 1) . '%'
            ];
        }

        $csvData[] = [];
        $csvData[] = ['Detailed Attendance Records'];
        $csvData[] = ['Employee ID', 'Employee Name', 'Date', 'Time In', 'Time Out', 'Total Hours', 'Overtime', 'Status', 'Remarks'];

        foreach ($report->details as $detail) {
            // Check for override
            $dateKey = \Carbon\Carbon::parse($detail->date)->toDateString();
            $ovKey = $detail->employee_id . '|' . $dateKey;
            $ov = $overrides ? ($overrides[$ovKey] ?? null) : null;

            $status = $ov ? 'Leave' : ucfirst($detail->status);
            $remarks = $ov ? ('Leave' . ($ov->remarks ? ': ' . $ov->remarks : '')) : ($detail->remarks ?? '');

            $csvData[] = [
                $detail->employee_id,
                $detail->employee->full_name,
                $detail->formatted_date,
                $detail->formatted_time_in,
                $detail->formatted_time_out,
                number_format($detail->total_hours, 2),
                number_format($detail->overtime_hours, 2),
                $status,
                $remarks
            ];
        }

        $csvContent = '';
        foreach ($csvData as $row) {
            $csvContent .= implode(',', array_map(function ($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '.csv"');
    }

    private function downloadAsExcel($report, $overrides, $filename)
    {
        $spreadsheet = new Spreadsheet();

        // Employee Summary Sheet
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Employee Summary');

        $summarySheet->setCellValue('A1', 'DTR Report: ' . $report->report_title);
        $summarySheet->setCellValue('A2', 'Generated on: ' . $report->formatted_generated_on);
        $summarySheet->setCellValue('A3', 'Department: ' . $report->department_name);
        $summarySheet->setCellValue('A4', 'Period: ' . $report->formatted_period);

        $summarySheet->setCellValue('A6', 'Employee Summary');
        $summarySheet->setCellValue('A7', 'Employee ID');
        $summarySheet->setCellValue('B7', 'Name');
        $summarySheet->setCellValue('C7', 'Department');
        $summarySheet->setCellValue('D7', 'Present Days');
        $summarySheet->setCellValue('E7', 'Absent Days');
        $summarySheet->setCellValue('F7', 'Total Hours');
        $summarySheet->setCellValue('G7', 'Overtime Hours');
        $summarySheet->setCellValue('H7', 'Attendance Rate');

        $row = 8;
        foreach ($report->summaries as $summary) {
            $summarySheet->setCellValue('A' . $row, $summary->employee_id);
            $summarySheet->setCellValue('B' . $row, $summary->employee->full_name);
            $summarySheet->setCellValue('C' . $row, $summary->employee->department->department_name);
            $summarySheet->setCellValue('D' . $row, $summary->present_days);
            $summarySheet->setCellValue('E' . $row, $summary->absent_days);
            $summarySheet->setCellValue('F' . $row, $summary->total_hours);
            $summarySheet->setCellValue('G' . $row, $summary->overtime_hours);
            $summarySheet->setCellValue('H' . $row, $summary->attendance_rate . '%');
            $row++;
        }

        // Detailed Records Sheet
        $detailSheet = $spreadsheet->createSheet();
        $detailSheet->setTitle('Detailed Records');

        $detailSheet->setCellValue('A1', 'Detailed Attendance Records');
        $detailSheet->setCellValue('A2', 'Employee ID');
        $detailSheet->setCellValue('B2', 'Employee Name');
        $detailSheet->setCellValue('C2', 'Date');
        $detailSheet->setCellValue('D2', 'Time In');
        $detailSheet->setCellValue('E2', 'Time Out');
        $detailSheet->setCellValue('F2', 'Total Hours');
        $detailSheet->setCellValue('G2', 'Overtime');
        $detailSheet->setCellValue('H2', 'Status');
        $detailSheet->setCellValue('I2', 'Remarks');

        $row = 3;
        foreach ($report->details as $detail) {
            // Check for override
            $dateKey = \Carbon\Carbon::parse($detail->date)->toDateString();
            $ovKey = $detail->employee_id . '|' . $dateKey;
            $ov = $overrides ? ($overrides[$ovKey] ?? null) : null;

            $status = $ov ? 'Leave' : ucfirst($detail->status);
            $remarks = $ov ? ('Leave' . ($ov->remarks ? ': ' . $ov->remarks : '')) : ($detail->remarks ?? '');

            $detailSheet->setCellValue('A' . $row, $detail->employee_id);
            $detailSheet->setCellValue('B' . $row, $detail->employee->full_name);
            $detailSheet->setCellValue('C' . $row, $detail->formatted_date);
            $detailSheet->setCellValue('D' . $row, $detail->formatted_time_in);
            $detailSheet->setCellValue('E' . $row, $detail->formatted_time_out);
            $detailSheet->setCellValue('F' . $row, $detail->total_hours);
            $detailSheet->setCellValue('G' . $row, $detail->overtime_hours);
            $detailSheet->setCellValue('H' . $row, $status);
            $detailSheet->setCellValue('I' . $row, $remarks);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'I') as $col) {
            $detailSheet->getColumnDimension($col)->setAutoSize(true);
        }
        foreach (range('A', 'H') as $col) {
            $summarySheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'dtr_excel_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename . '.xlsx')->deleteFileAfterSend();
    }

    private function generateHTMLContent($report, $overrides = null)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>DTR Report - ' . $report->report_title . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                .report-info { margin-bottom: 30px; }
                .report-info table { width: 100%; border-collapse: collapse; }
                .report-info td { padding: 8px; border: 1px solid #ddd; }
                .report-info th { background-color: #f5f5f5; padding: 8px; border: 1px solid #ddd; }
                .summary { margin-bottom: 30px; }
                .summary table { width: 100%; border-collapse: collapse; }
                .summary th, .summary td { padding: 8px; border: 1px solid #ddd; text-align: center; }
                .summary th { background-color: #f5f5f5; }
                .details { margin-bottom: 30px; }
                .details table { width: 100%; border-collapse: collapse; font-size: 12px; }
                .details th, .details td { padding: 6px; border: 1px solid #ddd; text-align: center; }
                .details th { background-color: #f5f5f5; }
                .page-break { page-break-before: always; }
                .employee-section { margin-bottom: 40px; }
                .employee-header { background-color: #f0f0f0; padding: 10px; margin-bottom: 10px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Daily Time Record (DTR) Report</h1>
                <h2>' . $report->report_title . '</h2>
                <p>Generated on: ' . $report->formatted_generated_on . '</p>
            </div>

            <div class="report-info">
                <h3>Report Information</h3>
                <table>
                    <tr><th>Report ID</th><td>#' . $report->report_id . '</td><th>Department</th><td>' . $report->department_name . '</td></tr>
                    <tr><th>Report Type</th><td>' . ucfirst($report->report_type) . '</td><th>Period</th><td>' . $report->formatted_period . '</td></tr>
                    <tr><th>Total Employees</th><td>' . $report->total_employees . '</td><th>Total Days</th><td>' . $report->total_days . '</td></tr>
                    <tr><th>Total Hours</th><td>' . number_format($report->total_hours, 2) . ' hours</td><th>Status</th><td>' . ucfirst($report->status) . '</td></tr>
                </table>
            </div>';

        // Add employee summaries
        if ($report->summaries && count($report->summaries) > 0) {
            $html .= '
            <div class="summary">
                <h3>Employee Summary</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Present Days</th>
                            <th>Absent Days</th>
                            <th>Total Hours</th>
                            <th>Overtime Hours</th>
                            <th>Attendance Rate</th>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($report->summaries as $summary) {
                $html .= '
                         <tr>
                             <td>#' . $summary->employee_id . '</td>
                             <td>' . $summary->employee->full_name . '</td>
                             <td>' . $summary->employee->department->department_name . '</td>
                             <td>' . $summary->present_days . '</td>
                             <td>' . $summary->absent_days . '</td>
                             <td>' . number_format($summary->total_hours, 2) . '</td>
                             <td>' . number_format($summary->overtime_hours, 2) . '</td>
                             <td>' . number_format($summary->attendance_rate, 1) . '%</td>
                         </tr>';
            }

            $html .= '
                    </tbody>
                </table>
            </div>';
        }

        // Add detailed attendance records
        if ($report->details && count($report->details) > 0) {
            $html .= '
            <div class="details">
                <h3>Detailed Attendance Records</h3>';

            $currentEmployee = null;
            foreach ($report->details as $detail) {
                if ($currentEmployee !== $detail->employee_id) {
                    if ($currentEmployee !== null) {
                        $html .= '</table></div>';
                    }
                    $currentEmployee = $detail->employee_id;
                    $html .= '
                     <div class="employee-section">
                         <div class="employee-header">
                             <strong>Employee: ' . $detail->employee->full_name . ' (#' . $detail->employee_id . ')</strong>
                         </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Total Hours</th>
                                    <th>Overtime</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>';
                }

                // Check for override
                $dateKey = \Carbon\Carbon::parse($detail->date)->toDateString();
                $ovKey = $detail->employee_id . '|' . $dateKey;
                $ov = $overrides ? ($overrides[$ovKey] ?? null) : null;

                $status = $ov ? 'Leave' : ucfirst($detail->status);
                $remarks = $ov ? ('Leave' . ($ov->remarks ? ': ' . $ov->remarks : '')) : ($detail->remarks ?? '');

                $html .= '
                             <tr>
                                 <td>' . $detail->formatted_date . '</td>
                                 <td>' . $detail->formatted_time_in . '</td>
                                 <td>' . $detail->formatted_time_out . '</td>
                                 <td>' . number_format($detail->total_hours, 2) . '</td>
                                 <td>' . number_format($detail->overtime_hours, 2) . '</td>
                                 <td>' . $status . '</td>
                                 <td>' . $remarks . '</td>
                             </tr>';
            }

            if ($currentEmployee !== null) {
                $html .= '</tbody></table></div>';
            }

            $html .= '</div>';
        }

        $html .= '
        </body>
        </html>';

        return $html;
    }

    /**
     * Display the photo associated with an attendance log
     */
    public function showPhoto($id)
    {
        // Fetch raw BLOB directly to avoid any ORM casting side-effects
        $row = DB::table('attendance_logs')
            ->select('photo_data', 'photo_content_type')
            ->where('log_id', $id)
            ->first();

        if (!$row) {
            abort(404, 'Attendance log not found');
        }

        if ($row->photo_data === null || $row->photo_data === '') {
            abort(404, 'Photo not found for this attendance log');
        }

        // Get the raw photo data (BLOB) – may be a string or a stream resource depending on PDO config
        $photoData = $row->photo_data;

        // Normalize only when the column clearly contains base64 text
        $originalString = null;
        if (is_string($photoData)) {
            $originalString = $photoData;
            $raw = $photoData;
            // If it starts with data: strip prefix
            if (stripos($raw, 'data:') === 0) {
                $comma = strpos($raw, ',');
                if ($comma !== false) {
                    $raw = substr($raw, $comma + 1);
                }
            }
            // Decode only if it looks like base64
            $looksBase64 = preg_match('/^[A-Za-z0-9+\/\r\n=]+$/', $raw) && (strlen($raw) % 4 === 0);
            if ($looksBase64) {
                $decoded = base64_decode($raw, true);
                if ($decoded !== false && $decoded !== '') {
                    $photoData = $decoded;
                }
            }
        }

        // If returned as a stream resource from PDO, read into a string for consistent output
        if (is_resource($photoData)) {
            $photoData = stream_get_contents($photoData);
        }

        // Determine content type (fallback to magic bytes)
        $contentType = $row->photo_content_type ?: 'application/octet-stream';
        // Prefer finfo if available and safe to peek
        if (function_exists('finfo_open')) {
            $buffer = '';
            if (is_resource($photoData)) {
                $meta = stream_get_meta_data($photoData);
                $seekable = $meta['seekable'] ?? false;
                if ($seekable) {
                    $current = ftell($photoData);
                    $buffer = fread($photoData, 8192);
                    if ($current !== false) {
                        fseek($photoData, $current);
                    } else {
                        rewind($photoData);
                    }
                }
            } else {
                $buffer = (string) $photoData;
            }
            if ($buffer !== '') {
                $fi = finfo_open(FILEINFO_MIME_TYPE);
                if ($fi) {
                    $detected = finfo_buffer($fi, $buffer);
                    finfo_close($fi);
                    if ($detected) {
                        $contentType = $detected;
                    }
                }
            }
        }

        // If content type is still generic or image fails to load, attempt a last-chance base64 decode
        $isRecognized = false;
        if (strlen($photoData) >= 4) {
            $sig = bin2hex(substr($photoData, 0, 4));
            if (strpos($sig, 'ffd8') === 0) {
                $isRecognized = true;
                $contentType = 'image/jpeg';
            }
            if ($sig === '89504e47') {
                $isRecognized = true;
                $contentType = 'image/png';
            }
            if ($sig === '47494638') {
                $isRecognized = true;
                $contentType = 'image/gif';
            }
            if ($sig === '52494646') { /* could be WEBP */
                $isRecognized = true;
                $contentType = $contentType === 'application/octet-stream' ? 'image/webp' : $contentType;
            }
        }

        if (!$isRecognized && is_string($originalString)) {
            $try = preg_replace('/^data:[^,]*,/', '', $originalString);
            $try = preg_replace('/\s+/', '', $try);
            $decoded = base64_decode($try, false);
            if ($decoded !== false && strlen($decoded) > 4) {
                $sig = bin2hex(substr($decoded, 0, 4));
                if (strpos($sig, 'ffd8') === 0 || $sig === '89504e47' || $sig === '47494638' || $sig === '52494646') {
                    $photoData = $decoded;
                    if (strpos($sig, 'ffd8') === 0) {
                        $contentType = 'image/jpeg';
                    } elseif ($sig === '89504e47') {
                        $contentType = 'image/png';
                    } elseif ($sig === '47494638') {
                        $contentType = 'image/gif';
                    } elseif ($sig === '52494646') {
                        $contentType = 'image/webp';
                    }
                }
            }
        }

        // Repair known corruption pattern: leading bytes turned into '????' (0x3f) but JFIF appears at offset 6
        if (strlen($photoData) > 10) {
            $hasJfif = substr($photoData, 6, 4) === 'JFIF';
            $head = bin2hex(substr($photoData, 0, 4));
            if ($hasJfif && $head === '3f3f3f3f') {
                // Replace with valid JPEG SOI + APP0 marker
                $fixed = hex2bin('ffd8ffe0');
                $photoData = $fixed . substr($photoData, 4);
                $contentType = 'image/jpeg';
            }
        }

        // Do not transform bytes; just send what is stored

        // Debug: log basic info to help diagnose legacy rows that don't render
        // Send bytes as-is

        // Return the bytes directly; browsers can render inline
        // Choose proper file extension for download name
        $ext = 'jpg';
        if (stripos($contentType, 'png') !== false) $ext = 'png';
        if (stripos($contentType, 'gif') !== false) $ext = 'gif';

        return response($photoData, 200, [
            'Content-Type' => $contentType,
            'Content-Length' => (string) strlen($photoData),
            'Content-Disposition' => 'inline; filename="attendance_photo_' . $id . '.' . $ext . '"',
            'Cache-Control' => 'public, max-age=31536000'
        ]);
    }

    /**
     * Diagnostics for a specific attendance photo row to verify data integrity.
     */
    public function photoInfo($id)
    {
        $row = DB::table('attendance_logs')
            ->select('log_id', 'photo_data', 'photo_content_type')
            ->where('log_id', $id)
            ->first();

        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Attendance log not found'], 404);
        }

        $data = $row->photo_data;
        $isString = is_string($data);
        $len = $isString ? strlen($data) : 0;
        $firstHex = $len >= 16 ? bin2hex(substr($data, 0, 16)) : ($len > 0 ? bin2hex(substr($data, 0, $len)) : null);

        // If string might be base64, compute decoded signature/length
        $base64Info = null;
        if ($isString) {
            $raw = $data;
            if (stripos($raw, 'data:') === 0) {
                $comma = strpos($raw, ',');
                if ($comma !== false) {
                    $raw = substr($raw, $comma + 1);
                }
            }
            $sanitized = preg_replace('/\s+/', '', $raw);
            $decoded = base64_decode($sanitized, false);
            if ($decoded !== false && $decoded !== '') {
                $dlen = strlen($decoded);
                $dhex = bin2hex(substr($decoded, 0, min(16, $dlen)));
                $base64Info = [
                    'decoded_len' => $dlen,
                    'decoded_first_hex' => $dhex
                ];
            }
        }

        return response()->json([
            'ok' => true,
            'log_id' => $row->log_id,
            'db_content_type' => $row->photo_content_type,
            'stored_is_string' => $isString,
            'stored_len' => $len,
            'stored_first_hex' => $firstHex,
            'base64_probe' => $base64Info
        ]);
    }

    // RFID Verification Methods for Admin Review
    public function approveRfid(Request $request, $id)
    {
        $attendanceLog = AttendanceLog::findOrFail($id);

        // Check if user can verify this record
        if (
            auth()->user()->role->role_name !== 'super_admin' &&
            auth()->user()->department_id !== $attendanceLog->employee->department_id
        ) {
            abort(403, 'You can only verify attendance records from your department.');
        }

        // Check if this is an RFID record that needs verification
        if ($attendanceLog->method !== 'rfid') {
            return back()->with('error', 'Only RFID records can be verified.');
        }

        if ($attendanceLog->verification_status !== 'pending') {
            return back()->with('error', 'This record has already been processed.');
        }

        // Update verification status
        $attendanceLog->update([
            'is_verified' => true,
            'verification_status' => 'verified',
            'verified_by' => auth()->user()->admin_id,
            'verified_at' => now('Asia/Manila'),
            'verification_notes' => null
        ]);

        // Create audit log
        try {
            \App\Models\AuditLog::create([
                'admin_id' => auth()->user()->admin_id,
                'action' => 'verify',
                'model_type' => 'AttendanceLog',
                'model_id' => $attendanceLog->log_id,
                'old_values' => json_encode(['verification_status' => 'pending']),
                'new_values' => json_encode(['verification_status' => 'verified']),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create audit log for RFID verification', [
                'error' => $e->getMessage(),
                'log_id' => $attendanceLog->log_id
            ]);
        }

        return back()->with('success', 'RFID attendance record verified successfully!');
    }

    public function rejectRfid(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        $attendanceLog = AttendanceLog::findOrFail($id);

        // Check if user can reject this record
        if (
            auth()->user()->role->role_name !== 'super_admin' &&
            auth()->user()->department_id !== $attendanceLog->employee->department_id
        ) {
            abort(403, 'You can only reject attendance records from your department.');
        }

        // Check if this is an RFID record that needs verification
        if ($attendanceLog->method !== 'rfid') {
            return back()->with('error', 'Only RFID records can be rejected.');
        }

        if ($attendanceLog->verification_status !== 'pending') {
            return back()->with('error', 'This record has already been processed.');
        }

        // Update verification status
        $attendanceLog->update([
            'is_verified' => false,
            'verification_status' => 'rejected',
            'verified_by' => auth()->user()->admin_id,
            'verified_at' => now('Asia/Manila'),
            'verification_notes' => $request->rejection_reason
        ]);

        // Create audit log
        try {
            \App\Models\AuditLog::create([
                'admin_id' => auth()->user()->admin_id,
                'action' => 'reject',
                'model_type' => 'AttendanceLog',
                'model_id' => $attendanceLog->log_id,
                'old_values' => json_encode(['verification_status' => 'pending']),
                'new_values' => json_encode([
                    'verification_status' => 'rejected',
                    'verification_notes' => $request->rejection_reason
                ]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create audit log for RFID rejection', [
                'error' => $e->getMessage(),
                'log_id' => $attendanceLog->log_id
            ]);
        }

        return back()->with('success', 'RFID attendance record rejected successfully!');
    }

    // API endpoint to get verification data for a log
    public function getVerificationData($id)
    {
        $attendanceLog = AttendanceLog::findOrFail($id);

        // Check if user can view this record
        if (
            auth()->user()->role->role_name !== 'super_admin' &&
            auth()->user()->department_id !== $attendanceLog->employee->department_id
        ) {
            abort(403, 'You can only view attendance records from your department.');
        }

        return response()->json([
            'method' => $attendanceLog->method,
            'rfid_reason' => $attendanceLog->rfid_reason,
            'verification_status' => $attendanceLog->verification_status,
            'verification_badge' => $attendanceLog->getVerificationStatusBadge(),
            'verified_by' => $attendanceLog->verifiedBy ? $attendanceLog->verifiedBy->username : null,
            'verified_at' => $attendanceLog->verified_at ? $attendanceLog->verified_at->format('M d, Y h:i A') : null,
            'verification_notes' => $attendanceLog->verification_notes
        ]);
    }

    // Get count of pending RFID verifications for the current user's scope
    public static function getPendingRfidCount()
    {
        $query = AttendanceLog::where('method', 'rfid')->where('verification_status', 'pending');

        // Apply department restriction for non-super admins
        if (auth()->check() && auth()->user()->role->role_name !== 'super_admin' && auth()->user()->department_id) {
            $query->whereHas('employee', function ($q) {
                $q->where('department_id', auth()->user()->department_id);
            });
        }

        return $query->count();
    }
}
