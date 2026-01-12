<?php

namespace App\Http\Controllers;

use App\Models\EmploymentType;
use Illuminate\Http\Request;

class EmploymentTypeController extends Controller
{
    /**
     * Display a listing of employment types
     */
    public function index()
    {
        $employmentTypes = EmploymentType::orderBy('display_name')->paginate(15);
        return view('admin.employment-types.index', compact('employmentTypes'));
    }

    /**
     * Show the form for creating a new employment type
     */
    public function create()
    {
        return view('admin.employment-types.create');
    }

    /**
     * Store a newly created employment type
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type_name' => 'required|string|unique:employment_types,type_name|max:100',
            'display_name' => 'required|string|max:100',
        ]);

        $validated['is_active'] = true; // Always active on creation
        $validated['is_default'] = false; // Only system can set default

        EmploymentType::create($validated);

        return redirect()->route('employment-types.index')
            ->with('success', 'Employment type created successfully.');
    }

    /**
     * Show the form for editing an employment type
     */
    public function edit(EmploymentType $employmentType)
    {
        return view('admin.employment-types.edit', compact('employmentType'));
    }

    /**
     * Update the specified employment type
     */
    public function update(Request $request, EmploymentType $employmentType)
    {
        // Default types can only have display name updated
        if ($employmentType->is_default) {
            $validated = $request->validate([
                'display_name' => 'required|string|max:100',
            ]);
        } else {
            $validated = $request->validate([
                'type_name' => 'required|string|unique:employment_types,type_name,' . $employmentType->id . '|max:100',
                'display_name' => 'required|string|max:100',
            ]);
        }

        $employmentType->update($validated);

        return redirect()->route('employment-types.index')
            ->with('success', 'Employment type updated successfully.');
    }

    /**
     * Delete the specified employment type
     */
    public function destroy(EmploymentType $employmentType)
    {
        // Prevent deletion of default types
        if ($employmentType->is_default) {
            return redirect()->route('employment-types.index')
                ->with('error', 'Cannot delete default employment types.');
        }

        // Check if type is in use
        if ($employmentType->employees()->count() > 0) {
            return redirect()->route('employment-types.index')
                ->with('error', 'Cannot delete employment type that is in use by employees.');
        }

        $employmentType->delete();

        return redirect()->route('employment-types.index')
            ->with('success', 'Employment type deleted successfully.');
    }
}
