<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_fingerprint_templates', function (Blueprint $table) {
            $table->bigIncrements('template_id');
            $table->unsignedInteger('employee_id');
            $table->unsignedTinyInteger('template_index')->default(1);
            // Will be altered to LONGBLOB after table creation
            $table->binary('template_data');
            $table->decimal('template_quality', 5, 2)->nullable();
            $table->string('finger_position', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Upgrade template_data to LONGBLOB for larger templates
        DB::statement('ALTER TABLE employee_fingerprint_templates MODIFY COLUMN template_data LONGBLOB NOT NULL');
    }

    public function down()
    {
        Schema::dropIfExists('employee_fingerprint_templates');
    }
};