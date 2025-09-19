<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('admin_id'); // Who made the change
            $table->string('action'); // What type of action (edit, create, delete)
            $table->string('model_type'); // Which model was affected (Employee, AttendanceLog, etc.)
            $table->unsignedBigInteger('model_id'); // ID of the affected record
            $table->text('old_values')->nullable(); // Previous values in JSON
            $table->text('new_values')->nullable(); // New values in JSON
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('admin_id')
                  ->references('admin_id')
                  ->on('admins')
                  ->onDelete('cascade');

            $table->index(['model_type', 'model_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
};