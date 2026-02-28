<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoring_country_weights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('country_code', 2);
            $table->string('block_code', 2);   // F, E, FM, A, T, P, G
            $table->decimal('weight', 5, 2);   // сумма по стране = 100
            $table->timestamps();

            $table->unique(['country_code', 'block_code']);
        });

        Schema::create('scoring_financial_thresholds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('country_code', 2)->unique();
            $table->unsignedInteger('min_bank_balance')->default(0);    // USD
            $table->unsignedInteger('min_monthly_income')->default(0);  // USD
            $table->unsignedSmallInteger('bank_history_days')->default(90);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('client_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained()->cascadeOnDelete();
            $table->string('country_code', 2);
            $table->decimal('score', 5, 2)->default(0);   // 0–100
            $table->jsonb('block_scores')->nullable();     // разбивка по блокам
            $table->jsonb('flags')->nullable();            // красные флаги
            $table->jsonb('recommendations')->nullable();  // советы
            $table->boolean('is_blocked')->default(false); // судимость
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'country_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_scores');
        Schema::dropIfExists('scoring_financial_thresholds');
        Schema::dropIfExists('scoring_country_weights');
    }
};
