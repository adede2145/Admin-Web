<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // Show all admins
    public function index()
    {
        // Fetch all admins with employee and role relationships
        $admins = Admin::with(['role', 'employee.department'])->get();

        // Fetch all roles for the dropdown (kept for stats / future use)
        $roles = Role::all();

        // Restrict departments to HR / Office HR for admin creation (case-insensitive)
        $departments = Department::whereRaw('LOWER(department_name) IN (?, ?)', ['hr', 'office hr'])->get();

        // Get available employees (not already admins) under HR / Office HR only
        $availableEmployees = Employee::whereDoesntHave('admin')
            ->whereIn('department_id', $departments->pluck('department_id'))
            ->with('department')
            ->orderBy('full_name')
            ->get();

        return view('admin.panel', compact('admins', 'roles', 'departments', 'availableEmployees'));
    }

    // API endpoint to get employees by department
    public function getEmployeesByDepartment(Request $request)
    {
        $departmentId = $request->get('department_id');
        
        $query = Employee::whereDoesntHave('admin')
            ->with('department')
            ->orderBy('full_name');

        if ($departmentId && $departmentId !== 'all') {
            $query->where('department_id', $departmentId);
        }

        $employees = $query->get();

        return response()->json([
            'employees' => $employees->map(function ($employee) {
                return [
                    'employee_id' => $employee->employee_id,
                    'full_name' => $employee->full_name,
                    'department' => $employee->department->department_name ?? 'N/A',
                    'employment_type' => ucfirst(str_replace('_', ' ', $employee->employment_type))
                ];
            })
        ]);
    }

    // Create new admin
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:admins,username',
            // Password: min 8, starts with capital letter, includes at least one symbol
            'password' => ['required','min:8','regex:/^[A-Z].*$/','regex:/[^A-Za-z0-9]/'],
            'employee_id' => 'required|exists:employees,employee_id|unique:admins,employee_id',
            'employment_type_access' => 'required|array|min:1',
            'employment_type_access.*' => 'in:full_time,part_time,cos',
        ], [
            'password.min' => 'Password must be at least 8 characters.',
            'password.regex' => 'Password must start with a capital letter and include at least one symbol.',
            'employee_id.unique' => 'This employee is already an admin.',
            'employment_type_access.required' => 'Select at least one employment type for this admin.',
        ]);

        try {
            DB::beginTransaction();

            // Find role_id for "admin"
            $adminRole = Role::where('role_name', 'admin')->first();

            if (!$adminRole) {
                return redirect()->back()->with('error', 'Admin role not found. Please insert it in roles table.');
            }

            // Get employee to inherit department
            $employee = Employee::with('department')->find($request->employee_id);

            // Only HR / Office HR employees can be created as admins (case-insensitive)
            $deptName = $employee->department ? $employee->department->department_name : null;
            $normalizedDept = $deptName ? mb_strtolower($deptName) : null;
            if (!in_array($normalizedDept, ['hr', 'office hr'], true)) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Only employees under HR / Office HR can be added as admins.');
            }

            Admin::create([
                'username' => $request->username,
                'password_hash' => DB::raw("SHA2('" . $request->password . "', 256)"),
                'role_id' => $adminRole->role_id,
                'department_id' => $employee->department_id, // Inherit from employee
                'employee_id' => $request->employee_id,
                'employment_type_access' => $request->employment_type_access,
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'New admin created successfully for ' . $employee->full_name . '!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to create admin: ' . $e->getMessage());
        }
    }

    // Delete admin
    public function destroy($id)
    {
        $admin = Admin::findOrFail($id);

        // Prevent deleting superadmin accidentally
        if ($admin->role && $admin->role->role_name === 'super_admin') {
            return redirect()->back()->with('error', 'You cannot delete a superadmin.');
        }

        // Don't delete the employee, just the admin access
        $employeeName = $admin->employee ? $admin->employee->full_name : $admin->username;

        $admin->delete();
        
        return redirect()->back()->with('success', "Admin access removed for {$employeeName}. Employee record remains intact.");
    }

    // Update existing admin (username, password)
    public function update(Request $request, $id)
    {
        $admin = Admin::findOrFail($id);

        $request->validate([
            'username' => 'required|unique:admins,username,' . $admin->admin_id . ',admin_id',
            'password' => 'nullable|min:6',
        ]);

        $admin->username = $request->input('username');

        if ($request->filled('password')) {
            $admin->password_hash = DB::raw("SHA2('" . $request->password . "', 256)");
        }

        $admin->save();

        return redirect()->back()->with('success', 'Admin updated successfully.');
    }

    // API endpoint for admin creation stats (for line graph)
    public function creationStats(Request $request)
    {
        // If created_at does not exist, replace with another date field
        $stats = \App\Models\Admin::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->whereRaw('created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)')
            ->get();
        return response()->json($stats);
    }
}
