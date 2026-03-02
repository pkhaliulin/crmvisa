<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 5 критериев в отзывах (nullable — для обратной совместимости)
        Schema::table('agency_reviews', function (Blueprint $table) {
            $table->smallInteger('punctuality')->nullable()->after('rating');
            $table->smallInteger('quality')->nullable()->after('punctuality');
            $table->smallInteger('communication')->nullable()->after('quality');
            $table->smallInteger('professionalism')->nullable()->after('communication');
            $table->smallInteger('price_quality')->nullable()->after('professionalism');
        });

        // Лучший критерий агентства (кешируется при сохранении отзыва)
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('top_criterion', 50)->nullable()->after('reviews_count');
        });
    }

    public function down(): void
    {
        Schema::table('agency_reviews', function (Blueprint $table) {
            $table->dropColumn(['punctuality', 'quality', 'communication', 'professionalism', 'price_quality']);
        });

        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn('top_criterion');
        });
    }
};
