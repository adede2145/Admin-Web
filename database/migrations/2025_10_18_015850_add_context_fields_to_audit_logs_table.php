<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('context_info')->nullable()->after('model_id');
            $table->string('related_model_type')->nullable()->after('context_info');
            $table->unsignedBigInteger('related_model_id')->nullable()->after('related_model_type');
            $table->text('summary')->nullable()->after('related_model_id');
        });
    }

    public function down()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['context_info', 'related_model_type', 'related_model_id', 'summary']);
        });
    }
};