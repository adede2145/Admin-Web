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
        Schema::table('attendance_logs', function (Blueprint $table) {
            // RFID verification fields
            $table->text('rfid_reason')->nullable()->comment('Reason why RFID was used');
            $table->boolean('is_verified')->default(false)->comment('True after admin verification');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending')->comment('Verification status');
            $table->unsignedInteger('verified_by')->nullable()->comment('Admin who verified the record');
            $table->timestamp('verified_at')->nullable()->comment('When the record was verified');
            $table->text('verification_notes')->nullable()->comment('Notes for rejection reason');

            // Add foreign key constraint to admins table
            $table->foreign('verified_by')->references('admin_id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['verified_by']);

            // Drop columns
            $table->dropColumn([
                'rfid_reason',
                'is_verified',
                'verification_status',
                'verified_by',
                'verified_at',
                'verification_notes'
            ]);
        });
    }
};
