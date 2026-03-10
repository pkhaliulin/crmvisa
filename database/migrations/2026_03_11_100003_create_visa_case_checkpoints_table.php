<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_case_checkpoints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('visa_case_rule_id');
            $table->string('stage', 30);
            $table->string('slug', 50);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('check_type', 20)->default('manual'); // manual, auto_document, auto_field, auto_payment
            $table->jsonb('auto_check_config')->nullable();
            $table->boolean('is_blocking')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('visa_case_rule_id')->references('id')->on('visa_case_rules')->cascadeOnDelete();
            $table->unique(['visa_case_rule_id', 'slug'], 'vcc_rule_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_case_checkpoints');
    }
};
