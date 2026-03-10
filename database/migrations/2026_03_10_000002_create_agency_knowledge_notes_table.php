<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_knowledge_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agency_id')->index();
            $table->string('country_code', 2)->index();
            $table->string('visa_type')->nullable();
            $table->string('category')->nullable(); // process, tips, contacts, prices, timing, other

            // Контент
            $table->string('title');
            $table->text('content'); // markdown

            // Автор
            $table->uuid('created_by')->nullable();

            // Флаги
            $table->boolean('is_pinned')->default(false);

            // Готовность к вкладу в глобальную БЗ
            $table->boolean('is_shared')->default(false);           // агентство хочет поделиться
            $table->string('moderation_status')->nullable();        // pending, approved, rejected, merged
            $table->uuid('merged_to_article_id')->nullable();       // FK -> knowledge_articles
            $table->float('ai_review_score')->nullable();           // оценка ИИ (0-1)
            $table->text('ai_review_comment')->nullable();          // комментарий ИИ

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('agency_id')->references('id')->on('agencies')->onDelete('cascade');
            $table->index(['agency_id', 'country_code']);
        });

        // RLS для мультитенантности
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE agency_knowledge_notes ENABLE ROW LEVEL SECURITY');
            DB::statement('ALTER TABLE agency_knowledge_notes FORCE ROW LEVEL SECURITY');
            DB::statement("
                CREATE POLICY tenant_isolation_agency_knowledge_notes ON agency_knowledge_notes
                FOR ALL
                USING (
                    agency_id::text = current_setting('app.current_tenant_id', true)
                    OR current_setting('app.is_superadmin', true) = 'true'
                )
                WITH CHECK (
                    agency_id::text = current_setting('app.current_tenant_id', true)
                    OR current_setting('app.is_superadmin', true) = 'true'
                )
            ");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('DROP POLICY IF EXISTS tenant_isolation_agency_knowledge_notes ON agency_knowledge_notes');
            DB::statement('ALTER TABLE agency_knowledge_notes DISABLE ROW LEVEL SECURITY');
        }

        Schema::dropIfExists('agency_knowledge_notes');
    }
};
