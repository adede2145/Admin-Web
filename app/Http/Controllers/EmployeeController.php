<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\EmployeeFingerprintTemplate;
use App\Http\Requests\StoreEmployeeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    // Show all employees
    public function index()
    {
        try {
            $query = Employee::with(['department', 'attendanceLogs']);

            // Department restriction for non-super admins
            if (auth()->user()->role->role_name !== 'super_admin' && auth()->user()->department_id) {
                $query->where('department_id', auth()->user()->department_id);
            }

            // Search filter
            if ($search = request('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%$search%")
                        ->orWhere('employee_id', $search)
                        ->orWhere('rfid_code', 'like', "%$search%");
                });
            }

            $employees = $query->paginate(11);

            // Ensure departments are available for the user's access level
            if (auth()->user()->role->role_name === 'super_admin') {
                $departments = Department::all();
            } else {
                $departments = Department::where('department_id', auth()->user()->department_id)->get();
            }

            // Determine which employee to show in the summary pane
            $firstEmployee = collect($employees->items())->first();
            $selectedEmployeeId = request('employee_id');

            if (!$selectedEmployeeId && request()->filled('search')) {
                $search = trim(request('search'));

                // Prefer exact ID match when the search looks numeric
                if (ctype_digit($search)) {
                    $candidate = Employee::query()
                        ->when(
                            auth()->user()->role->role_name !== 'super_admin' && auth()->user()->department_id,
                            function ($q) { $q->where('department_id', auth()->user()->department_id); }
                        )
                        ->where('employee_id', (int) $search)
                        ->first();
                    if ($candidate) {
                        $selectedEmployeeId = $candidate->employee_id;
                    }
                }

                // Fallback: prefer exact full name match (case-insensitive)
                if (!$selectedEmployeeId) {
                    $candidate = Employee::query()
                        ->when(
                            auth()->user()->role->role_name !== 'super_admin' && auth()->user()->department_id,
                            function ($q) { $q->where('department_id', auth()->user()->department_id); }
                        )
                        ->whereRaw('LOWER(full_name) = ?', [mb_strtolower($search)])
                        ->first();
                    if ($candidate) {
                        $selectedEmployeeId = $candidate->employee_id;
                    }
                }
            }

            // If still not determined, default to first employee in the (filtered) list
            if (!$selectedEmployeeId) {
                $selectedEmployeeId = optional($firstEmployee)->employee_id;
            }

            $selectedEmployee = $selectedEmployeeId ? Employee::with('department')->find($selectedEmployeeId) : null;

            // Verify user has access to the selected employee
            if (
                $selectedEmployee &&
                auth()->user()->role->role_name !== 'super_admin' &&
                auth()->user()->department_id !== $selectedEmployee->department_id
            ) {
                $selectedEmployee = null;
                $selectedEmployeeId = null;
            }

            $employeeStats = $this->calculateEmployeeStats($selectedEmployee);

            return view('employees.index', compact('employees', 'departments', 'selectedEmployee', 'employeeStats'));
        } catch (\Exception $e) {
            Log::error('Error in EmployeeController@index: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while loading employees. Please try again.');
        }
    }

    /**
     * Get employee summary data for AJAX requests
     */
    public function getSummary($id)
    {
        try {
            $employee = Employee::with('department')->findOrFail($id);

            // Check if user has access to this employee
            if (
                auth()->user()->role->role_name !== 'super_admin' &&
                auth()->user()->department_id !== $employee->department_id
            ) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            $stats = $this->calculateEmployeeStats($employee);

            return response()->json([
                'employee_id' => $employee->employee_id,
                'full_name' => $employee->full_name,
                'department' => $employee->department->department_name ?? 'N/A',
                'photo_url' => route('employees.photo', $employee->employee_id),
                'daysPresent' => $stats['daysPresent'],
                'lateArrivals' => $stats['lateArrivals'],
                'totalHours' => $stats['totalHours'],
                'overtimeHours' => $stats['overtimeHours'],
                'attendanceRate' => $stats['attendanceRate'],
                'lastLog' => $stats['lastLog']
            ]);
        } catch (\Exception $e) {
            Log::error('Error in EmployeeController@getSummary: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load employee data'], 500);
        }
    }

    /**
     * Calculate employee attendance statistics
     */
    private function calculateEmployeeStats($employee)
    {
        if (!$employee) {
            return [
                'daysPresent' => 0,
                'lateArrivals' => 0,
                'totalHours' => 0,
                'overtimeHours' => 0,
                'attendanceRate' => 0,
                'lastLog' => null,
            ];
        }

        $period = request('period', 'month');
        $today = \Carbon\Carbon::today();

        $start = match ($period) {
            'week' => $today->copy()->subDays(6),
            'quarter' => $today->copy()->subDays(89),
            default => $today->copy()->startOfMonth()
        };

        $end = $today->copy();

        $logs = $employee->attendanceLogs()
            ->whereBetween('time_in', [$start->startOfDay(), $end->endOfDay()])
            ->get();

        $daysPresent = $logs->groupBy(function ($log) {
            return \Carbon\Carbon::parse($log->time_in)->format('Y-m-d');
        })->count();

        $lateArrivals = $logs->where('time_in', '>', $start->format('Y-m-d') . ' 08:00:00')->count();

        $totalHours = 0;
        $overtimeHours = 0;

        foreach ($logs as $log) {
            $timeIn = \Carbon\Carbon::parse($log->time_in);
            $timeOut = $log->time_out ? \Carbon\Carbon::parse($log->time_out) : null;

            if ($timeOut) {
                $hours = $timeIn->diffInMinutes($timeOut) / 60;
                $totalHours += $hours;
                if ($hours > 8) {
                    $overtimeHours += ($hours - 8);
                }
            }
        }

        $totalDays = max($start->diffInDays($end) + 1, 1);
        $attendanceRate = $totalDays ? round(($daysPresent / $totalDays) * 100) : 0;
        $lastLog = $logs->sortByDesc('time_in')->first();

        return [
            'daysPresent' => $daysPresent,
            'lateArrivals' => $lateArrivals,
            'totalHours' => $totalHours,
            'overtimeHours' => $overtimeHours,
            'attendanceRate' => $attendanceRate,
            'lastLog' => $lastLog,
        ];
    }

    // Store new employee
    public function store(StoreEmployeeRequest $request)
    {
        DB::beginTransaction();

        try {
            // Prepare employee data
            $employeeData = [
                'employee_id' => $request->emp_id,
                'full_name' => $request->emp_name,
                'employment_type' => 'full_time', // Default, can be made configurable
                'rfid_code' => $request->rfid_uid,
                'department_id' => $request->department_id,
            ];

            // Handle profile image
            if ($request->hasFile('profile_image')) {
                $file = $request->file('profile_image');
                $employeeData['photo_data'] = file_get_contents($file->getRealPath());
                $employeeData['photo_content_type'] = $file->getMimeType();
            }

            // Create employee record
            $employee = Employee::create($employeeData);

            // Store primary fingerprint template
            EmployeeFingerprintTemplate::create([
                'employee_id' => $employee->employee_id,
                'template_index' => 1,
                'template_data' => base64_decode($request->primary_template),
                'finger_position' => 'index',
                'template_quality' => 85.00 // Default quality score
            ]);

            // Store backup fingerprint template if provided
            if ($request->backup_template) {
                EmployeeFingerprintTemplate::create([
                    'employee_id' => $employee->employee_id,
                    'template_index' => 2,
                    'template_data' => base64_decode($request->backup_template),
                    'finger_position' => 'thumb',
                    'template_quality' => 85.00 // Default quality score
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee registered successfully!',
                'employee' => [
                    'id' => $employee->employee_id,
                    'name' => $employee->full_name,
                    'department' => $employee->department->department_name ?? 'Unknown'
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Employee registration failed: ' . $e->getMessage(), [
                'employee_id' => $request->emp_id,
                'user_id' => auth()->id(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
    }

    // Update employee
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'full_name' => 'required|string|max:100',
                'employment_type' => 'required|in:full_time,part_time,cos',
                'department_id' => 'required|exists:departments,department_id',
                'photo' => 'nullable|image|max:5120',
            ]);

            $employee = Employee::findOrFail($id);

            // Check if user can edit this employee
            if (
                auth()->user()->role->role_name !== 'super_admin' &&
                auth()->user()->department_id !== $employee->department_id
            ) {
                abort(403, 'You can only edit employees from your department.');
            }

            // Department admins can only assign employees to their own department
            if (
                auth()->user()->role->role_name !== 'super_admin' &&
                $request->department_id != auth()->user()->department_id
            ) {
                abort(403, 'You can only assign employees to your department.');
            }

            $updateData = [
                'full_name' => $request->full_name,
                'employment_type' => $request->employment_type,
                'department_id' => $request->department_id,
            ];

            // Handle optional photo upload
            if ($request->hasFile('photo')) {
                try {
                    $file = $request->file('photo');
                    
                    // Validate file
                    if (!$file->isValid()) {
                        throw new \Exception('Invalid file upload');
                    }
                    
                    // Check file size (5MB max)
                    if ($file->getSize() > 5242880) {
                        throw new \Exception('File size exceeds 5MB limit');
                    }
                    
                    // Verify it's an image
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                    if (!in_array($file->getMimeType(), $allowedMimes)) {
                        throw new \Exception('File must be an image (JPEG, PNG, JPG, or GIF)');
                    }
                    
                    $updateData['photo_data'] = file_get_contents($file->getRealPath());
                    $updateData['photo_content_type'] = $file->getMimeType();
                    $updateData['photo_path'] = null;
                    
                    Log::info("Photo uploaded successfully for employee ID: {$id}", [
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType()
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error uploading employee photo', [
                        'employee_id' => $id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Return JSON for AJAX requests
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to upload photo: ' . $e->getMessage()
                        ], 400);
                    }
                    
                    return back()->with('error', 'Failed to upload photo: ' . $e->getMessage());
                }
            }

            $employee->update($updateData);
            
            Log::info("Employee updated successfully: ID {$id}");

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Employee updated successfully!'
                ]);
            }

            return back()->with('success', 'Employee updated successfully!');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error updating employee', [
                'employee_id' => $id,
                'errors' => $e->errors()
            ]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', array_map(fn($errors) => implode(', ', $errors), $e->errors()))
                ], 422);
            }
            
            throw $e;
            
        } catch (\Exception $e) {
            Log::error('Error updating employee', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update employee: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to update employee: ' . $e->getMessage());
        }
    }

    // Delete employee
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);

        // Check if user can delete this employee
        if (
            auth()->user()->role->role_name !== 'super_admin' &&
            auth()->user()->department_id !== $employee->department_id
        ) {
            abort(403, 'You can only delete employees from your department.');
        }

        $employee->delete();
        return back()->with('success', 'Employee deleted successfully!');
    }

    /**
     * Show fingerprint edit screen using the register page in edit mode
     */
    public function editFingerprints(int $id)
    {
        $employee = Employee::with('department')->findOrFail($id);

        // Authorization: admin or superadmin, and department restriction for admins
        if (!in_array(auth()->user()->role->role_name, ['admin', 'super_admin'])) {
            abort(403);
        }
        if (
            auth()->user()->role->role_name !== 'super_admin' &&
            auth()->user()->department_id !== $employee->department_id
        ) {
            abort(403, 'You can only edit employees from your department.');
        }

        // Load existing templates
        $templates = EmployeeFingerprintTemplate::where('employee_id', $employee->employee_id)
            ->get()
            ->keyBy('template_index');

        // Generate token for the session (expires in 60 minutes for edit mode)
        $tokenService = app(\App\Services\TokenService::class);
        $token = $tokenService->generateRegistrationToken(auth()->user(), 60);

        $backendUrl = url('/');

        // Redirect to register.html with employee data as query parameters
        $queryParams = http_build_query([
            'mode' => 'edit',
            'employee_id' => $employee->employee_id,
            'token' => $token,
            'backend_url' => $backendUrl,
        ]);

        return redirect('http://127.0.0.1:18426/register.html?' . $queryParams);
    }

    /**
     * Update employee fingerprint templates (primary/backup) only
     */
    public function updateFingerprints(Request $request, int $id)
    {
        $employee = Employee::findOrFail($id);

        // Authorization: admin or superadmin, and department restriction for admins
        if (!in_array(auth()->user()->role->role_name, ['admin', 'super_admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        if (
            auth()->user()->role->role_name !== 'super_admin' &&
            auth()->user()->department_id !== $employee->department_id
        ) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'replace_primary' => 'sometimes|boolean',
            'replace_backup' => 'sometimes|boolean',
            'primary_template' => 'nullable|string',
            'backup_template' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            if (!empty($data['replace_primary'])) {
                if (empty($data['primary_template'])) {
                    return response()->json(['success' => false, 'message' => 'Primary template required'], 422);
                }
                EmployeeFingerprintTemplate::updateOrCreate(
                    ['employee_id' => $employee->employee_id, 'template_index' => 1],
                    [
                        'template_data' => base64_decode($data['primary_template']),
                        'finger_position' => 'index',
                        'template_quality' => 85.0,
                    ]
                );
            }

            if (!empty($data['replace_backup'])) {
                if (empty($data['backup_template'])) {
                    return response()->json(['success' => false, 'message' => 'Backup template required'], 422);
                }
                EmployeeFingerprintTemplate::updateOrCreate(
                    ['employee_id' => $employee->employee_id, 'template_index' => 2],
                    [
                        'template_data' => base64_decode($data['backup_template']),
                        'finger_position' => 'thumb',
                        'template_quality' => 85.0,
                    ]
                );
            }

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Fingerprint update failed: ' . $e->getMessage(), ['employee_id' => $id]);
            return response()->json(['success' => false, 'message' => 'Update failed'], 500);
        }
    }

    // Serve employee photo from stored path (supports absolute kiosk paths)
    public function photo($id)
    {
        $employee = Employee::findOrFail($id);
        if ($employee->photo_data && $employee->photo_content_type) {
            return response($employee->photo_data, 200, [
                'Content-Type' => $employee->photo_content_type,
                'Cache-Control' => 'public, max-age=604800',
            ]);
        }
        // Fallback: transparent 1x1 PNG
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAqMBi0zJw+oAAAAASUVORK5CYII=');
        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
