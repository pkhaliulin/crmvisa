<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_financial_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignUuid('contract_id')->nullable()->constrained('case_contracts')->nullOnDelete();
            $table->string('type', 30);
            $table->string('document_number', 30)->nullable();
            $table->date('date');
            $table->string('status', 20)->default('draft');
            $table->string('pdf_path', 500)->nullable();
            $table->smallInteger('version')->default(1);
            $table->integer('amount')->nullable();
            $table->string('currency', 3)->default('UZS');
            $table->jsonb('metadata')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('case_id');
            $table->index(['agency_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_financial_documents');
    }
};
