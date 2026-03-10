<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_case_required_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('visa_case_rule_id');
            $table->uuid('document_template_id')->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('requirement_level', 20)->default('required'); // required, recommended, conditional
            $table->text('condition_description')->nullable();
            $table->jsonb('applicant_types')->nullable(); // ['adult','child'] or null = all
            $table->string('accepted_formats', 100)->nullable(); // pdf,jpg,png
            $table->boolean('requires_translation')->default(false);
            $table->string('min_validity_rule', 50)->nullable(); // e.g. "6_months_after_exit"
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('visa_case_rule_id')->references('id')->on('visa_case_rules')->cascadeOnDelete();
            $table->foreign('document_template_id')->references('id')->on('document_templates')->nullOnDelete();
            $table->index(['visa_case_rule_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_case_required_documents');
    }
};
