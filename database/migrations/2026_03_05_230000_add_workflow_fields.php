<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Расширить статусы case_checklist: + needs_translation, translating, translated, translation_approved
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE case_checklist DROP CONSTRAINT IF EXISTS case_checklist_status_check");
            DB::statement("ALTER TABLE case_checklist ALTER COLUMN status TYPE varchar(30)");
        }

        // 2. Новые поля case_checklist для перевода
        Schema::table('case_checklist', function (Blueprint $table) {
            if (! Schema::hasColumn('case_checklist', 'review_status')) {
                $table->string('review_status', 30)->nullable()->after('status'); // approved | needs_translation | rejected
            }
            if (! Schema::hasColumn('case_checklist', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('review_status');
            }
            if (! Schema::hasColumn('case_checklist', 'reviewed_by')) {
                $table->uuid('reviewed_by')->nullable()->after('review_notes');
            }
            if (! Schema::hasColumn('case_checklist', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            if (! Schema::hasColumn('case_checklist', 'translation_pages')) {
                $table->integer('translation_pages')->nullable()->after('reviewed_at');
            }
            if (! Schema::hasColumn('case_checklist', 'translation_price')) {
                $table->integer('translation_price')->nullable()->after('translation_pages');
            }
            if (! Schema::hasColumn('case_checklist', 'translation_document_id')) {
                $table->uuid('translation_document_id')->nullable()->after('translation_price');
            }
            if (! Schema::hasColumn('case_checklist', 'translated_by')) {
                $table->uuid('translated_by')->nullable()->after('translation_document_id');
            }
            if (! Schema::hasColumn('case_checklist', 'translated_at')) {
                $table->timestamp('translated_at')->nullable()->after('translated_by');
            }
        });

        // 3. Новые поля cases для workflow
        Schema::table('cases', function (Blueprint $table) {
            if (! Schema::hasColumn('cases', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('appointment_location');
            }
            if (! Schema::hasColumn('cases', 'expected_result_date')) {
                $table->date('expected_result_date')->nullable()->after('submitted_at');
            }
            if (! Schema::hasColumn('cases', 'result_type')) {
                $table->string('result_type', 20)->nullable()->after('expected_result_date'); // approved | rejected
            }
            if (! Schema::hasColumn('cases', 'result_notes')) {
                $table->text('result_notes')->nullable()->after('result_type');
            }
            if (! Schema::hasColumn('cases', 'visa_issued_at')) {
                $table->date('visa_issued_at')->nullable()->after('result_notes');
            }
            if (! Schema::hasColumn('cases', 'visa_received_at')) {
                $table->date('visa_received_at')->nullable()->after('visa_issued_at');
            }
            if (! Schema::hasColumn('cases', 'visa_validity')) {
                $table->string('visa_validity', 100)->nullable()->after('visa_received_at');
            }
            if (! Schema::hasColumn('cases', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('visa_validity');
            }
            if (! Schema::hasColumn('cases', 'can_reapply')) {
                $table->boolean('can_reapply')->nullable()->after('rejection_reason');
            }
            if (! Schema::hasColumn('cases', 'reapply_recommendation')) {
                $table->text('reapply_recommendation')->nullable()->after('can_reapply');
            }
            if (! Schema::hasColumn('cases', 'previous_case_id')) {
                $table->uuid('previous_case_id')->nullable()->after('reapply_recommendation');
            }
            if (! Schema::hasColumn('cases', 'last_manager_update_at')) {
                $table->timestamp('last_manager_update_at')->nullable()->after('previous_case_id');
            }
        });

        // 4. Настройка перевода в агентстве
        Schema::table('agencies', function (Blueprint $table) {
            if (! Schema::hasColumn('agencies', 'translation_price_per_page')) {
                $table->integer('translation_price_per_page')->nullable()->after('lead_assignment_mode');
            }
            if (! Schema::hasColumn('agencies', 'translation_currency')) {
                $table->string('translation_currency', 3)->default('UZS')->after('translation_price_per_page');
            }
        });
    }

    public function down(): void
    {
        Schema::table('case_checklist', function (Blueprint $table) {
            $cols = ['review_status','review_notes','reviewed_by','reviewed_at',
                     'translation_pages','translation_price','translation_document_id',
                     'translated_by','translated_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('case_checklist', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('cases', function (Blueprint $table) {
            $cols = ['submitted_at','expected_result_date','result_type','result_notes',
                     'visa_issued_at','visa_received_at','visa_validity',
                     'rejection_reason','can_reapply','reapply_recommendation',
                     'previous_case_id','last_manager_update_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('cases', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('agencies', function (Blueprint $table) {
            if (Schema::hasColumn('agencies', 'translation_price_per_page')) {
                $table->dropColumn(['translation_price_per_page', 'translation_currency']);
            }
        });
    }
};
