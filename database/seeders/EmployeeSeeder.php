<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;

class EmployeeSeeder extends Seeder
{
    public function run()
    {
        $employees = [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'department_id' => 1,
                'position' => 'Software Engineer',
                'employment_status' => 'Regular',
                'date_hired' => '2023-01-15',
            ],
            [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane.smith@example.com',
                'department_id' => 2,
                'position' => 'HR Manager',
                'employment_status' => 'Regular',
                'date_hired' => '2023-02-01',
            ],
            [
                'first_name' => 'Mike',
                'last_name' => 'Johnson',
                'email' => 'mike.johnson@example.com',
                'department_id' => 1,
                'position' => 'Senior Developer',
                'employment_status' => 'Regular',
                'date_hired' => '2023-01-10',
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Williams',
                'email' => 'sarah.williams@example.com',
                'department_id' => 3,
                'position' => 'Accountant',
                'employment_status' => 'Regular',
                'date_hired' => '2023-03-01',
            ],
            [
                'first_name' => 'Robert',
                'last_name' => 'Brown',
                'email' => 'robert.brown@example.com',
                'department_id' => 2,
                'position' => 'HR Assistant',
                'employment_status' => 'Probationary',
                'date_hired' => '2023-07-01',
            ],
        ];

        foreach ($employees as $employee) {
            Employee::create($employee);
        }
    }
}
