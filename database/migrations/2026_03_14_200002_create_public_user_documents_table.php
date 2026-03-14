<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_user_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('public_user_id')->constrained()->cascadeOnDelete();

            $table->string('doc_type', 20);  // foreign_passport, id_card, internal_passport
            $table->text('doc_number')->nullable();          // encrypted
            $table->text('expires_at')->nullable();          // encrypted
            $table->text('issue_date')->nullable();          // encrypted
            $table->text('issued_by')->nullable();           // encrypted
            $table->text('place_of_birth')->nullable();      // encrypted
            $table->text('country')->nullable();             // ISO-2

            // ФИО как в документе
            $table->text('first_name')->nullable();          // encrypted
            $table->text('last_name')->nullable();           // encrypted
            $table->text('middle_name')->nullable();         // encrypted
            $table->string('script_type', 10)->nullable();   // latin, cyrillic, mixed

            $table->string('gender', 1)->nullable();
            $table->text('nationality')->nullable();         // ISO-3
            $table->text('dob')->nullable();                 // encrypted
            $table->text('pnfl')->nullable();                // encrypted

            // MRZ
            $table->text('mrz_line1')->nullable();
            $table->text('mrz_line2')->nullable();

            // OCR
            $table->string('ocr_provider', 20)->nullable();
            $table->decimal('ocr_confidence', 3, 2)->nullable();
            $table->text('ocr_raw_data')->nullable();        // encrypted

            // Файл
            $table->string('file_path', 500)->nullable();

            // Статус
            $table->boolean('is_current')->default(true);
            $table->timestamp('replaced_at')->nullable();
            $table->string('replaced_reason', 50)->nullable(); // new_document, correction, expiry

            $table->timestamps();
            $table->softDeletes();

            $table->index(['public_user_id', 'doc_type', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_user_documents');
    }
};
