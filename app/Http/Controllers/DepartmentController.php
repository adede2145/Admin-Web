<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct()
    {
        // Ensure only super admins can access department management
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || !auth()->user()->role || auth()->user()->role->role_name !== 'super_admin') {
                abort(403, 'Access denied. Only Super Admins can manage departments.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $departments = Department::withCount('employees')->get();
        return view('admin.departments.index', compact('departments'));
    }

    public function create()
    {
        return view('admin.departments.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_name' => 'required|string|max:100|unique:departments,department_name',
        ]);

        Department::create($validated);

        return redirect()->route('departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function edit(Department $department)
    {
        return view('admin.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        try {
            $validated = $request->validate([
                'department_name' => 'required|string|max:100|unique:departments,department_name,' . $department->department_id . ',department_id',
            ], [
                'department_name.required' => 'Department name is required.',
                'department_name.string' => 'Department name must be a valid text.',
                'department_name.max' => 'Department name cannot exceed 100 characters.',
                'department_name.unique' => 'A department with this name already exists.',
            ]);

            $department->update($validated);

            return redirect()->route('departments.index')
                ->with('success', 'Department "' . $validated['department_name'] . '" updated successfully!');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('departments.index')
                ->withErrors($e->validator)
                ->withInput()
                ->with('error', 'Failed to update department. Please check the form for errors.');
        } catch (\Exception $e) {
            \Log::error('Department update failed', [
                'department_id' => $department->department_id,
                'error' => $e->getMessage(),
                'user_id' => auth()->user()->admin_id
            ]);
            
            return redirect()->route('departments.index')
                ->with('error', 'An unexpected error occurred while updating the department.');
        }
    }

    public function destroy(Department $department)
    {
        try {
            $departmentName = $department->department_name;
            $employeeCount = $department->employees()->count();
            
            if ($employeeCount > 0) {
                return redirect()->route('departments.index')
                    ->with('error', 'Cannot delete "' . $departmentName . '" because it has ' . $employeeCount . ' employee(s). Please reassign or remove employees first.');
            }

            $department->delete();

            return redirect()->route('departments.index')
                ->with('success', 'Department "' . $departmentName . '" deleted successfully!');
                
        } catch (\Exception $e) {
            \Log::error('Department deletion failed', [
                'department_id' => $department->department_id,
                'error' => $e->getMessage(),
                'user_id' => auth()->user()->admin_id
            ]);
            
            return redirect()->route('departments.index')
                ->with('error', 'An unexpected error occurred while deleting the department.');
        }
    }
}
