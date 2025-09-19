<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // Show all admins
    public function index()
    {
        // Fetch all admins
        $admins = Admin::with('role')->get();

        // Fetch all roles for the dropdown
        $roles = Role::all();

        // Fetch all departments for the dropdown
        $departments = \App\Models\Department::all();

        return view('admin.panel', compact('admins', 'roles', 'departments'));
    }

    // Create new admin
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:admins,username',
            // Password: min 8, starts with capital letter, includes at least one symbol
            'password' => ['required','min:8','regex:/^[A-Z].*$/','regex:/[^A-Za-z0-9]/'],
            'department_id' => 'required|exists:departments,department_id',
        ], [
            'password.min' => 'Password must be at least 8 characters.',
            'password.regex' => 'Password must start with a capital letter and include at least one symbol.',
        ]);

        // Find role_id for "admin"
        $adminRole = Role::where('role_name', 'admin')->first();

        if (!$adminRole) {
            return redirect()->back()->with('error', 'Admin role not found. Please insert it in roles table.');
        }

        Admin::create([
            'username' => $request->username,
            'password_hash' => DB::raw("SHA2('" . $request->password . "', 256)"),
            'role_id' => $adminRole->role_id,
            'department_id' => $request->department_id, // Assign to selected department
        ]);

        return redirect()->back()->with('success', 'New admin created successfully!');
    }

    // Delete admin
    public function destroy($id)
    {
        $admin = Admin::findOrFail($id);

        // Prevent deleting superadmin accidentally
        if ($admin->role && $admin->role->role_name === 'super_admin') {
            return redirect()->back()->with('error', 'You cannot delete a superadmin.');
        }

        $admin->delete();
        return redirect()->back()->with('success', 'Admin deleted successfully!');
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
