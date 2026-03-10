<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->uuid('visa_case_rule_id')->nullable()->after('lock_version');
            $table->string('visa_subtype', 50)->nullable()->after('visa_case_rule_id');
            $table->string('applicant_type', 30)->default('adult')->after('visa_subtype');
            $table->string('embassy_platform', 50)->nullable()->after('applicant_type');
            $table->string('submission_method', 30)->nullable()->after('embassy_platform');
            $table->boolean('appointment_required')->nullable()->after('submission_method');
            $table->boolean('biometrics_required')->nullable()->after('appointment_required');
            $table->string('reference_number', 100)->nullable()->after('biometrics_required');
            $table->unsignedSmallInteger('readiness_score')->default(0)->after('reference_number');
            $table->jsonb('form_data')->nullable()->after('readiness_score');
            $table->jsonb('missing_items')->nullable()->after('form_data');
            $table->string('next_action', 255)->nullable()->after('missing_items');

            $table->foreign('visa_case_rule_id')->references('id')->on('visa_case_rules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropForeign(['visa_case_rule_id']);
            $table->dropColumn([
                'visa_case_rule_id', 'visa_subtype', 'applicant_type',
                'embassy_platform', 'submission_method', 'appointment_required',
                'biometrics_required', 'reference_number', 'readiness_score',
                'form_data', 'missing_items', 'next_action',
            ]);
        });
    }
};
