<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Добавить столбец visa_types в portal_countries
        if (! Schema::hasColumn('portal_countries', 'visa_types')) {
            Schema::table('portal_countries', function (Blueprint $table) {
                $table->jsonb('visa_types')->default('["tourist","student","business"]');
            });
        }

        // Таблица глобальных типов виз
        if (! Schema::hasTable('portal_visa_types')) {
            Schema::create('portal_visa_types', function (Blueprint $table) {
                $table->string('slug', 50)->primary();
                $table->string('name_ru', 100);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_visa_types');

        if (Schema::hasColumn('portal_countries', 'visa_types')) {
            Schema::table('portal_countries', function (Blueprint $table) {
                $table->dropColumn('visa_types');
            });
        }
    }
};
