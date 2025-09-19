<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kiosks', function (Blueprint $table) {
            $table->increments('kiosk_id');
            $table->string('location', 100)->nullable();
            $table->boolean('is_active')->default(1);
        });
    }

    public function down()
    {
        Schema::dropIfExists('kiosks');
    }
};