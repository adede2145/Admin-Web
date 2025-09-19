<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->increments('admin_id');
            $table->string('username', 50);
            $table->text('password_hash');
            $table->text('fingerprint_hash')->nullable();
            $table->string('rfid_code', 100)->nullable();
            $table->unsignedInteger('role_id')->nullable();
            $table->unsignedInteger('department_id')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admins');
    }
};