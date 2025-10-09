<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Admin;
use App\Models\Employee;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // This migration helps link existing admin to an employee record
        // Skip super admins - they don't need to be linked to employees

        $existingAdmins = Admin::with('role')->get();

        foreach ($existingAdmins as $admin) {
            // Skip super admins - they should not be linked to employees
            if ($admin->role && $admin->role->role_name === 'super_admin') {
                echo "Skipping super admin: " . $admin->username . " (Super admins don't need employee records)\n";
                continue;
            }

            // Only process regular admins that don't have employee_id yet
            if (!$admin->employee_id) {
                // Create employee record for the regular admin
                $employee = Employee::create([
                    'full_name' => $admin->username, // You can update this manually later
                    'employment_type' => 'full_time', // Default to full time
                    'department_id' => $admin->department_id
                ]);

                // Link admin to employee
                $admin->update(['employee_id' => $employee->employee_id]);

                echo "Created employee record for admin: " . $admin->username . "\n";
                echo "Employee ID: " . $employee->employee_id . "\n";
                echo "Please update the employee's full_name manually if needed.\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove employee_id from existing admins (except super admins)
        Admin::with('role')
            ->whereNotNull('employee_id')
            ->whereHas('role', function ($query) {
                $query->where('role_name', '!=', 'super_admin');
            })
            ->update(['employee_id' => null]);

        // Optionally remove created employee records
        // Be careful with this in production!
        echo "Removed employee links from regular admins (super admins were not affected)\n";
    }
};
