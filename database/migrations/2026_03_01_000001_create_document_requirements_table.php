<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Справочник необходимых документов по стране + типу визы
        Schema::create('document_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('country_code', 2);          // DE, FR, US... или '*' = для всех стран
            $table->string('visa_type', 50);             // tourist, business, student... или '*' = для всех
            $table->string('name');                      // "Загранпаспорт", "Фото 3x4", "Выписка из банка"
            $table->text('description')->nullable();     // Пояснение: что именно нужно
            $table->boolean('is_required')->default(true);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country_code', 'visa_type']);
        });

        // Чек-лист документов для конкретной заявки (авто-создаётся при создании заявки)
        Schema::create('case_checklist', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('case_id')->constrained('cases')->cascadeOnDelete();
            $table->uuid('requirement_id')->nullable(); // ссылка на справочник (nullable — если добавлен вручную)
            $table->string('name');                     // копируется из requirements.name
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->uuid('document_id')->nullable();    // загруженный файл (→ documents.id)
            $table->enum('status', ['pending', 'uploaded', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();          // комментарий менеджера при rejected
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['case_id', 'status']);
            $table->foreign('requirement_id')->references('id')->on('document_requirements')->nullOnDelete();
            $table->foreign('document_id')->references('id')->on('documents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_checklist');
        Schema::dropIfExists('document_requirements');
    }
};
