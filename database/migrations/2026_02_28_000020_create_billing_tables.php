<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Тарифные планы (хранятся в БД, управляются через админку)
        Schema::create('billing_plans', function (Blueprint $table) {
            $table->string('slug')->primary();           // trial, starter, pro, enterprise
            $table->string('name');
            $table->unsignedInteger('price_monthly');    // в центах USD (1900 = $19)
            $table->unsignedInteger('price_yearly');     // в центах USD со скидкой
            $table->unsignedInteger('price_uzs');        // в сумах UZB для Payme
            $table->unsignedInteger('max_managers');
            $table->unsignedInteger('max_cases');        // 0 = unlimited
            $table->boolean('has_marketplace')->default(false);
            $table->boolean('has_priority_support')->default(false);
            $table->string('stripe_price_id_monthly')->nullable();
            $table->string('stripe_price_id_yearly')->nullable();
            $table->jsonb('features')->default('[]');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Подписки агентств
        Schema::create('agency_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained()->cascadeOnDelete();
            $table->string('plan_slug');
            $table->enum('status', ['active', 'trialing', 'past_due', 'cancelled', 'expired'])
                  ->default('trialing');
            $table->enum('billing_period', ['monthly', 'yearly'])->default('monthly');
            $table->enum('payment_method', ['stripe', 'payme', 'manual'])->default('stripe');
            $table->string('stripe_subscription_id')->nullable()->index();
            $table->string('stripe_customer_id')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();

            $table->foreign('plan_slug')->references('slug')->on('billing_plans');
            $table->index(['agency_id', 'status']);
        });

        // История транзакций
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained()->cascadeOnDelete();
            $table->uuid('subscription_id')->nullable();
            $table->enum('provider', ['stripe', 'payme', 'manual']);
            $table->string('provider_transaction_id')->nullable()->index();
            $table->unsignedInteger('amount');           // в минимальных единицах валюты
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'succeeded', 'failed', 'refunded'])
                  ->default('pending');
            $table->string('description')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('agency_subscriptions');
        Schema::dropIfExists('billing_plans');
    }
};
