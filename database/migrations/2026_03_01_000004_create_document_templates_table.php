<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Глобальный справочник типов документов.
        // Каждый документ создаётся ОДИН РАЗ и может использоваться в любом количестве стран.
        Schema::create('document_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();           // foreign_passport, bank_statement, marriage_cert...
            $table->string('name');                     // "Загранпаспорт", "Выписка из банка"
            $table->enum('category', [
                'personal',      // Личные: паспорт, фото, ВНЖ
                'financial',     // Финансовые: выписки, справки о доходах
                'family',        // Семейные: свидетельство о браке, метрики детей
                'property',      // Имущество: недвижимость, авто
                'travel',        // Поездка: билеты, бронь, страховка
                'employment',    // Занятость: справка с работы, ИП
                'confirmation',  // Подтверждения без файла: фото сделано, анкета заполнена
                'other',
            ])->default('other');
            $table->text('description')->nullable();    // Общее пояснение (что это за документ)
            $table->enum('type', ['upload', 'checkbox'])->default('upload');
            $table->boolean('is_repeatable')->default(false);   // Метрика ребёнка — можно добавить несколько
            $table->jsonb('metadata_schema')->nullable();        // JSON: {"min_balance": 500, "period_months": 3}
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Pivot: связь страна+тип визы → документ, с уровнем требования и версионированием.
        Schema::create('country_visa_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('country_code', 2);          // ISO Alpha-2: DE, ES, US...
            $table->string('visa_type', 50);             // tourist, business, student, work, * = все типы
            $table->foreignUuid('document_template_id')->constrained('document_templates')->cascadeOnDelete();
            $table->enum('requirement_level', [
                'required',           // Обязательный — без него подача невозможна
                'recommended',        // Рекомендуемый — повышает шансы (proof of ties)
                'confirmation_only',  // Только чекбокс — фото сделано, подпись поставлена и т.д.
            ])->default('required');
            $table->text('notes')->nullable();           // Специфические инструкции для этой страны
            $table->jsonb('override_metadata')->nullable(); // Переопределить metadata_schema шаблона
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            // Версионирование требований (консульства меняют правила)
            $table->date('effective_from')->nullable();  // null = всегда актуально
            $table->date('effective_to')->nullable();    // null = бессрочно
            $table->timestamps();

            $table->unique(['country_code', 'visa_type', 'document_template_id'], 'cvr_unique');
            $table->index(['country_code', 'visa_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_visa_requirements');
        Schema::dropIfExists('document_templates');
    }
};
