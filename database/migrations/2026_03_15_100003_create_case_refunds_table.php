<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignUuid('contract_id')->nullable()->constrained('case_contracts')->nullOnDelete();
            $table->foreignUuid('payment_id')->nullable()->constrained('case_payments')->nullOnDelete();
            $table->integer('amount');
            $table->string('currency', 3)->default('UZS');
            $table->text('reason')->nullable();
            $table->string('type', 10)->default('partial');
            $table->string('basis', 30)->default('contract_policy');
            $table->string('initiator', 10)->default('agent');
            $table->string('status', 20)->default('draft');
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('refund_method', 30)->nullable();
            $table->text('comment')->nullable();
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
        Schema::dropIfExists('case_refunds');
    }
};
