<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_checklist', function (Blueprint $table) {
            $table->jsonb('ai_analysis')->nullable()->after('translation_document_id');
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_analysis');
            $table->integer('ai_confidence')->nullable()->after('ai_analyzed_at');
        });
    }

    public function down(): void
    {
        Schema::table('case_checklist', function (Blueprint $table) {
            $table->dropColumn(['ai_analysis', 'ai_analyzed_at', 'ai_confidence']);
        });
    }
};
