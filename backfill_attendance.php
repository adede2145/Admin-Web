<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel via artisan
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "Starting backfill for attendance_logs..." . PHP_EOL;

// 1. Insert the record
// SQL: INSERT INTO attendance_logs (employee_id, time_in, method, kiosk_id) VALUES (3, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Fingerprint', 1);

$employeeId = 3;
$timeIn = Carbon::now()->subDay();
$method = 'Fingerprint';
$kioskId = 1;

echo "Inserting record for Employee ID: $employeeId, Time In: $timeIn, Method: $method, Kiosk ID: $kioskId" . PHP_EOL;

$id = DB::table('attendance_logs')->insertGetId([
    'employee_id' => $employeeId,
    'time_in' => $timeIn,
    'method' => $method,
    'kiosk_id' => $kioskId,
    // Note: If created_at/updated_at are required, they should be added here. 
    // Based on the user's SQL, they are not included, so assuming database defaults or nullable.
]);

echo "✓ Inserted record with ID: $id" . PHP_EOL;

// 2. Verify the record was inserted and get its ID
// SQL: SELECT * FROM attendance_logs WHERE employee_id = 3 ORDER BY log_id DESC LIMIT 5;

echo "Verifying insertion..." . PHP_EOL;

$logs = DB::table('attendance_logs')
    ->where('employee_id', $employeeId)
    ->orderBy('log_id', 'desc')
    ->limit(5)
    ->get();

foreach ($logs as $log) {
    echo "Log ID: {$log->log_id} | Employee ID: {$log->employee_id} | Time In: {$log->time_in} | Method: {$log->method} | Kiosk ID: {$log->kiosk_id}" . PHP_EOL;
}

echo "Done." . PHP_EOL;
