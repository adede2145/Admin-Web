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
        // Since you have one existing admin, we'll create an employee for them
        
        $existingAdmin = Admin::first();
        
        if ($existingAdmin && !$existingAdmin->employee_id) {
            // Create employee record for the existing admin
            $employee = Employee::create([
                'full_name' => $existingAdmin->username, // You can update this manually later
                'employment_type' => 'full_time', // Default to full time
                'department_id' => $existingAdmin->department_id
            ]);
            
            // Link admin to employee
            $existingAdmin->update(['employee_id' => $employee->employee_id]);
            
            echo "Created employee record for admin: " . $existingAdmin->username . "\n";
            echo "Employee ID: " . $employee->employee_id . "\n";
            echo "Please update the employee's full_name manually if needed.\n";
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove employee_id from existing admin
        Admin::whereNotNull('employee_id')->update(['employee_id' => null]);
        
        // Optionally remove created employee records
        // Be careful with this in production!
    }
};
