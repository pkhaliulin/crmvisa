<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Публичный профиль агентства на маркетплейсе
        Schema::create('agency_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('website')->nullable();
            $table->jsonb('countries')->default('[]');    // ISO коды стран с которыми работают
            $table->jsonb('visa_types')->default('[]');   // Типы виз
            $table->jsonb('services')->default('[]');     // Список услуг
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_featured')->default(false); // Приоритет в списке
            $table->boolean('is_visible')->default(false);  // Видим только Pro/Enterprise
            $table->unsignedSmallInteger('rating_avg')->default(0);   // 0-500 (x100)
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('completed_cases')->default(0);
            $table->timestamps();
        });

        // Заявки клиентов через маркетплейс
        Schema::create('marketplace_leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained()->cascadeOnDelete();
            $table->string('client_name');
            $table->string('client_phone');
            $table->string('client_email')->nullable();
            $table->string('country_code', 2);
            $table->string('visa_type', 50)->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['new', 'contacted', 'converted', 'rejected'])
                  ->default('new');
            $table->uuid('converted_case_id')->nullable();  // если стал заявкой
            $table->string('utm_source')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_leads');
        Schema::dropIfExists('agency_profiles');
    }
};
