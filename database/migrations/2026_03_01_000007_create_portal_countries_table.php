<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_countries', function (Blueprint $table) {
            $table->string('country_code', 2)->primary();
            $table->string('name');
            $table->string('flag_emoji', 10)->default('🌍');
            $table->boolean('is_active')->default(true);
            // Веса блоков (публичный скоринг): сумма ≈ 1.00
            $table->decimal('weight_finance', 4, 2)->default(0.30);
            $table->decimal('weight_ties',    4, 2)->default(0.40);
            $table->decimal('weight_travel',  4, 2)->default(0.20);
            $table->decimal('weight_profile', 4, 2)->default(0.10);
            // Финансовые пороги
            $table->unsignedInteger('min_monthly_income_usd')->default(500);
            $table->unsignedSmallInteger('min_score')->default(30);
            // Порядок отображения
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_countries');
    }
};
