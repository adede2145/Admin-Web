<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminSeeder extends Seeder
{
    public function run()
    {
        DB::table('admins')->insert([
            'username' => 'tresmongos',
            'password_hash' => hash('sha256', 'klentchristianade'),
            'role_id' => 1, // super_admin
        ]);
    }
}
