<?php

// Debug script to check DTR generation issues
require_once 'vendor/autoload.php';

use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\DTRReport;
use Carbon\Carbon;

try {
    echo "=== DTR Generation Debug Report ===\n\n";
    
    // Test 1: Check database connection
    try {
        \DB::connection()->getPdo();
        echo "✅ Database connection: OK\n";
    } catch (Exception $e) {
        echo "❌ Database connection error: " . $e->getMessage() . "\n";
        exit;
    }
    
    // Test 2: Check if employees exist
    $employees = Employee::all();
    echo "✅ Found " . $employees->count() . " employees\n";
    
    // Test 3: Check if attendance logs exist
    $logs = AttendanceLog::all();
    echo "✅ Found " . $logs->count() . " attendance logs\n";
    
    // Test 4: Check date range of attendance data
    $earliestLog = AttendanceLog::orderBy('time_in', 'asc')->first();
    $latestLog = AttendanceLog::orderBy('time_in', 'desc')->first();
    
    if ($earliestLog && $latestLog) {
        echo "📅 Attendance data date range:\n";
        echo "   Earliest: " . $earliestLog->time_in->format('Y-m-d') . "\n";
        echo "   Latest: " . $latestLog->time_in->format('Y-m-d') . "\n";
    }
    
    // Test 5: Check specific dates that have data
    echo "\n📊 Attendance data by date:\n";
    $dateCounts = AttendanceLog::selectRaw('DATE(time_in) as date, COUNT(*) as count')
        ->groupBy('date')
        ->orderBy('date')
        ->get();
    
    foreach ($dateCounts as $dateCount) {
        echo "   {$dateCount->date}: {$dateCount->count} records\n";
    }
    
    // Test 6: Check if DTR tables exist
    try {
        $reports = DTRReport::all();
        echo "\n✅ Found " . $reports->count() . " existing DTR reports\n";
    } catch (Exception $e) {
        echo "\n❌ DTR reports table error: " . $e->getMessage() . "\n";
    }
    
    // Test 7: Test specific date range (2024-01-10 to 2024-01-15)
    echo "\n🧪 Testing DTR generation for 2024-01-10 to 2024-01-15:\n";
    
    $testStartDate = '2024-01-10';
    $testEndDate = '2024-01-15';
    
    $testLogs = AttendanceLog::whereBetween('time_in', [
        Carbon::parse($testStartDate)->startOfDay(),
        Carbon::parse($testEndDate)->endOfDay()
    ])->get();
    
    echo "   Found " . $testLogs->count() . " attendance records in test range\n";
    
    if ($testLogs->count() > 0) {
        echo "   Sample records:\n";
        foreach ($testLogs->take(3) as $log) {
            echo "     - Employee {$log->employee_id}: {$log->time_in->format('Y-m-d H:i')} to " . 
                 ($log->time_out ? $log->time_out->format('H:i') : 'Not Set') . "\n";
        }
    }
    
    // Test 8: Check employees by department
    echo "\n👥 Employees by department:\n";
    $departments = \DB::table('departments')->get();
    foreach ($departments as $dept) {
        $empCount = Employee::where('department_id', $dept->department_id)->count();
        echo "   {$dept->department_name}: {$empCount} employees\n";
    }
    
    echo "\n=== Debug Complete ===\n";
    echo "\n💡 To generate DTR report, use these dates:\n";
    echo "   Start Date: 2024-01-10\n";
    echo "   End Date: 2024-01-15\n";
    echo "   This will include all available attendance data.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
