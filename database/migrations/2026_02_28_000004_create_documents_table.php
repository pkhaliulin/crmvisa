<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 60);           // passport, photo, bank_statement, invitation...
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedSmallInteger('version')->default(1);
            $table->text('notes')->nullable();
            // OCR
            $table->enum('ocr_status', ['none', 'pending', 'processing', 'done', 'failed'])->default('none');
            $table->jsonb('extracted_data')->nullable();
            $table->timestamp('ocr_processed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
