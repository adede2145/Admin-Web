<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\AttendanceLog;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceLogSeeder extends Seeder
{
    public function run()
    {
        // Get all employees
        $employees = Employee::all();
        if ($employees->isEmpty()) {
            $this->command->info('No employees found. Please run employee seeder first.');
            return;
        }

        // Generate attendance for the last 30 days
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(30);
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($employees as $employee) {
            foreach ($period as $date) {
                // Skip weekends
                if ($date->isWeekend()) {
                    continue;
                }

                // Random scenarios
                $scenario = rand(1, 100);

                if ($scenario <= 70) {
                    // 70% chance of normal attendance
                    $this->createNormalAttendance($employee, $date);
                } elseif ($scenario <= 85) {
                    // 15% chance of late arrival
                    $this->createLateAttendance($employee, $date);
                } elseif ($scenario <= 95) {
                    // 10% chance of incomplete (half day)
                    $this->createIncompleteAttendance($employee, $date);
                }
                // 5% chance of absence (no record)
            }

            // Add attendance for today
            $today = Carbon::today();
            AttendanceLog::create([
                'employee_id' => $employee->employee_id,
                'time_in' => $today->copy()->setTime(8, 0, 0),
                'time_out' => $today->copy()->setTime(17, 0, 0),
                'method' => 'rfid',
                'kiosk_id' => 1,
            ]);

            // Add attendance for tomorrow
            $tomorrow = Carbon::tomorrow();
            AttendanceLog::create([
                'employee_id' => $employee->employee_id,
                'time_in' => $tomorrow->copy()->setTime(8, 0, 0),
                'time_out' => $tomorrow->copy()->setTime(17, 0, 0),
                'method' => 'rfid',
                'kiosk_id' => 1,
            ]);
        }
    }

    private function createNormalAttendance($employee, $date)
    {
        // Normal time in (between 8:45 AM and 9:00 AM)
        $timeIn = $date->copy()->addHours(8)
                      ->addMinutes(rand(45, 59));

        // Normal time out (between 5:00 PM and 5:15 PM)
        $timeOut = $date->copy()->addHours(17)
                       ->addMinutes(rand(0, 15));

        // Create time in record
        AttendanceLog::create([
            'emp_id' => $employee->id,
            'log_type' => 'time_in',
            'method' => rand(0, 1) ? 'biometric' : 'rfid',
            'log_time' => $timeIn,
            'kiosk_id' => 'KIOSK-' . rand(1, 3),
            'is_synced' => true
        ]);

        // Create time out record
        AttendanceLog::create([
            'emp_id' => $employee->id,
            'log_type' => 'time_out',
            'method' => rand(0, 1) ? 'biometric' : 'rfid',
            'log_time' => $timeOut,
            'kiosk_id' => 'KIOSK-' . rand(1, 3),
            'is_synced' => true
        ]);
    }

    private function createLateAttendance($employee, $date)
    {
        // Late time in (between 9:15 AM and 10:00 AM)
        $timeIn = $date->copy()->addHours(9)
                      ->addMinutes(rand(15, 60));

        // Normal time out (between 5:15 PM and 5:30 PM)
        $timeOut = $date->copy()->addHours(17)
                       ->addMinutes(rand(15, 30));

        // Create time in record
        AttendanceLog::create([
            'emp_id' => $employee->id,
            'log_type' => 'time_in',
            'method' => rand(0, 1) ? 'biometric' : 'rfid',
            'log_time' => $timeIn,
            'kiosk_id' => 'KIOSK-' . rand(1, 3),
            'is_synced' => true
        ]);

        // Create time out record
        AttendanceLog::create([
            'emp_id' => $employee->id,
            'log_type' => 'time_out',
            'method' => rand(0, 1) ? 'biometric' : 'rfid',
            'log_time' => $timeOut,
            'kiosk_id' => 'KIOSK-' . rand(1, 3),
            'is_synced' => true
        ]);
    }

    private function createIncompleteAttendance($employee, $date)
    {
        // 50-50 chance between morning half-day and afternoon half-day
        if (rand(0, 1)) {
            // Morning half-day (only time in)
            $timeIn = $date->copy()->addHours(8)
                          ->addMinutes(rand(45, 59));

            AttendanceLog::create([
                'emp_id' => $employee->id,
                'log_type' => 'time_in',
                'method' => rand(0, 1) ? 'biometric' : 'rfid',
                'log_time' => $timeIn,
                'kiosk_id' => 'KIOSK-' . rand(1, 3),
                'is_synced' => true
            ]);
        } else {
            // Afternoon half-day (only time out)
            $timeOut = $date->copy()->addHours(17)
                          ->addMinutes(rand(0, 15));

            AttendanceLog::create([
                'emp_id' => $employee->id,
                'log_type' => 'time_out',
                'method' => rand(0, 1) ? 'biometric' : 'rfid',
                'log_time' => $timeOut,
                'kiosk_id' => 'KIOSK-' . rand(1, 3),
                'is_synced' => true
            ]);
        }
    }
}
