<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kiosk_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('kiosk_id')->index();
            $table->timestamp('last_seen')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();

            // Add foreign key directly in create statement
            $table->foreign('kiosk_id')
                ->references('kiosk_id')
                ->on('kiosks')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kiosk_heartbeats');
    }
};
