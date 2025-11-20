<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('kiosks', function (Blueprint $table) {
            $table->datetime('last_reboot_at')->nullable()->after('last_seen')
                ->comment('Timestamp of when the kiosk was last rebooted/started');
            $table->index('last_reboot_at', 'idx_kiosks_last_reboot_at');
        });
    }

    public function down()
    {
        Schema::table('kiosks', function (Blueprint $table) {
            $table->dropIndex('idx_kiosks_last_reboot_at');
            $table->dropColumn('last_reboot_at');
        });
    }
};
