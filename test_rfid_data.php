<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Models\AttendanceLog;
use App\Models\Employee;
use Carbon\Carbon;

echo "Creating sample RFID attendance records with verification data...\n";

// Get first employee
$employee = Employee::first();

if (!$employee) {
    echo "No employees found. Please create some employees first.\n";
    exit;
}

// Create sample RFID records
$records = [
    [
        'employee_id' => $employee->employee_id,
        'time_in' => Carbon::now()->subHours(2),
        'time_out' => null,
        'method' => 'rfid',
        'kiosk_id' => 1,
        'rfid_reason' => 'Card malfunction - used backup RFID card',
        'verification_status' => 'pending'
    ],
    [
        'employee_id' => $employee->employee_id,
        'time_in' => Carbon::now()->subHours(4),
        'time_out' => Carbon::now()->subHours(1),
        'method' => 'rfid',
        'kiosk_id' => 1,
        'rfid_reason' => 'Fingerprint scanner not working',
        'verification_status' => 'pending'
    ],
    [
        'employee_id' => $employee->employee_id,
        'time_in' => Carbon::yesterday()->addHours(8),
        'time_out' => Carbon::yesterday()->addHours(17),
        'method' => 'rfid',
        'kiosk_id' => 1,
        'rfid_reason' => 'Temporary RFID access due to injured finger',
        'verification_status' => 'verified',
        'verified_by' => 1,
        'verified_at' => Carbon::yesterday()->addHours(18),
        'is_verified' => true
    ]
];

foreach ($records as $recordData) {
    $log = AttendanceLog::create($recordData);
    echo "Created RFID record #{$log->log_id} for {$employee->full_name} - Status: {$recordData['verification_status']}\n";
}

echo "Sample RFID records created successfully!\n";
echo "You can now test the RFID verification functionality.\n";
