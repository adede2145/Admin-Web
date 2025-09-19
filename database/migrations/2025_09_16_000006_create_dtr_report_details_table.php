<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dtr_report_details', function (Blueprint $table) {
            $table->increments('detail_id');
            $table->unsignedInteger('report_id');
            $table->unsignedInteger('employee_id');
            $table->date('date');
            $table->dateTime('time_in')->nullable();
            $table->dateTime('time_out')->nullable();
            $table->decimal('total_hours', 5, 2)->default(0.00);
            $table->decimal('overtime_hours', 5, 2)->default(0.00);
            $table->enum('status', ['present','absent','incomplete','holiday','weekend'])->default('absent');
            $table->string('remarks', 255)->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('dtr_report_details');
    }
};