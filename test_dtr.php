<?php

// Simple test script to debug DTR generation
require_once 'vendor/autoload.php';

use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\DTRReport;
use App\Models\DTRReportDetail;
use App\Models\DTRReportSummary;
use Carbon\Carbon;

try {
    echo "Testing DTR Generation...\n";
    
    // Test 1: Check if employees exist
    $employees = Employee::all();
    echo "Found " . $employees->count() . " employees\n";
    
    // Test 2: Check if attendance logs exist
    $logs = AttendanceLog::all();
    echo "Found " . $logs->count() . " attendance logs\n";
    
    // Test 3: Check if DTR tables exist
    try {
        $reports = DTRReport::all();
        echo "Found " . $reports->count() . " DTR reports\n";
    } catch (Exception $e) {
        echo "DTR reports table error: " . $e->getMessage() . "\n";
    }
    
    // Test 4: Check database connection
    try {
        \DB::connection()->getPdo();
        echo "Database connection: OK\n";
    } catch (Exception $e) {
        echo "Database connection error: " . $e->getMessage() . "\n";
    }
    
    // Test 5: Check table structure
    try {
        $columns = \DB::select("DESCRIBE dtr_report_details");
        echo "DTR details table columns:\n";
        foreach ($columns as $column) {
            echo "  - " . $column->Field . " (" . $column->Type . ")\n";
        }
    } catch (Exception $e) {
        echo "Table structure error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
