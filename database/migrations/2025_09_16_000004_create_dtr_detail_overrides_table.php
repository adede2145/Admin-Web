<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dtr_detail_overrides', function (Blueprint $table) {
            $table->bigIncrements('override_id');
            $table->unsignedInteger('report_id');
            $table->unsignedInteger('employee_id');
            $table->date('date');
            $table->enum('status_override', ['leave'])->default('leave');
            $table->string('remarks', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('dtr_detail_overrides');
    }
};