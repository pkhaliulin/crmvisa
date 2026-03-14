<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->integer('amount');
            $table->string('currency', 3)->default('UZS');
            $table->string('payment_method', 30);
            $table->timestamp('paid_at');
            $table->foreignUuid('recorded_by')->nullable()->constrained('users');
            $table->text('comment')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['case_id', 'agency_id']);
        });

        Schema::table('cases', function (Blueprint $table) {
            $table->integer('total_price')->nullable();
            $table->string('price_currency', 3)->default('UZS');
            $table->date('payment_deadline')->nullable();
            $table->boolean('payment_blocked')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn(['total_price', 'price_currency', 'payment_deadline', 'payment_blocked']);
        });

        Schema::dropIfExists('case_payments');
    }
};
