<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;

class EmployeeSeeder extends Seeder
{
    public function run()
    {
        $employmentTypes = ['full_time', 'part_time', 'cos', 'admin', 'faculty with designation'];
        
        $employees = [
            [
                'employee_code' => 'EMP201',
                'full_name' => 'Alexander Rodriguez',
                'employment_type' => $employmentTypes[array_rand($employmentTypes)],
                'department_id' => 1, // IT Office
                'rfid_code' => 'RFID201',
            ],
            [
                'employee_code' => 'EMP202',
                'full_name' => 'Emma Thompson',
                'employment_type' => $employmentTypes[array_rand($employmentTypes)],
                'department_id' => 1, // IT Office
                'rfid_code' => 'RFID202',
            ],
            [
                'employee_code' => 'EMP203',
                'full_name' => 'Michael Nguyen',
                'employment_type' => $employmentTypes[array_rand($employmentTypes)],
                'department_id' => 1, // IT Office
                'rfid_code' => 'RFID203',
            ],
            [
                'employee_code' => 'EMP204',
                'full_name' => 'Sophia Kumar',
                'employment_type' => $employmentTypes[array_rand($employmentTypes)],
                'department_id' => 1, // IT Office
                'rfid_code' => 'RFID204',
            ],
            [
                'employee_code' => 'EMP205',
                'full_name' => 'Daniel Park',
                'employment_type' => $employmentTypes[array_rand($employmentTypes)],
                'department_id' => 1, // IT Office
                'rfid_code' => 'RFID205',
            ],
        ];

        foreach ($employees as $employee) {
            Employee::create($employee);
        }
    }
}
