<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct()
    {
        // Ensure only super admins can access office management
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || !auth()->user()->role || auth()->user()->role->role_name !== 'super_admin') {
                abort(403, 'Access denied. Only Super Admins can manage offices.');
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
        ], [
            'department_name.required' => 'Office name is required.',
            'department_name.string' => 'Office name must be a valid text.',
            'department_name.max' => 'Office name cannot exceed 100 characters.',
            'department_name.unique' => 'An office with this name already exists.',
        ]);

        Department::create($validated);

        return redirect()->route('departments.index')
            ->with('success', 'Office created successfully.');
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
                'department_name.required' => 'Office name is required.',
                'department_name.string' => 'Office name must be a valid text.',
                'department_name.max' => 'Office name cannot exceed 100 characters.',
                'department_name.unique' => 'An office with this name already exists.',
            ]);

            $department->update($validated);

            return redirect()->route('departments.index')
                ->with('success', 'Office "' . $validated['department_name'] . '" updated successfully!');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('departments.index')
                ->withErrors($e->validator)
                ->withInput()
                ->with('error', 'Failed to update office. Please check the form for errors.');
        } catch (\Exception $e) {
            \Log::error('Office update failed', [
                'department_id' => $department->department_id,
                'error' => $e->getMessage(),
                'user_id' => auth()->user()->admin_id
            ]);
            
            return redirect()->route('departments.index')
                ->with('error', 'An unexpected error occurred while updating the office.');
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
                ->with('success', 'Office "' . $departmentName . '" deleted successfully!');
                
        } catch (\Exception $e) {
            \Log::error('Office deletion failed', [
                'department_id' => $department->department_id,
                'error' => $e->getMessage(),
                'user_id' => auth()->user()->admin_id
            ]);
            
            return redirect()->route('departments.index')
                ->with('error', 'An unexpected error occurred while deleting the office.');
        }
    }
}
