<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\DTRDetailOverride;
use App\Models\DTRHeadOfficerOverride;
use App\Models\DTRReport;
use App\Models\Employee;
use App\Models\DepartmentHead;

class DTROverrideController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'department.admin']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_id' => 'required|exists:dtr_reports,report_id',
            'employee_id' => 'required|exists:employees,employee_id',
            'date' => 'required|date',
            'remarks' => 'nullable|string|max:255',
        ]);

        $report = DTRReport::findOrFail($validated['report_id']);
        $employee = Employee::findOrFail($validated['employee_id']);

        // RBAC: ensure admin can only override within their department (unless super admin)
        if (auth()->user()->role->role_name !== 'super_admin' && auth()->user()->department_id != $employee->department_id) {
            abort(403, 'You can only edit your department.');
        }

        DTRDetailOverride::updateOrCreate(
            [
                'report_id' => $validated['report_id'],
                'employee_id' => $validated['employee_id'],
                'date' => $validated['date'],
            ],
            [
                'status_override' => 'leave',
                'remarks' => $validated['remarks'] ?? null,
            ]
        );

        return back()->with('success', 'Day marked as leave for this DTR report.');
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'report_id' => 'required|exists:dtr_reports,report_id',
            'employee_id' => 'required|exists:employees,employee_id',
            'date' => 'required|date',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        if (auth()->user()->role->role_name !== 'super_admin' && auth()->user()->department_id != $employee->department_id) {
            abort(403, 'You can only edit your department.');
        }

        DTRDetailOverride::where('report_id', $validated['report_id'])
            ->where('employee_id', $validated['employee_id'])
            ->whereDate('date', $validated['date'])
            ->delete();

        return back()->with('success', 'Leave override removed.');
    }

    /**
     * Store or update head officer name and office for an employee in a DTR report
     * Now accepts head_officer_id from dropdown instead of manual input
     */
    public function storeHeadOfficer(Request $request)
    {
        try {
            // First, check if head_officer_id is provided and not empty
            if (!$request->has('head_officer_id') || empty($request->input('head_officer_id'))) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please select a head officer.'
                    ], 422);
                }
                return back()
                    ->withInput($request->only(['report_id', 'employee_id']))
                    ->with('error', 'Please select a head officer.');
            }
            
            // Validate and normalize the input
            $validated = $request->validate([
                'report_id' => 'required|exists:dtr_reports,report_id',
                'employee_id' => 'required|exists:employees,employee_id',
                'head_officer_id' => 'required|integer|exists:department_heads,id',
            ], [
                'report_id.required' => 'Report ID is required.',
                'report_id.exists' => 'The selected report does not exist.',
                'employee_id.required' => 'Employee ID is required.',
                'employee_id.exists' => 'The selected employee does not exist.',
                'head_officer_id.required' => 'Please select a head officer.',
                'head_officer_id.integer' => 'Invalid head officer selection.',
                'head_officer_id.exists' => 'The selected head officer does not exist.',
            ]);
            
            // Ensure head_officer_id is an integer
            $validated['head_officer_id'] = (int) $validated['head_officer_id'];

            $report = DTRReport::findOrFail($validated['report_id']);
            $employee = Employee::findOrFail($validated['employee_id']);

            // RBAC: ensure admin can only override within their department (unless super admin)
            if (auth()->user()->role->role_name !== 'super_admin' && auth()->user()->department_id != $employee->department_id) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only edit your department.'
                    ], 403);
                }
                return back()->with('error', 'You can only edit your department.');
            }

            // Get the department head - check if it exists and is active
            $departmentHead = DepartmentHead::where('id', $validated['head_officer_id'])
                ->where('is_active', true)
                ->first();
            
            if (!$departmentHead) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The selected head officer does not exist or is inactive.'
                    ], 422);
                }
                return back()
                    ->withInput($request->only(['report_id', 'employee_id', 'head_officer_id']))
                    ->with('error', 'The selected head officer does not exist or is inactive.');
            }

            // Additional RBAC: Ensure the selected head officer belongs to the employee's department
            if ($departmentHead->department_id != $employee->department_id) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected head officer does not belong to the employee\'s department.'
                    ], 422);
                }
                return back()
                    ->withInput($request->only(['report_id', 'employee_id', 'head_officer_id']))
                    ->with('error', 'Selected head officer does not belong to the employee\'s department.');
            }

            // Use updateOrCreate to handle both new and existing overrides
            $override = DTRHeadOfficerOverride::updateOrCreate(
                [
                    'report_id' => $validated['report_id'],
                    'employee_id' => $validated['employee_id'],
                ],
                [
                    'head_officer_name' => $departmentHead->head_name,
                    'head_officer_office' => $departmentHead->head_title ?? null,
                ]
            );

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Head officer information saved successfully.',
                    'data' => [
                        'head_officer_name' => $override->head_officer_name,
                        'head_officer_office' => $override->head_officer_office,
                        'employee_id' => $validated['employee_id'],
                    ]
                ]);
            }

            return back()->with('success', 'Head officer information saved successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed. Please check your input.',
                    'errors' => $e->errors()
                ], 422);
            }
            return back()
                ->withErrors($e->validator)
                ->withInput($request->only(['report_id', 'employee_id', 'head_officer_id']))
                ->with('error', 'Validation failed. Please check your input.');
        } catch (\Exception $e) {
            Log::error('Error saving head officer override', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while saving the head officer: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'An error occurred while saving the head officer: ' . $e->getMessage());
        }
    }

    /**
     * Delete head officer override for an employee in a DTR report
     */
    public function destroyHeadOfficer(Request $request)
    {
        try {
            $validated = $request->validate([
                'report_id' => 'required|exists:dtr_reports,report_id',
                'employee_id' => 'required|exists:employees,employee_id',
            ]);

            $employee = Employee::findOrFail($validated['employee_id']);
            if (auth()->user()->role->role_name !== 'super_admin' && auth()->user()->department_id != $employee->department_id) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only edit your department.'
                    ], 403);
                }
                abort(403, 'You can only edit your department.');
            }

            DTRHeadOfficerOverride::where('report_id', $validated['report_id'])
                ->where('employee_id', $validated['employee_id'])
                ->delete();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Head officer information removed.',
                    'data' => [
                        'employee_id' => $validated['employee_id'],
                    ]
                ]);
            }

            return back()->with('success', 'Head officer information removed.');
        } catch (\Exception $e) {
            Log::error('Error deleting head officer override', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while removing the head officer: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'An error occurred while removing the head officer: ' . $e->getMessage());
        }
    }
}
