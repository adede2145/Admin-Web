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
            'time_out' => 'nullable|date_format:H:i',
            'method' => 'required|in:rfid,fingerprint,manual',
        ]);

        // Validate time_out if provided
        if ($request->filled('time_out')) {
            try {
                // Validate that the time format is correct
                $timeOut = Carbon::createFromFormat('H:i', $request->time_out);
                Log::info('Time out validation passed', ['time_out' => $timeOut->format('H:i:s')]);
                
                // Validate that time_out is after time_in
                $timeIn = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . $request->time_in);
                $timeOutFull = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . $request->time_out);
                
                if ($timeOutFull->lessThan($timeIn)) {
                    return back()->withErrors(['time_out' => 'Time Out cannot be before Time In. If the employee worked past midnight, please create a separate attendance record for the next day.'])->withInput();
                }
                
                // Validate that the time difference is reasonable (max 24 hours in a single log)
                $hoursDiff = $timeIn->diffInHours($timeOutFull);
                if ($hoursDiff > 24) {
                    return back()->withErrors(['time_out' => 'Time difference exceeds 24 hours. Please verify the times or create separate records.'])->withInput();
                }
                
            } catch (\Exception $e) {
                Log::error('Time out validation error', [
                    'error' => $e->getMessage(),
                    'time_out' => $request->time_out
                ]);
                return back()->withErrors(['time_out' => 'Invalid time format.'])->withInput();
            }
        }

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

        // Log what we're about to update
        Log::info('Preparing update', [
            'request_data' => [
                'date' => $request->date,
                'time_in' => $request->time_in,
                'time_out' => $request->time_out,
                'method' => $request->method
            ],
            'processed_time_in' => $timeIn
        ]);

        if ($request->filled('time_out')) {
            $timeOut = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . $request->time_out)
                ->format('Y-m-d H:i:s');
            $updateData['time_out'] = $timeOut;
            Log::info('Setting time_out', ['time_out' => $timeOut]);
        } else {
            // Clear time_out if field is empty
            $updateData['time_out'] = null;
            Log::info('Clearing time_out');
        }

        // Log final update data
        Log::info('Final update data', ['updateData' => $updateData]);


        // Update the attendance log
        $attendanceLog->update($updateData);

        // Save new values for audit after the update
        $newValues = $attendanceLog->fresh()->only(['time_in', 'time_out', 'method']);
        
        // Log what was actually saved
        Log::info('Update completed', [
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'update_successful' => true
        ]);
        

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
            'kiosk_id' => 'required|exists:kiosks,kiosk_id',
            'last_reboot_at' => 'nullable|date_format:Y-m-d H:i:s',
            'uptime_seconds' => 'nullable|integer|min:0'
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

        // If kiosk sends boot time, update last_reboot_at
        if ($request->filled('last_reboot_at')) {
            $kiosk->update([
                'last_reboot_at' => $request->last_reboot_at
            ]);
        } elseif ($request->filled('uptime_seconds')) {
            // If only uptime in seconds is provided, calculate boot time
            $bootTime = now('Asia/Manila')->subSeconds($request->uptime_seconds);
            $kiosk->update([
                'last_reboot_at' => $bootTime
            ]);
        }

        // Persist heartbeat to history table for analytics graph
        try {
            \App\Models\KioskHeartbeat::create([
                'kiosk_id' => $kiosk->kiosk_id,
                'last_seen' => now('Asia/Manila')->toDateTimeString(),
                'location' => $kiosk->location
            ]);
        } catch (\Exception $e) {
            logger()->warning('Failed to persist kiosk heartbeat: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat updated successfully',
            'timestamp' => $kiosk->last_seen,
            'uptime_days' => $kiosk->uptime_days,
            'uptime' => $kiosk->uptime_formatted
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
                ->with('success', 'DTR report generated successfully!')
                ->with('generated_report_id', $dtrReport->report_id);
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
                case 'docx':
                    return $this->downloadAsDOCX($report, $overrides, $filename);
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
        $dompdf->setPaper('Letter', 'portrait');
        $dompdf->render();

        return response($dompdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"');
    }

    private function downloadAsCSV($report, $overrides, $filename)
    {
        $csvData = [];
        
        $startDate = \Carbon\Carbon::parse($report->start_date);
        $endDate = \Carbon\Carbon::parse($report->end_date);

        // Generate CSV for each employee
        foreach ($report->summaries as $summary) {
            $employee = $summary->employee;
            
            // Format period based on report type
            $periodLabel = '';
            if ($report->report_type === 'monthly') {
                $periodLabel = 'For the month of ' . $startDate->format('F Y');
            } elseif ($report->report_type === 'weekly') {
                $periodLabel = 'For the week of ' . $startDate->format('M d') . ' - ' . $endDate->format('M d, Y');
            } else {
                $periodLabel = 'For the period ' . $startDate->format('M d') . ' - ' . $endDate->format('M d, Y');
            }
            
            // Add headers for each employee
            $csvData[] = ['Civil Service Form No. 48'];
            $csvData[] = ['DAILY TIME RECORD'];
            $csvData[] = [];
            $csvData[] = [$employee->full_name];
            $csvData[] = ['(Name)'];
            $csvData[] = [];
            $csvData[] = [$periodLabel];
            $csvData[] = ['Official hours for arrival and departure: Regular days 8:00 AM - 5:00 PM', 'Saturdays: N/A'];
            $csvData[] = [];
            
            // Table header
            $csvData[] = ['Day', 'A.M. Arrival', 'A.M. Departure', 'P.M. Arrival', 'P.M. Departure', 'Undertime Hours', 'Undertime Min.'];
            
            // Get attendance details for this employee
            $employeeDetails = $report->details->where('employee_id', $employee->employee_id);
            $detailsByDate = [];
            foreach ($employeeDetails as $detail) {
                $dateKey = \Carbon\Carbon::parse($detail->date)->toDateString();
                $detailsByDate[$dateKey] = $detail;
            }
            
            // Loop through the actual date range of the report
            $currentDate = $startDate->copy();
            
            while ($currentDate->lte($endDate)) {
                $dateKey = $currentDate->toDateString();
                $detail = $detailsByDate[$dateKey] ?? null;
                
                // Check for override
                $ovKey = $employee->employee_id . '|' . $dateKey;
                $ov = $overrides ? ($overrides[$ovKey] ?? null) : null;
                
                // Calculate AM/PM times and undertime
                $amData = $this->extractAMPMTimes($detail, $ov, $currentDate);
                
                $csvData[] = [
                    $currentDate->format('j'),
                    $amData['am_arrival'],
                    $amData['am_departure'],
                    $amData['pm_arrival'],
                    $amData['pm_departure'],
                    $amData['undertime_hours'],
                    $amData['undertime_minutes']
                ];
                
                $currentDate->addDay();
            }
            
            // Total row
            $totalUndertime = $this->calculateTotalUndertime($employeeDetails, $overrides, $employee->employee_id, $startDate, $endDate);
            $csvData[] = ['Total', '', '', '', '', $totalUndertime['hours'], $totalUndertime['minutes']];
            
            $csvData[] = [];
            $csvData[] = ['I certify on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.'];
            $csvData[] = [];
            $csvData[] = ['VERIFIED as to the prescribed office hours'];
            $csvData[] = [];
            $csvData[] = ['________________________________________'];
            $csvData[] = ['In Charge'];
            $csvData[] = [];
            $csvData[] = [];
            $csvData[] = ['----------------------------------------'];
            $csvData[] = [];
        }

        $csvContent = '';
        foreach ($csvData as $row) {
            $csvContent .= implode(',', array_map(function ($field) {
                return '"' . str_replace('"', '""', $field ?? '') . '"';
            }, $row)) . "\n";
        }

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '.csv"');
    }

    
    private function downloadAsDOCX($report, $overrides, $filename)
    {
        try {
            // Process only the first employee for now
            if ($report->summaries->isEmpty()) {
                throw new \Exception('No employee data found in the report.');
            }
            
            $summary = $report->summaries->first();
            $employee = $summary->employee;
            
            // Load template
            $templatePath = storage_path('app/templates/dtr_template.docx');
            if (!file_exists($templatePath)) {
                throw new \Exception('Template file not found at: ' . $templatePath);
            }
            
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
            
            // Set period
            $startDate = \Carbon\Carbon::parse($report->start_date);
            $endDate = \Carbon\Carbon::parse($report->end_date);
            
            // Prepare period label parts
            $periodPrefix = '';
            $periodDate = '';
            if ($report->report_type === 'monthly') {
                $periodPrefix = 'For the month of:';
                $periodDate = $startDate->format('F j') . '-' . $endDate->format('j, Y');
            } elseif ($report->report_type === 'weekly') {
                $periodPrefix = 'For the week of:';
                $periodDate = $startDate->format('M d') . ' - ' . $endDate->format('M d, Y');
            } else {
                $periodPrefix = 'For the period:';
                $periodDate = $startDate->format('M d') . ' - ' . $endDate->format('M d, Y');
            }
            
            // Get attendance details for this employee
            $employeeDetails = $report->details->where('employee_id', $employee->employee_id);
            $detailsByDate = [];
            foreach ($employeeDetails as $detail) {
                $dateKey = \Carbon\Carbon::parse($detail->date)->toDateString();
                $detailsByDate[$dateKey] = $detail;
            }
            
            // Prepare days data
            $daysData = [];
            $currentDate = $startDate->copy();
            
            while ($currentDate->lte($endDate)) {
                $dateKey = $currentDate->toDateString();
                $detail = $detailsByDate[$dateKey] ?? null;
                
                // Check for override
                $ovKey = $employee->employee_id . '|' . $dateKey;
                $ov = $overrides ? ($overrides[$ovKey] ?? null) : null;
                
                // Check if weekend
                $isWeekend = $currentDate->isWeekend();
                $dayName = $isWeekend ? strtoupper($currentDate->format('l')) : '';
                
                // Calculate AM/PM times and undertime
                $amData = $this->extractAMPMTimes($detail, $ov, $currentDate);
                
                $daysData[] = [
                    'day' => $currentDate->format('j'),
                    'am_arrival' => $amData['am_arrival'] ?: '',
                    'am_departure' => $amData['am_departure'] ?: '',
                    'pm_arrival' => $amData['pm_arrival'] ?: '',
                    'pm_departure' => $amData['pm_departure'] ?: '',
                    'undertime_hours' => $amData['undertime_hours'] !== '' ? $amData['undertime_hours'] : '',
                    'undertime_minutes' => $amData['undertime_minutes'] !== '' ? $amData['undertime_minutes'] : '',
                    'is_weekend' => $isWeekend,
                    'day_name' => $dayName,
                    'is_leave' => $ov !== null,
                    'leave_text' => $ov ? strtoupper($ov->remarks ?: 'LEAVE') : '',
                ];
                
                $currentDate->addDay();
            }
            
            // Calculate totals
            $totalUndertime = $this->calculateTotalUndertime($employeeDetails, $overrides, $employee->employee_id, $startDate, $endDate);
            
            // Set basic template variables
            $templateProcessor->setValue('employee_name', $employee->full_name);
            $templateProcessor->setValue('period_label', $periodPrefix);
            $templateProcessor->setValue('period_date', $periodDate);
            $templateProcessor->setValue('office_hours', '8:00AM-12:00NN  /  1:00PM-5:00PM');
            
            // Prepare summary text
            $summaryParts = [];
            if ($totalUndertime['leave_days'] > 0) {
                $summaryParts[] = $totalUndertime['leave_days'] . ' day' . ($totalUndertime['leave_days'] > 1 ? 's' : '') . ' leave w/ pay';
            }
            if ($totalUndertime['tardy_days'] > 0) {
                $summaryParts[] = $totalUndertime['tardy_days'] . ' day' . ($totalUndertime['tardy_days'] > 1 ? 's' : '') . ' tardy';
            } else {
                $summaryParts[] = 'no tardy';
            }
            if ($totalUndertime['has_undertime']) {
                $summaryParts[] = $totalUndertime['hours'] . 'h ' . $totalUndertime['minutes'] . 'm undertime';
            } else {
                $summaryParts[] = 'no undertime';
            }
            $summaryText = implode('; ', $summaryParts);
            $templateProcessor->setValue('summary_text', $summaryText);
            
            // Clone the row for days data
            $templateProcessor->cloneRow('day', count($daysData));
            
            // Keep track of which rows are weekends/leaves for later merging
            $weekendRowIndices = [];
            
            // Fill in day data
            $index = 1;
            foreach ($daysData as $dayData) {
                $templateProcessor->setValue('day#' . $index, $dayData['day']);
            
                // Handle weekends and leave days (span across all columns)
                if ($dayData['is_leave'] || $dayData['is_weekend']) {
                    $displayText = $dayData['is_leave'] ? $dayData['leave_text'] : $dayData['day_name'];
                    $templateProcessor->setValue('am_arrival#' . $index, $displayText);
                    $templateProcessor->setValue('am_departure#' . $index, '');
                    $templateProcessor->setValue('pm_arrival#' . $index, '');
                    $templateProcessor->setValue('pm_departure#' . $index, '');
                    $templateProcessor->setValue('undertime_hours#' . $index, '');
                    $templateProcessor->setValue('undertime_minutes#' . $index, '');
                    $weekendRowIndices[] = $index - 1; // Store for later processing
                } else {
                    // Regular day
                    $templateProcessor->setValue('am_arrival#' . $index, $dayData['am_arrival'] ? $dayData['am_arrival'] . ' ' : '');
                    $templateProcessor->setValue('am_departure#' . $index, $dayData['am_departure'] ? $dayData['am_departure'] . ' ' : '');
                    $templateProcessor->setValue('pm_arrival#' . $index, $dayData['pm_arrival'] ? $dayData['pm_arrival'] . ' ' : '');
                    $templateProcessor->setValue('pm_departure#' . $index, $dayData['pm_departure'] ? $dayData['pm_departure'] . ' ' : '');
                    $templateProcessor->setValue('undertime_hours#' . $index, $dayData['undertime_hours']);
                    $templateProcessor->setValue('undertime_minutes#' . $index, $dayData['undertime_minutes']);
                }
                $index++;
            }
            
            // Save intermediate file
            $tempOutputPath = storage_path('app/temp_dtr_' . uniqid() . '.docx');
            $templateProcessor->saveAs($tempOutputPath);
                
            // Post-process: Merge cells for weekend/leave rows
            if (!empty($weekendRowIndices)) {
                $this->mergeCellsInDOCX($tempOutputPath, $weekendRowIndices, $daysData);
            }
            
            // Move to final output path
            $outputPath = storage_path('app/public/DTR.docx');
            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }
            rename($tempOutputPath, $outputPath);
            
            return response()->download($outputPath, $filename . '.docx')->deleteFileAfterSend();
            
        } catch (\Exception $e) {
            Log::error('DOCX export error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw new \Exception('Failed to generate DOCX: ' . $e->getMessage());
        }
    }

    private function mergeCellsInDOCX($filePath, $weekendRowIndices, $daysData)
    {
        try {
            // Load the DOCX file
            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) {
                Log::warning('Could not open DOCX for cell merging');
                return;
            }
            
            // Read document.xml
            $documentXml = $zip->getFromName('word/document.xml');
            if ($documentXml === false) {
                $zip->close();
                Log::warning('Could not read document.xml');
                return;
            }
            
            // Parse XML
            $dom = new \DOMDocument();
            $dom->loadXML($documentXml);
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            
            // Find all table rows
            $rows = $xpath->query('//w:tbl//w:tr');
            
            // Find the data table (look for rows with our data)
            $dataTableStartRow = null;
            
            // Look for the first numeric day value to identify data rows
            for ($rowIndex = 0; $rowIndex < $rows->length; $rowIndex++) {
                $row = $rows->item($rowIndex);
                $cells = $xpath->query('.//w:tc', $row);
                
                if ($cells->length === 0) {
                    continue;
                }
                
                // Check first cell for a day number
                $firstCell = $cells->item(0);
                $textNodes = $xpath->query('.//w:t', $firstCell);
                
                foreach ($textNodes as $textNode) {
                    $text = trim($textNode->nodeValue);
                    // Look for single digit or double digit (day numbers 1-31)
                    if (is_numeric($text) && $text >= 1 && $text <= 31) {
                        $dataTableStartRow = $rowIndex;
                        break 2;
                    }
                }
            }
            
            if ($dataTableStartRow === null) {
                $zip->close();
                Log::warning('Could not find data table start row');
                return;
            }
            
            Log::info('Found data table at row: ' . $dataTableStartRow);
            
            // Merge cells for weekend rows
            foreach ($weekendRowIndices as $relativeIndex) {
                $actualRowIndex = $dataTableStartRow + $relativeIndex;
                
                if ($actualRowIndex >= $rows->length) {
                    continue;
                }
                
                $row = $rows->item($actualRowIndex);
                $cells = $xpath->query('.//w:tc', $row);
                
                if ($cells->length < 2) {
                    continue; // Not enough cells
                }
                
                // Count total cells to determine span
                $totalCells = $cells->length;
                
                // We want to merge all cells except the first one (Day column)
                // So we need 6 columns to span (assuming 7 total: Day + 6 data columns)
                $columnsToSpan = max(1, $totalCells - 1);
                
                // Get the second cell (first cell after "Day" column)
                $firstDataCell = $cells->item(1);
                
                // Find or create tcPr (table cell properties)
                $tcPr = $xpath->query('.//w:tcPr', $firstDataCell)->item(0);
                if (!$tcPr) {
                    $tcPr = $dom->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:tcPr');
                    $firstDataCell->insertBefore($tcPr, $firstDataCell->firstChild);
                }
                
                // Add or update gridSpan
                $existingGridSpan = $xpath->query('.//w:gridSpan', $tcPr)->item(0);
                if ($existingGridSpan) {
                    $tcPr->removeChild($existingGridSpan);
                }
                
                // Create new gridSpan element with attribute
                $gridSpan = $dom->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:gridSpan');
                /** @var \DOMElement $gridSpan */
                $gridSpan->setAttribute('w:val', (string)$columnsToSpan);
                $tcPr->appendChild($gridSpan);
                
                // Also center-align the text in the merged cell
                $existingJc = $xpath->query('.//w:jc', $tcPr)->item(0);
                if ($existingJc) {
                    $tcPr->removeChild($existingJc);
                }
                $jc = $dom->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:jc');
                /** @var \DOMElement $jc */
                $jc->setAttribute('w:val', 'center');
                $tcPr->appendChild($jc);
                
                // Make text bold for weekends/leaves
                $paragraphs = $xpath->query('.//w:p', $firstDataCell);
                foreach ($paragraphs as $paragraph) {
                    $runs = $xpath->query('.//w:r', $paragraph);
                    foreach ($runs as $run) {
                        // Find or create run properties (rPr)
                        $rPr = $xpath->query('.//w:rPr', $run)->item(0);
                        if (!$rPr) {
                            $rPr = $dom->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:rPr');
                            $run->insertBefore($rPr, $run->firstChild);
                        }
                        
                        // Add bold element if not exists
                        $existingBold = $xpath->query('.//w:b', $rPr)->item(0);
                        if (!$existingBold) {
                            $bold = $dom->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:b');
                            $rPr->appendChild($bold);
                        }
                    }
                }
                
                // Remove subsequent cells (except first) - they become part of the merged cell
                $cellsToRemove = [];
                for ($i = 2; $i < $cells->length; $i++) {
                    $cellsToRemove[] = $cells->item($i);
                }
                
                foreach ($cellsToRemove as $cellToRemove) {
                    $row->removeChild($cellToRemove);
                }
            }
            
            // Save modified XML back
            $zip->addFromString('word/document.xml', $dom->saveXML());
            $zip->close();
            
        } catch (\Exception $e) {
            Log::error('Cell merging failed', ['error' => $e->getMessage()]);
            // Don't throw - fail gracefully
        }
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
                @page {
                    size: A4 portrait;
                    margin: 0.4cm 0.5cm;
                }
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0;
                    padding: 0;
                    font-size: 8pt;
                }
                .page-container {
                    display: table;
                    width: 100%;
                    height: 100%;
                }
                .two-column {
                    display: table-row;
                }
                .left-column, .right-column {
                    display: table-cell;
                    width: 50%;
                    vertical-align: top;
                    padding: 2px;
                }
                .left-column {
                    border-right: 1px solid #ccc;
                    padding-right: 3px;
                }
                .right-column {
                    padding-left: 3px;
                }
                .form-number {
                    font-size: 8pt;
                    font-style: italic;
                    text-align: left;
                    margin-bottom: 8px;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 10px;
                }
                .header h1 {
                    margin: 5px 0;
                    font-size: 12pt;
                    font-weight: bold;
                    letter-spacing: 0.5px;
                }
                .header .decorative {
                    text-align: center;
                    margin: 5px 0;
                    font-size: 8pt;
                }
                .employee-info {
                    margin: 8px 0;
                }
                .employee-info table {
                    width: 100%;
                    margin-bottom: 5px;
                }
                .employee-info td {
                    padding: 3px 0;
                    font-size: 8pt;
                }
                .employee-info .underline {
                    border-bottom: 1px solid #000;
                    display: inline-block;
                    min-width: 100px;
                    padding: 0 3px;
                }
                .work-schedule {
                    margin: 5px 0;
                    font-size: 8pt;
                    font-style: italic;
                    line-height: 1.6;
                }
                .dtr-table { 
                    width: 100%; 
                    border-collapse: collapse;
                    margin: 3px 0;
                    font-size: 9pt;
                }
                .dtr-table th, .dtr-table td { 
                    border: 1px solid #000; 
                    padding: 1px;
                    text-align: center;
                    line-height: 1;
                }
                .dtr-table th { 
                    background-color: #f5f5f5;
                    font-weight: bold;
                    font-size: 7pt;
                    padding: 2px 1px;
                }
                .dtr-table .day-col { width: 18px; font-size: 7pt; }
                .dtr-table .time-col { width: 45px; font-size: 7pt; }
                .dtr-table .undertime-col { width: 26px; font-size: 7pt; }
                .dtr-table .am-pm-header { font-weight: bold; font-size: 9pt; }
                .dtr-table .undertime-header { font-weight: bold; font-size: 8pt; }
                .dtr-table .total-row { font-size: 9pt; font-weight: bold; }
                .certification {
                    margin-top: 10px;
                    font-size: 7pt;
                    font-style: italic;
                    line-height: 1.6;
                }
                .certification p {
                    margin: 5px 0;
                }
                .signature-line {
                    margin-top: 15px;
                    text-align: center;
                    font-size: 7pt;
                }
                .signature-line .line {
                    border-top: 1px solid #000;
                    width: 120px;
                    margin: 0 auto 5px;
                }
                .right-column img {
                    width: 100%;
                    height: auto;
                    max-height: 100%;
                    object-fit: contain;
                    display: block;
                }
                .instructions {
                    font-size: 6pt;
                    line-height: 1.3;
                    text-align: justify;
                }
                .instructions h2 {
                    font-size: 8pt;
                    text-align: center;
                    margin: 5px 0;
                    font-weight: bold;
                }
                .instructions .diamond {
                    text-align: center;
                    margin: 3px 0;
                    font-size: 7pt;
                }
                .instructions p {
                    margin: 4px 0;
                }
                .page-break { 
                    page-break-after: always;
                }
            </style>
        </head>
        <body>';

        // Generate DTR for each employee in CSC Form 48 format
        if ($report->summaries && count($report->summaries) > 0) {
            $employeeCount = 0;
            
            foreach ($report->summaries as $summary) {
                $employeeCount++;
                $employee = $summary->employee;
                
                // Add page break after first employee
                if ($employeeCount > 1) {
                    $html .= '<div class="page-break"></div>';
                }
                
                $html .= '<div class="page-container">';
                $html .= '<div class="two-column">';
                
                // LEFT COLUMN - DTR FORM
                $html .= '<div class="left-column">';
                
                // Form number
                $html .= '<div class="form-number">Civil Service Form No. 48</div>';
                
                // Header
                $html .= '
                <div class="header">
                    <h1>DAILY TIME RECORD</h1>
                    <div class="decorative">————◇◇◇————</div>
                </div>';
                
                // Employee info
                $startDate = \Carbon\Carbon::parse($report->start_date);
                $endDate = \Carbon\Carbon::parse($report->end_date);
                
                // Format period based on report type
                $periodLabel = '';
                if ($report->report_type === 'monthly') {
                    $periodLabel = 'For the month of <span class="underline">' . $startDate->format('F j') . '-' . $endDate->format('j, Y') . '</span>';
                } elseif ($report->report_type === 'weekly') {
                    $periodLabel = 'For the week of <span class="underline">' . $startDate->format('M d') . ' - ' . $endDate->format('M d, Y') . '</span>';
                } else {
                    $periodLabel = 'For the period <span class="underline">' . $startDate->format('M d') . ' - ' . $endDate->format('M d, Y') . '</span>';
                }
                
                $html .= '
                <div class="employee-info">
                    <table>
                        <tr>
                            <td colspan="2" style="text-align: center;">
                                <span class="underline" style="font-size: 10pt;">' . htmlspecialchars($employee->full_name) . '</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align: center; font-size: 8pt;">(Name)</td>
                        </tr>
                    </table>
                    <div class="work-schedule">
                        ' . $periodLabel . '<br/>
                        <span class="underline">8:00AM-12:00NN / 1:00PM-5:00PM</span>
                    </div>
                </div>';
                
                // DTR Table
                $html .= '
                <table class="dtr-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="day-col">Day</th>
                            <th colspan="2" class="am-pm-header">A.M.</th>
                            <th colspan="2" class="am-pm-header">P.M.</th>
                            <th colspan="2" class="undertime-header">Undertime</th>
                        </tr>
                        <tr>
                            <th class="time-col">Arrival</th>
                            <th class="time-col">Departure</th>
                            <th class="time-col">Arrival</th>
                            <th class="time-col">Departure</th>
                            <th class="undertime-col">Hours</th>
                            <th class="undertime-col">Min.</th>
                        </tr>
                    </thead>
                    <tbody>';
                
                // Get attendance details for this employee
                $employeeDetails = $report->details->where('employee_id', $employee->employee_id);
                $detailsByDate = [];
                foreach ($employeeDetails as $detail) {
                    $dateKey = \Carbon\Carbon::parse($detail->date)->toDateString();
                    $detailsByDate[$dateKey] = $detail;
                }
                
                // Loop through the actual date range of the report
                $currentDate = $startDate->copy();
                $dayCounter = 1;
                
                while ($currentDate->lte($endDate)) {
                    $dateKey = $currentDate->toDateString();
                    $detail = $detailsByDate[$dateKey] ?? null;
                    
                    // Check for override
                    $ovKey = $employee->employee_id . '|' . $dateKey;
                    $ov = $overrides ? ($overrides[$ovKey] ?? null) : null;
                    
                    // Check if weekend
                    $isWeekend = $currentDate->isWeekend();
                    $dayName = '';
                    if ($isWeekend) {
                        $dayName = strtoupper($currentDate->format('l')); // SATURDAY or SUNDAY
                    }
                    
                    // Calculate AM/PM times and undertime
                    $amData = $this->extractAMPMTimes($detail, $ov, $currentDate);
                    
                    $html .= '<tr>';
                    
                    // Always show the day number first
                    $html .= '<td>' . $currentDate->format('j') . '</td>';
                    
                    // If leave/override, display leave reason spanning remaining columns
                    if ($ov) {
                        $leaveText = strtoupper($ov->remarks ?: 'LEAVE');
                        $html .= '<td colspan="6" style="font-weight: bold; padding: 6px 1px;">' . htmlspecialchars($leaveText) . '</td>';
                    }
                    // If weekend, display day name spanning remaining columns
                    elseif ($isWeekend) {
                        $html .= '<td colspan="6" style="font-weight: bold; padding: 6px 1px;">' . $dayName . '</td>';
                    } else {
                        $html .= '<td>' . $amData['am_arrival'] . '</td>';
                        $html .= '<td>' . $amData['am_departure'] . '</td>';
                        $html .= '<td>' . $amData['pm_arrival'] . '</td>';
                        $html .= '<td>' . $amData['pm_departure'] . '</td>';
                        $html .= '<td>' . $amData['undertime_hours'] . '</td>';
                        $html .= '<td>' . $amData['undertime_minutes'] . '</td>';
                    }
                    
                    $html .= '</tr>';
                    
                    $currentDate->addDay();
                    $dayCounter++;
                }
                
                // Total row
                $totalUndertime = $this->calculateTotalUndertime($employeeDetails, $overrides, $employee->employee_id, $startDate, $endDate);
                
                // Build total summary text
                $summaryParts = [];
                
                if ($totalUndertime['leave_days'] > 0) {
                    $summaryParts[] = $totalUndertime['leave_days'] . ' day' . ($totalUndertime['leave_days'] > 1 ? 's' : '') . ' leave w/ pay';
                }
                
                if ($totalUndertime['tardy_days'] > 0) {
                    $summaryParts[] = $totalUndertime['tardy_days'] . ' day' . ($totalUndertime['tardy_days'] > 1 ? 's' : '') . ' tardy';
                } else {
                    $summaryParts[] = 'no tardy';
                }
                
                if ($totalUndertime['has_undertime']) {
                    $summaryParts[] = $totalUndertime['hours'] . 'h ' . $totalUndertime['minutes'] . 'm undertime';
                } else {
                    $summaryParts[] = 'no undertime';
                }
                
                $summaryText = implode('; ', $summaryParts);
                
                $html .= '
                        <tr class="total-row">
                            <td colspan="7" style="text-align: left; padding: 4px 8px; font-size: 8pt;">
                                <strong>TOTAL</strong><br/>
                                ' . htmlspecialchars($summaryText) . '
                            </td>
                        </tr>
                    </tbody>
                </table>';
                
                // Certification
                $html .= '
                <div class="certification">
                    <p>I certify on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.</p>
                </div>
                
                <div class="signature-line">
                    <div class="line"></div>
                    <div style="margin-top: 5px;">VERIFIED as to the prescribed office hours</div>
                    <div style="margin-top: 15px;">
                        <div class="line"></div>
                        <div style="margin-top: 5px;">In Charge</div>
                    </div>
                </div>';
                
                $html .= '</div>'; // End left-column
                
                // RIGHT COLUMN - INSTRUCTIONS IMAGE
                $html .= '<div class="right-column">';
                
                // Get the image path and embed as base64 for PDF
                $imagePath = public_path('dtr-instructions.png');
                
                if (file_exists($imagePath)) {
                    // Convert image to base64 for PDF embedding
                    $imageData = base64_encode(file_get_contents($imagePath));
                    $imageSrc = 'data:image/png;base64,' . $imageData;
                    
                    $html .= '<img src="' . $imageSrc . '" style="width: 100%; height: auto; display: block; object-fit: contain;" alt="DTR Instructions">';
                } else {
                    // Fallback if image not found
                    $html .= '<div class="instructions">';
                    $html .= '<h2>INSTRUCTIONS</h2>';
                    $html .= '<div class="diamond">◇◇◇</div>';
                    $html .= '<p style="color: red; font-weight: bold;">Instructions image not found at: public/dtr-instructions.png</p>';
                    $html .= '<p>Please ensure the image file is placed in the public folder.</p>';
                    $html .= '</div>';
                }
                
                $html .= '</div>'; // End right-column
                
                $html .= '</div>'; // End two-column
                $html .= '</div>'; // End page-container
            }
        }

        $html .= '
        </body>
        </html>';

        return $html;
    }
    
    /**
     * Extract AM and PM times from attendance detail
     */
    private function extractAMPMTimes($detail, $override, $currentDate)
    {
        // Default empty values
        $result = [
            'am_arrival' => '',
            'am_departure' => '',
            'pm_arrival' => '',
            'pm_departure' => '',
            'undertime_hours' => '',
            'undertime_minutes' => ''
        ];
        
        // Check if on leave
        if ($override) {
            return $result; // Leave days show blank
        }
        
        // Check if weekend
        if ($currentDate->isWeekend()) {
            return $result; // Weekends show blank
        }
        
        // If no detail or absent - don't calculate undertime, just leave blank
        if (!$detail || $detail->status === 'absent') {
            return $result; // No attendance = blank, not 8 hours undertime
        }
        
        // Get all attendance logs for this day to split AM/PM
        $logs = \App\Models\AttendanceLog::where('employee_id', $detail->employee_id)
            ->whereDate('time_in', $detail->date)
            ->where('time_in', '>=', '1900-01-01 00:00:00')
            ->orderBy('time_in')
            ->get();
        
        // If no logs exist, leave blank (no time in/out recorded)
        if ($logs->isEmpty()) {
            return $result;
        }
        
        // Filter out logs with invalid time_out (time_out before time_in)
        $logs = $logs->filter(function($log) {
            if ($log->time_in && $log->time_out) {
                return $log->time_out->greaterThanOrEqualTo($log->time_in);
            }
            // Keep logs with only time_in (incomplete) for display purposes
            return true;
        });
        
        // Initialize AM/PM log arrays
        $amLogs = [];
        $pmLogs = [];
        $isSingleSpanningLog = false;
        
        // Check if there's a single log that spans both AM and PM
        if ($logs->count() == 1) {
            $log = $logs->first();
            if ($log->time_in && $log->time_out) {
                $timeInHour = (int) $log->time_in->format('H');
                $timeOutHour = (int) $log->time_out->format('H');
                
                // If time_in is AM (before 12) and time_out is PM (12 or after)
                if ($timeInHour < 12 && $timeOutHour >= 12) {
                    // This is a single log spanning both AM and PM
                    $isSingleSpanningLog = true;
                    $result['am_arrival'] = $log->time_in->format('h:i A');
                    $result['am_departure'] = '12:00 PM'; // Assume lunch break starts at 12
                    $result['pm_arrival'] = '01:00 PM'; // Assume lunch break ends at 1 PM
                    $result['pm_departure'] = $log->time_out->format('h:i A');
                } elseif ($timeInHour < 12) {
                    // AM only
                    $result['am_arrival'] = $log->time_in->format('h:i A');
                    $result['am_departure'] = $log->time_out->format('h:i A');
                } else {
                    // PM only
                    $result['pm_arrival'] = $log->time_in->format('h:i A');
                    $result['pm_departure'] = $log->time_out->format('h:i A');
                }
            } elseif ($log->time_in) {
                // Only time in, no time out
                $timeInHour = (int) $log->time_in->format('H');
                if ($timeInHour < 12) {
                    $result['am_arrival'] = $log->time_in->format('h:i A');
                } else {
                    $result['pm_arrival'] = $log->time_in->format('h:i A');
                }
            }
        } else {
            // Multiple logs - separate them into AM and PM
            foreach ($logs as $log) {
                if ($log->time_in) {
                    $hour = (int) $log->time_in->format('H');
                    if ($hour < 12) {
                        $amLogs[] = $log;
                    } else {
                        $pmLogs[] = $log;
                    }
                }
            }
            
            // Extract AM times
            if (!empty($amLogs)) {
                $firstAM = $amLogs[0];
                $result['am_arrival'] = $firstAM->time_in ? $firstAM->time_in->format('h:i A') : '';
                
                // AM departure could be from the same log or the last AM log
                $lastAM = end($amLogs);
                $result['am_departure'] = $lastAM->time_out ? $lastAM->time_out->format('h:i A') : '';
            }
            
            // Extract PM times
            if (!empty($pmLogs)) {
                $firstPM = $pmLogs[0];
                $result['pm_arrival'] = $firstPM->time_in ? $firstPM->time_in->format('h:i A') : '';
                
                $lastPM = end($pmLogs);
                $result['pm_departure'] = $lastPM->time_out ? $lastPM->time_out->format('h:i A') : '';
            }
        }
        
        // Calculate undertime
        // Standard work day: 8:00 AM - 12:00 PM (4 hrs) and 1:00 PM - 5:00 PM (4 hrs) = 8 hours total
        $totalWorkedMinutes = 0;
        $hasTimeIn = false;
        
        // Handle single log case
        if ($logs->count() == 1) {
            $log = $logs->first();
            if ($log->time_in && $log->time_out) {
                $hasTimeIn = true;
                
                // If it spans AM to PM (already detected), deduct 1 hour lunch break
                if ($isSingleSpanningLog) {
                    $totalMinutes = $log->time_in->diffInMinutes($log->time_out);
                    $totalWorkedMinutes = $totalMinutes - 60; // Deduct 1 hour lunch
                } else {
                    // Single session (either AM only or PM only)
                    $totalWorkedMinutes = $log->time_in->diffInMinutes($log->time_out);
                }
            } elseif ($log->time_in) {
                $hasTimeIn = true;
                // Only time in, no time out - no worked minutes
            }
        } else {
            // Multiple logs - calculate separately
            // Calculate AM work time
            if (!empty($amLogs)) {
                $firstAM = $amLogs[0];
                $lastAM = end($amLogs);
                if ($firstAM->time_in) {
                    $hasTimeIn = true;
                    if ($lastAM->time_out) {
                        $totalWorkedMinutes += $firstAM->time_in->diffInMinutes($lastAM->time_out);
                    }
                }
            }
            
            // Calculate PM work time  
            if (!empty($pmLogs)) {
                $firstPM = $pmLogs[0];
                $lastPM = end($pmLogs);
                if ($firstPM->time_in) {
                    $hasTimeIn = true;
                    if ($lastPM->time_out) {
                        $totalWorkedMinutes += $firstPM->time_in->diffInMinutes($lastPM->time_out);
                    }
                }
            }
        }
        
        // Only calculate undertime if there's at least one time-in recorded
        // AND the log has a corresponding time-out
        // Incomplete logs (only time_in, no time_out) should NOT show undertime
        if ($hasTimeIn && $totalWorkedMinutes > 0) {
            // Expected work time: 8 hours = 480 minutes
            $expectedMinutes = 480;
            $undertimeMinutes = max(0, $expectedMinutes - $totalWorkedMinutes);
            
            if ($undertimeMinutes > 0) {
                $result['undertime_hours'] = floor($undertimeMinutes / 60);
                $result['undertime_minutes'] = str_pad($undertimeMinutes % 60, 2, '0', STR_PAD_LEFT);
            }
        } else if ($hasTimeIn && $totalWorkedMinutes == 0) {
            // Has time_in but no time_out (incomplete record)
            // Don't calculate undertime - leave blank to indicate incomplete
            // This prevents showing 8 hours undertime for incomplete records
        }
        
        return $result;
    }
    
    /**
     * Calculate total undertime for the period
     */
    private function calculateTotalUndertime($details, $overrides, $employeeId, $startDate, $endDate)
    {
        $totalUndertimeMinutes = 0;
        $leaveDays = 0;
        $tardyDays = 0;
        
        $detailsByDate = [];
        foreach ($details as $detail) {
            $dateKey = \Carbon\Carbon::parse($detail->date)->toDateString();
            $detailsByDate[$dateKey] = $detail;
        }
        
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            $dateKey = $currentDate->toDateString();
            $detail = $detailsByDate[$dateKey] ?? null;
            
            $ovKey = $employeeId . '|' . $dateKey;
            $ov = $overrides ? ($overrides[$ovKey] ?? null) : null;
            
            // Count leave days
            if ($ov) {
                $leaveDays++;
            }
            
            // Check for tardy (late arrival)
            if (!$ov && !$currentDate->isWeekend() && $detail) {
                $logs = \App\Models\AttendanceLog::where('employee_id', $employeeId)
                    ->whereDate('time_in', $dateKey)
                    ->where('time_in', '>=', '1900-01-01 00:00:00')
                    ->orderBy('time_in')
                    ->first();
                
                if ($logs && $logs->time_in) {
                    $timeInHour = (int) $logs->time_in->format('H');
                    $timeInMinute = (int) $logs->time_in->format('i');
                    // Consider tardy if arrived after 8:00 AM
                    if ($timeInHour > 8 || ($timeInHour == 8 && $timeInMinute > 0)) {
                        $tardyDays++;
                    }
                }
            }
            
            $amData = $this->extractAMPMTimes($detail, $ov, $currentDate);
            
            if ($amData['undertime_hours'] !== '' && $amData['undertime_minutes'] !== '') {
                $totalUndertimeMinutes += ($amData['undertime_hours'] * 60) + (int)$amData['undertime_minutes'];
            }
            
            $currentDate->addDay();
        }
        
        return [
            'hours' => floor($totalUndertimeMinutes / 60),
            'minutes' => str_pad($totalUndertimeMinutes % 60, 2, '0', STR_PAD_LEFT),
            'leave_days' => $leaveDays,
            'tardy_days' => $tardyDays,
            'has_undertime' => $totalUndertimeMinutes > 0
        ];
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
