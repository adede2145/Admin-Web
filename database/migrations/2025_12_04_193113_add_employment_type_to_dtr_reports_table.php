<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dtr_reports', function (Blueprint $table) {
            // Add employment_type column after department_id
            DB::statement("
                ALTER TABLE dtr_reports
                ADD COLUMN employment_type
                ENUM('full_time', 'part_time', 'cos', 'admin', 'faculty with designation')
                NULL DEFAULT NULL
                AFTER department_id
            ");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtr_reports', function (Blueprint $table) {
            $table->dropColumn('employment_type');
        });
    }
};
