<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TokenService;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegistrationTokenController extends Controller
{
    protected $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Generate a token for local registration page access
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateToken(Request $request)
    {
        // Use web session auth instead of Sanctum
        $admin = auth()->user();

        // Verify user is authenticated and has proper role
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please log in first.'
            ], 401);
        }

        if (!$admin->role || !in_array($admin->role->role_name, ['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admins can generate registration tokens.'
            ], 403);
        }

        try {
            // Generate token with same expiry as session (120 minutes default)
            $sessionLifetime = config('session.lifetime', 120); // Gets from config/session.php
            $token = $this->tokenService->generateRegistrationToken($admin, $sessionLifetime);

            return response()->json([
                'success' => true,
                'token' => $token,
                'expires_in' => $sessionLifetime, // minutes
                'message' => 'Token generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate a registration token
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateToken(Request $request)
    {
        $token = $request->input('token') ?? $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'No token provided'
            ], 400);
        }

        $payload = $this->tokenService->validateRegistrationToken($token);

        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        }

        // Return admin details
        return response()->json([
            'success' => true,
            'admin' => [
                'admin_id' => $payload['admin_id'],
                'name' => $payload['name'],
                'email' => $payload['email'],
                'role' => $payload['role'],
                'office_id' => $payload['office_id'],
            ],
            'message' => 'Token is valid'
        ]);
    }

    /**
     * Get offices based on token (RBAC enforced)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOffices(Request $request)
    {
        $token = $request->input('token') ?? $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'No token provided'
            ], 400);
        }

        $payload = $this->tokenService->validateRegistrationToken($token);

        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        }

        try {
            $role = $payload['role'];
            $officeId = $payload['office_id'];

            // Super admin sees all offices
            if ($role === 'super_admin') {
                $offices = Department::select('department_id as id', 'department_name as name')
                    ->orderBy('department_name')
                    ->get();
            } else {
                // Regular admin sees only their office
                $offices = Department::select('department_id as id', 'department_name as name')
                    ->where('department_id', $officeId)
                    ->get();
            }

            return response()->json([
                'success' => true,
                'offices' => $offices,
                'admin_role' => $role
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch offices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register a new employee via token authentication (for local registration page)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerEmployee(Request $request)
    {
        // Validate token
        $token = $request->input('token') ?? $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'No authentication token provided'
            ], 401);
        }

        $payload = $this->tokenService->validateRegistrationToken($token);

        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            // Validate request (same rules as StoreEmployeeRequest)
            $validatedData = $request->validate([
                'emp_name' => 'required|string|max:100',
                'emp_id' => 'required|string|max:50|unique:employees,employee_code',
                'department_id' => 'required|exists:departments,department_id',
                'employment_type' => 'required|in:full_time,part_time',
                'rfid_uid' => 'required|string|max:100',
                'primary_template' => 'required|string',
                'backup_template' => 'nullable|string',
                'profile_image' => 'nullable|image|max:5120',
            ]);

            // Prepare employee data (exactly like EmployeeController)
            $employeeData = [
                'employee_code' => $request->emp_id, // Custom employee ID/code
                'full_name' => $request->emp_name,
                'employment_type' => $request->employment_type,
                'rfid_code' => $request->rfid_uid,
                'department_id' => $request->department_id,
            ];

            // Handle profile image as BLOB (exactly like EmployeeController)
            if ($request->hasFile('profile_image')) {
                $file = $request->file('profile_image');
                $employeeData['photo_data'] = file_get_contents($file->getRealPath());
                $employeeData['photo_content_type'] = $file->getMimeType();
            }

            // Create employee record
            $employee = \App\Models\Employee::create($employeeData);

            // Store primary fingerprint template (exactly like EmployeeController)
            \App\Models\EmployeeFingerprintTemplate::create([
                'employee_id' => $employee->employee_id,
                'template_index' => 1,
                'template_data' => base64_decode($request->primary_template),
                'finger_position' => 'index',
                'template_quality' => 85.00
            ]);

            // Store backup fingerprint template if provided
            if ($request->backup_template) {
                \App\Models\EmployeeFingerprintTemplate::create([
                    'employee_id' => $employee->employee_id,
                    'template_index' => 2,
                    'template_data' => base64_decode($request->backup_template),
                    'finger_position' => 'thumb',
                    'template_quality' => 85.00
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();

            // Return success response (same format as EmployeeController)
            return response()->json([
                'success' => true,
                'message' => 'Employee registered successfully!',
                'employee' => [
                    'id' => $employee->employee_id,
                    'name' => $employee->full_name,
                    'department' => $employee->department->department_name ?? 'Unknown'
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollback();
            \Illuminate\Support\Facades\Log::error('Employee registration failed: ' . $e->getMessage(), [
                'employee_id' => $request->emp_id,
                'token_admin_id' => $payload['admin_id'],
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee data for editing (for local registration page)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEmployee(Request $request)
    {
        // Validate token
        $token = $request->input('token') ?? $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'No authentication token provided'
            ], 401);
        }

        $payload = $this->tokenService->validateRegistrationToken($token);

        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        }

        try {
            $employeeId = $request->input('employee_id');
            
            if (!$employeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee ID is required'
                ], 400);
            }

            $employee = \App\Models\Employee::with('department')
                ->where('employee_id', $employeeId)
                ->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            // Check RBAC: admin can only see employees from their department
            if ($payload['role'] !== 'super_admin' && $employee->department_id !== $payload['office_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You can only view employees from your department'
                ], 403);
            }

            // Get existing fingerprint templates
            $templates = \App\Models\EmployeeFingerprintTemplate::where('employee_id', $employee->employee_id)
                ->get()
                ->keyBy('template_index');

            // Prepare response
            return response()->json([
                'success' => true,
                'employee' => [
                    'employee_id' => $employee->employee_id,
                    'employee_code' => $employee->employee_code,
                    'full_name' => $employee->full_name,
                    'employment_type' => $employee->employment_type,
                    'rfid_code' => $employee->rfid_code,
                    'department_id' => $employee->department_id,
                    'department_name' => $employee->department->department_name ?? 'Unknown',
                    'has_primary_fingerprint' => isset($templates[1]),
                    'has_backup_fingerprint' => isset($templates[2]),
                    'photo_url' => route('employees.photo', $employee->employee_id),
                ]
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Get employee failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch employee: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update employee fingerprints (for local registration page)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateFingerprints(Request $request)
    {
        // Validate token
        $token = $request->input('token') ?? $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'No authentication token provided'
            ], 401);
        }

        $payload = $this->tokenService->validateRegistrationToken($token);

        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            // Validate request
            $validatedData = $request->validate([
                'employee_id' => 'required|exists:employees,employee_id',
                'primary_template' => 'nullable|string',
                'backup_template' => 'nullable|string',
                'replace_primary' => 'nullable|boolean',
                'replace_backup' => 'nullable|boolean',
            ]);

            $employeeId = $request->employee_id;
            
            $employee = \App\Models\Employee::where('employee_id', $employeeId)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            // Check RBAC: admin can only edit employees from their department
            if ($payload['role'] !== 'super_admin' && $employee->department_id !== $payload['office_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You can only edit employees from your department'
                ], 403);
            }

            $updated = false;

            // Update primary fingerprint if requested
            if ($request->replace_primary && $request->primary_template) {
                \App\Models\EmployeeFingerprintTemplate::updateOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'template_index' => 1,
                    ],
                    [
                        'template_data' => base64_decode($request->primary_template),
                        'finger_position' => 'index',
                        'template_quality' => 85.00
                    ]
                );
                $updated = true;
            }

            // Update backup fingerprint if requested
            if ($request->replace_backup && $request->backup_template) {
                \App\Models\EmployeeFingerprintTemplate::updateOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'template_index' => 2,
                    ],
                    [
                        'template_data' => base64_decode($request->backup_template),
                        'finger_position' => 'thumb',
                        'template_quality' => 85.00
                    ]
                );
                $updated = true;
            }

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'No fingerprints were updated. Please select at least one fingerprint to replace.'
                ], 400);
            }

            \Illuminate\Support\Facades\DB::commit();

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Fingerprints updated successfully!',
                'employee' => [
                    'id' => $employee->employee_id,
                    'name' => $employee->full_name,
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollback();
            \Illuminate\Support\Facades\Log::error('Fingerprint update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
