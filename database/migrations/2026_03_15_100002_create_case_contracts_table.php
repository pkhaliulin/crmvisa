<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('contract_number', 30);
            $table->smallInteger('version')->default(1);
            $table->string('status', 20)->default('draft');
            $table->integer('total_price');
            $table->integer('prepayment_amount')->default(0);
            $table->integer('remaining_amount')->default(0);
            $table->string('currency', 3)->default('UZS');
            $table->date('payment_deadline')->nullable();
            $table->string('full_payment_required_stage', 30)->nullable();
            $table->jsonb('refund_policy')->nullable();
            $table->text('terms_text')->nullable();
            $table->timestamp('client_confirmed_at')->nullable();
            $table->string('client_confirmation_method', 20)->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->jsonb('metadata')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('case_id');
            $table->index(['agency_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_contracts');
    }
};
