<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->increments('log_id');
            $table->unsignedInteger('employee_id')->nullable();
            $table->dateTime('time_in');
            $table->dateTime('time_out')->nullable();
            $table->enum('method', ['rfid', 'fingerprint', 'manual']);
            $table->unsignedInteger('kiosk_id')->nullable();

            // Photo capture fields for RFID scanning
            $table->string('photo_content_type', 100)->nullable(); // MIME type (image/jpeg, image/png)
            $table->timestamp('photo_captured_at')->nullable(); // When photo was taken
            $table->string('photo_filename', 255)->nullable(); // Original filename if any
        });

        // Add LONGBLOB column using raw SQL since Laravel doesn't support it directly
        DB::statement('ALTER TABLE attendance_logs ADD COLUMN photo_data LONGBLOB NULL COMMENT "Photo binary data"');
    }

    public function down()
    {
        Schema::dropIfExists('attendance_logs');
    }
};
