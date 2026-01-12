<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employment_types', function (Blueprint $table) {
            $table->id();
            $table->string('type_name')->unique(); // Database value: 'full_time', 'cos', etc.
            $table->string('display_name'); // UI display: 'Full Time', 'COS', etc.
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // True for core types (shouldn't be deleted)
            $table->timestamps();
        });

        // Seed initial employment types (same as current ENUM values)
        \Illuminate\Support\Facades\DB::table('employment_types')->insert([
            [
                'type_name' => 'full_time',
                'display_name' => 'Full Time',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type_name' => 'part_time',
                'display_name' => 'Part Time',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type_name' => 'cos',
                'display_name' => 'COS',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type_name' => 'admin',
                'display_name' => 'Admin',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type_name' => 'faculty with designation',
                'display_name' => 'Faculty',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employment_types');
    }
};
