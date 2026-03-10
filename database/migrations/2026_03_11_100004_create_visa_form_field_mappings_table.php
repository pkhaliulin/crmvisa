<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_form_field_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('visa_case_rule_id');
            $table->unsignedSmallInteger('step_number');
            $table->string('step_title', 100);
            $table->string('field_key', 100);
            $table->string('field_label', 255);
            $table->string('field_type', 30)->default('text'); // text, date, select, radio, checkbox, textarea
            $table->jsonb('options')->nullable(); // for select/radio
            $table->string('default_value', 255)->nullable();
            $table->string('mapping_source', 100)->nullable(); // client.first_name, case.travel_date etc.
            $table->string('transform_rule', 100)->nullable(); // uppercase, date_dmy, country_name etc.
            $table->text('help_text')->nullable();
            $table->jsonb('validation_rules')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('visa_case_rule_id')->references('id')->on('visa_case_rules')->cascadeOnDelete();
            $table->index(['visa_case_rule_id', 'step_number', 'display_order'], 'vffm_rule_step_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_form_field_mappings');
    }
};
