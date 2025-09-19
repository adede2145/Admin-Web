<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dtr_reports', function (Blueprint $table) {
            $table->increments('report_id');
            $table->unsignedInteger('admin_id')->nullable();
            $table->unsignedInteger('department_id')->nullable();
            $table->enum('report_type', ['weekly','monthly','custom']);
            $table->string('report_title', 255)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->dateTime('generated_on')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('file_path', 500)->nullable();
            $table->integer('total_employees')->default(0);
            $table->integer('total_days')->default(0);
            $table->decimal('total_hours', 10, 2)->default(0.00);
            $table->enum('status', ['generated','archived','deleted'])->default('generated');
            $table->text('notes')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('dtr_reports');
    }
};