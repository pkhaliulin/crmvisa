<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();

            // Привязка к стране / типу визы (опционально)
            $table->string('country_code', 2)->nullable()->index();
            $table->string('visa_type')->nullable();

            // Категория
            $table->string('category')->index(); // country_guide, visa_process, documents, faq, tips, requirements, common_mistakes, changes, finance

            // Контент (RU + UZ)
            $table->string('title');
            $table->string('title_uz')->nullable();
            $table->text('content');                // markdown
            $table->text('content_uz')->nullable();
            $table->text('summary')->nullable();    // краткое описание для списка
            $table->text('summary_uz')->nullable();

            // Метаданные
            $table->jsonb('tags')->nullable();
            $table->string('source')->default('admin'); // admin, agency_contribution, ai_generated
            $table->boolean('is_published')->default(false)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->integer('view_count')->default(0);

            // Для будущего semantic search (pgvector)
            // $table->vector('embedding', 1536)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['country_code', 'category']);
            $table->index(['country_code', 'visa_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_articles');
    }
};
