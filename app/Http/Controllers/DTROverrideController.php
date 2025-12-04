<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DTRDetailOverride;
use App\Models\DTRHeadOfficerOverride;
use App\Models\DTRReport;
use App\Models\Employee;

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
     */
    public function storeHeadOfficer(Request $request)
    {
        $validated = $request->validate([
            'report_id' => 'required|exists:dtr_reports,report_id',
            'employee_id' => 'required|exists:employees,employee_id',
            'head_officer_name' => 'required|string|max:255',
            'head_officer_office' => 'nullable|string|max:255',
        ]);

        $report = DTRReport::findOrFail($validated['report_id']);
        $employee = Employee::findOrFail($validated['employee_id']);

        // RBAC: ensure admin can only override within their department (unless super admin)
        if (auth()->user()->role->role_name !== 'super_admin' && auth()->user()->department_id != $employee->department_id) {
            abort(403, 'You can only edit your department.');
        }

        DTRHeadOfficerOverride::updateOrCreate(
            [
                'report_id' => $validated['report_id'],
                'employee_id' => $validated['employee_id'],
            ],
            [
                'head_officer_name' => $validated['head_officer_name'],
                'head_officer_office' => $validated['head_officer_office'] ?? null,
            ]
        );

        return back()->with('success', 'Head officer information saved successfully.');
    }

    /**
     * Delete head officer override for an employee in a DTR report
     */
    public function destroyHeadOfficer(Request $request)
    {
        $validated = $request->validate([
            'report_id' => 'required|exists:dtr_reports,report_id',
            'employee_id' => 'required|exists:employees,employee_id',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        if (auth()->user()->role->role_name !== 'super_admin' && auth()->user()->department_id != $employee->department_id) {
            abort(403, 'You can only edit your department.');
        }

        DTRHeadOfficerOverride::where('report_id', $validated['report_id'])
            ->where('employee_id', $validated['employee_id'])
            ->delete();

        return back()->with('success', 'Head officer information removed.');
    }
}
