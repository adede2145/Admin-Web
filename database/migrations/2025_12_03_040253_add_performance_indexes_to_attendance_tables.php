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
     * Purpose: Add missing indexes to dramatically improve query performance
     * Expected Impact: 25-40% faster fingerprint scans (1600ms → 1000ms)
     * 
     * FIXED: Added prefix length for TEXT/BLOB columns (rfid_reason)
     */
    public function up(): void
    {
        // ============================================================================
        // CRITICAL INDEXES FOR attendance_logs
        // ============================================================================
        
        // Index 1: Employee + Time-Out + Time-In (MOST CRITICAL)
        if (!$this->indexExists('attendance_logs', 'idx_attendance_employee_timeout_date')) {
            Schema::table('attendance_logs', function (Blueprint $table) {
                $table->index(['employee_id', 'time_out', 'time_in'], 'idx_attendance_employee_timeout_date');
            });
        }
        
        // Index 2: Method column for reporting and filtering
        if (!$this->indexExists('attendance_logs', 'idx_attendance_method')) {
            Schema::table('attendance_logs', function (Blueprint $table) {
                $table->index('method', 'idx_attendance_method');
            });
        }
        
        // Index 3: Kiosk + Date for kiosk-specific queries
        if (!$this->indexExists('attendance_logs', 'idx_attendance_kiosk_date')) {
            Schema::table('attendance_logs', function (Blueprint $table) {
                $table->index(['kiosk_id', 'time_in'], 'idx_attendance_kiosk_date');
            });
        }
        
        // Index 4: RFID reason - Use raw SQL with prefix length for TEXT column
        if (!$this->indexExists('attendance_logs', 'idx_attendance_rfid_reason')) {
            DB::statement('CREATE INDEX idx_attendance_rfid_reason ON attendance_logs (rfid_reason(100))');
        }
        
        // ============================================================================
        // ADDITIONAL INDEXES FOR employee_fingerprint_templates
        // ============================================================================
        
        if (Schema::hasTable('employee_fingerprint_templates')) {
            // Index for employee lookups
            if (!$this->indexExists('employee_fingerprint_templates', 'idx_fp_templates_employee')) {
                Schema::table('employee_fingerprint_templates', function (Blueprint $table) {
                    $table->index('employee_id', 'idx_fp_templates_employee');
                });
            }
            
            // Index for template quality filtering
            if (!$this->indexExists('employee_fingerprint_templates', 'idx_fp_templates_quality')) {
                Schema::table('employee_fingerprint_templates', function (Blueprint $table) {
                    $table->index('template_quality', 'idx_fp_templates_quality');
                });
            }
        }
        
        // ============================================================================
        // ANALYZE TABLES TO UPDATE STATISTICS
        // ============================================================================
        
        DB::statement('ANALYZE TABLE attendance_logs');
        DB::statement('ANALYZE TABLE employees');
        
        if (Schema::hasTable('employee_fingerprint_templates')) {
            DB::statement('ANALYZE TABLE employee_fingerprint_templates');
        }
        
        if (Schema::hasTable('departments')) {
            DB::statement('ANALYZE TABLE departments');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropIndex('idx_attendance_employee_timeout_date');
            $table->dropIndex('idx_attendance_method');
            $table->dropIndex('idx_attendance_kiosk_date');
            $table->dropIndex('idx_attendance_rfid_reason');
        });
        
        if (Schema::hasTable('employee_fingerprint_templates')) {
            Schema::table('employee_fingerprint_templates', function (Blueprint $table) {
                if ($this->indexExists('employee_fingerprint_templates', 'idx_fp_templates_employee')) {
                    $table->dropIndex('idx_fp_templates_employee');
                }
                if ($this->indexExists('employee_fingerprint_templates', 'idx_fp_templates_quality')) {
                    $table->dropIndex('idx_fp_templates_quality');
                }
            });
        }
    }
    
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return !empty($indexes);
    }
};
