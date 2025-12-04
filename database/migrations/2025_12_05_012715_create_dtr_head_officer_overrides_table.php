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
        Schema::create('dtr_head_officer_overrides', function (Blueprint $table) {
            $table->bigIncrements('override_id');
            $table->unsignedInteger('report_id');
            $table->unsignedInteger('employee_id');
            $table->string('head_officer_name', 255);
            $table->string('head_officer_office', 255)->nullable();
            $table->timestamps();
            
            // Unique constraint: one head officer per employee per report
            $table->unique(['report_id', 'employee_id'], 'unique_employee_report');
            
            // Foreign keys
            $table->foreign('report_id')
                  ->references('report_id')
                  ->on('dtr_reports')
                  ->onDelete('cascade');
            
            $table->foreign('employee_id')
                  ->references('employee_id')
                  ->on('employees')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dtr_head_officer_overrides');
    }
};
