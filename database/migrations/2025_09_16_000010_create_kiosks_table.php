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
            $table->datetime('last_seen')->nullable()->comment('Timestamp of when this kiosk was last seen/heard from');

            // Add indexes for better query performance
            $table->index('last_seen', 'idx_kiosks_last_seen');
            $table->index(['is_active', 'last_seen'], 'idx_kiosks_active_last_seen');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kiosks');
    }
};
