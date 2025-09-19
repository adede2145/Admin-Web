<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->increments('employee_id');
            $table->string('full_name', 100);
            $table->enum('employment_type', ['full_time','part_time','cos'])->nullable();
            $table->binary('fingerprint_hash')->nullable();
            $table->string('rfid_code', 100)->nullable();
            $table->unsignedInteger('department_id')->nullable();
            $table->string('photo_path', 500)->nullable();
            $table->binary('photo_data')->nullable();
            $table->string('photo_content_type', 50)->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
};