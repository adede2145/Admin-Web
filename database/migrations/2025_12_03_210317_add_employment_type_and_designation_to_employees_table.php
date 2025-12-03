<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extend employment_type enum values to keep old ones and add new ones
        // Values: full_time, part_time, cos, admin, faculty with designation
        DB::statement("
            ALTER TABLE employees
            MODIFY COLUMN employment_type
            ENUM('full_time', 'part_time', 'cos', 'admin', 'faculty with designation')
            NULL DEFAULT NULL
        ");
    }

    public function down(): void
    {
        // Revert employment_type enum to original values (remove admin and faculty with designation)
        DB::statement("
            ALTER TABLE employees
            MODIFY COLUMN employment_type
            ENUM('full_time', 'part_time', 'cos')
            NULL DEFAULT NULL
        ");
    }
};