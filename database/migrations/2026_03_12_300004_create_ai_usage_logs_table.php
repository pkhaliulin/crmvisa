<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agency_id')->nullable()->index();
            $table->uuid('case_id')->nullable()->index();
            $table->uuid('user_id')->nullable();

            $table->string('service', 30);        // ocr_passport, doc_analyze, doc_cross_validate
            $table->string('provider', 20);        // openai, claude, google, mindee
            $table->string('model', 50);           // gpt-4o-mini, claude-haiku-4-5, etc.

            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0);

            $table->string('status', 20)->default('success'); // success, error
            $table->text('error_message')->nullable();
            $table->integer('duration_ms')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
