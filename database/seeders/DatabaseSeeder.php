<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ROLES
        DB::table('roles')->insert([
            ['role_id' => 1, 'role_name' => 'super_admin'],
            ['role_id' => 2, 'role_name' => 'admin'],
            ['role_id' => 3, 'role_name' => 'employee'],
        ]);

        // DEPARTMENTS
        DB::table('departments')->insert([
            ['department_id' => 1, 'department_name' => 'IT Department'],
            ['department_id' => 2, 'department_name' => 'Human Resources'],
            ['department_id' => 3, 'department_name' => 'Finance'],
            ['department_id' => 4, 'department_name' => 'Marketing'],
        ]);

        // ADMINS (call AdminSeeder for super admin)
        $this->call(AdminSeeder::class);

        // EMPLOYEES (null for empty employment_type)
        DB::table('employees')->insert([
            ['employee_id'=>1,'full_name'=>'John Smith','employment_type'=>'full_time','fingerprint_hash'=>null,'rfid_code'=>'RFID001','department_id'=>1],
            ['employee_id'=>2,'full_name'=>'Sarah Johnson','employment_type'=>'full_time','fingerprint_hash'=>null,'rfid_code'=>'RFID002','department_id'=>1],
            ['employee_id'=>3,'full_name'=>'Michael Brown','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID003','department_id'=>1],
            ['employee_id'=>4,'full_name'=>'Emily Davis','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID004','department_id'=>1],
            ['employee_id'=>5,'full_name'=>'David Wilson','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID005','department_id'=>1],
            ['employee_id'=>6,'full_name'=>'Lisa Anderson','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID006','department_id'=>2],
            ['employee_id'=>7,'full_name'=>'Robert Taylor','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID007','department_id'=>2],
            ['employee_id'=>8,'full_name'=>'Jennifer Martinez','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID008','department_id'=>2],
            ['employee_id'=>9,'full_name'=>'Christopher Garcia','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID009','department_id'=>2],
            ['employee_id'=>10,'full_name'=>'Amanda Rodriguez','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID010','department_id'=>2],
            ['employee_id'=>11,'full_name'=>'James Lee','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID011','department_id'=>3],
            ['employee_id'=>12,'full_name'=>'Michelle White','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID012','department_id'=>3],
            ['employee_id'=>13,'full_name'=>'Daniel Clark','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID013','department_id'=>3],
            ['employee_id'=>14,'full_name'=>'Jessica Hall','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID014','department_id'=>3],
            ['employee_id'=>15,'full_name'=>'Kevin Lewis','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID015','department_id'=>3],
            ['employee_id'=>16,'full_name'=>'Ashley Young','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID016','department_id'=>4],
            ['employee_id'=>17,'full_name'=>'Matthew Allen','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID017','department_id'=>4],
            ['employee_id'=>18,'full_name'=>'Nicole King','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID018','department_id'=>4],
            ['employee_id'=>19,'full_name'=>'Steven Wright','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID019','department_id'=>4],
            ['employee_id'=>20,'full_name'=>'Rachel Green','employment_type'=>null,'fingerprint_hash'=>null,'rfid_code'=>'RFID020','department_id'=>4],
        ]);

        // KIOSKS
        DB::table('kiosks')->insert([
            ['kiosk_id'=>1,'location'=>'IT Department - Ground Floor','is_active'=>1],
            ['kiosk_id'=>2,'location'=>'HR Department - 2nd Floor','is_active'=>1],
            ['kiosk_id'=>3,'location'=>'Finance Department - 3rd Floor','is_active'=>1],
            ['kiosk_id'=>4,'location'=>'Marketing Department - 4th Floor','is_active'=>1],
        ]);

        // ATTENDANCE LOGS (all 32 rows from SQL dump)
        DB::table('attendance_logs')->insert([
            // paste all 32 rows from SQL file here exactly
        ]);

        // DTR REPORTS (5 rows)
        DB::table('dtr_reports')->insert([
            // paste all rows from SQL file here exactly
        ]);

        // DTR REPORT DETAILS (25 rows)
        DB::table('dtr_report_details')->insert([
            // paste all rows from SQL file here exactly
        ]);

        // DTR REPORT SUMMARIES (5 rows)
        DB::table('dtr_report_summaries')->insert([
            // paste all rows from SQL file here exactly
        ]);
    }
}
