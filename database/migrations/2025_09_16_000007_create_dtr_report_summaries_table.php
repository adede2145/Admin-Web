<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dtr_report_summaries', function (Blueprint $table) {
            $table->increments('summary_id');
            $table->unsignedInteger('report_id');
            $table->unsignedInteger('employee_id');
            $table->integer('total_days')->default(0);
            $table->integer('present_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->integer('incomplete_days')->default(0);
            $table->decimal('total_hours', 10, 2)->default(0.00);
            $table->decimal('overtime_hours', 10, 2)->default(0.00);
            $table->decimal('average_hours_per_day', 5, 2)->default(0.00);
            $table->decimal('attendance_rate', 5, 2)->default(0.00);
        });
    }

    public function down()
    {
        Schema::dropIfExists('dtr_report_summaries');
    }
};