<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        $departments = [
            [
                'name' => 'IT Department',
                'description' => 'Information Technology Department',
            ],
            [
                'name' => 'Human Resources',
                'description' => 'HR Department',
            ],
            [
                'name' => 'Finance',
                'description' => 'Finance and Accounting Department',
            ],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }
    }
}
