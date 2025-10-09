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
        // Check if the column already exists first
        if (!Schema::hasColumn('admins', 'employee_id')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->unsignedInteger('employee_id')->nullable()->after('admin_id');
            });
        }
        
        // Check if foreign key already exists
        $foreignKeyExists = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'admins' 
            AND CONSTRAINT_NAME = 'admins_employee_id_foreign'
        ");
        
        if ($foreignKeyExists[0]->count == 0) {
            Schema::table('admins', function (Blueprint $table) {
                $table->foreign('employee_id')->references('employee_id')->on('employees')->onDelete('cascade');
            });
        }
        
        // Check if unique key already exists
        $uniqueKeyExists = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'admins' 
            AND COLUMN_NAME = 'employee_id'
            AND CONSTRAINT_NAME LIKE '%unique%'
        ");
        
        if ($uniqueKeyExists[0]->count == 0) {
            Schema::table('admins', function (Blueprint $table) {
                $table->unique('employee_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropUnique(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }
};
