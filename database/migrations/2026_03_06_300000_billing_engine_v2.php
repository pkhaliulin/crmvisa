<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // 1. Расширяем billing_plans — полноценные тарифы
        // =====================================================================
        Schema::table('billing_plans', function (Blueprint $table) {
            // Описание для отображения
            $table->text('description')->nullable()->after('name');
            $table->string('name_uz')->nullable()->after('name');

            // Активационный сбор (обязательный платёж при подключении)
            $table->unsignedInteger('activation_fee_uzs')->default(0)->after('price_uzs');

            // Earn-first модель: подписка удерживается из заработка
            $table->boolean('earn_first_enabled')->default(false)->after('activation_fee_uzs');
            $table->unsignedSmallInteger('earn_first_deduction_pct')->default(0)
                  ->comment('% удержания с каждого выполненного заказа')->after('earn_first_enabled');

            // Лимиты
            $table->unsignedInteger('max_leads_per_month')->default(0)->comment('0=unlimited')->after('max_cases');
            $table->boolean('has_api_access')->default(false)->after('has_priority_support');
            $table->boolean('has_custom_domain')->default(false)->after('has_api_access');
            $table->boolean('has_white_label')->default(false)->after('has_custom_domain');
            $table->boolean('has_analytics')->default(false)->after('has_white_label');

            // Пробный период
            $table->unsignedSmallInteger('trial_days')->default(0)->after('has_analytics');

            // Grace-период (сколько дней после окончания подписки не блокировать)
            $table->unsignedSmallInteger('grace_period_days')->default(3)->after('trial_days');

            // Настройки видимости
            $table->boolean('is_public')->default(true)->after('is_active');
            $table->boolean('is_recommended')->default(false)->after('is_public');
        });

        // =====================================================================
        // 2. Расширяем agency_subscriptions
        // =====================================================================
        Schema::table('agency_subscriptions', function (Blueprint $table) {
            // Модель оплаты: prepaid (классика), earn_first (удержание), hybrid
            $table->string('payment_model', 20)->default('prepaid')->after('payment_method');

            // Earn-first поля
            $table->unsignedInteger('earn_first_deducted_total')->default(0)
                  ->comment('Сколько уже удержано (UZS)')->after('payment_model');
            $table->unsignedInteger('earn_first_target')->default(0)
                  ->comment('Целевая сумма подписки для удержания (UZS)')->after('earn_first_deducted_total');

            // Активационный сбор
            $table->boolean('activation_fee_paid')->default(false)->after('earn_first_target');
            $table->timestamp('activation_paid_at')->nullable()->after('activation_fee_paid');

            // Grace-period
            $table->timestamp('grace_ends_at')->nullable()->after('expires_at');

            // Авто-продление
            $table->boolean('auto_renew')->default(true)->after('grace_ends_at');

            // Купон
            $table->uuid('coupon_id')->nullable()->after('auto_renew');
            $table->unsignedInteger('discount_amount')->default(0)->after('coupon_id');
        });

        // =====================================================================
        // 3. Расширяем payment_transactions
        // =====================================================================
        Schema::table('payment_transactions', function (Blueprint $table) {
            // Тип транзакции
            $table->string('type', 30)->default('subscription')
                  ->comment('subscription|activation_fee|earn_first_deduction|refund|payout|commission|addon')
                  ->after('subscription_id');

            // Направление
            $table->string('direction', 10)->default('inbound')
                  ->comment('inbound (от агентства) или outbound (выплата агентству)')
                  ->after('type');

            // НДС
            $table->unsignedInteger('vat_amount')->default(0)->after('amount');
            $table->decimal('vat_rate', 5, 2)->default(0)->after('vat_amount');
        });

        // =====================================================================
        // 4. platform_settings — глобальные настройки платформы
        // =====================================================================
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value');
            $table->string('type', 20)->default('string')->comment('string|integer|decimal|boolean|json');
            $table->string('group', 50)->default('general');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Начальные настройки
        DB::table('platform_settings')->insert([
            ['key' => 'vat_enabled',          'value' => 'false',  'type' => 'boolean', 'group' => 'billing', 'description' => 'Включить НДС',                        'created_at' => now(), 'updated_at' => now()],
            ['key' => 'vat_rate',             'value' => '12.00',  'type' => 'decimal', 'group' => 'billing', 'description' => 'Ставка НДС (%)',                       'created_at' => now(), 'updated_at' => now()],
            ['key' => 'platform_commission',  'value' => '5.00',   'type' => 'decimal', 'group' => 'billing', 'description' => 'Комиссия платформы с каждого лида (%)', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'click_fee_pct',        'value' => '2.50',   'type' => 'decimal', 'group' => 'billing', 'description' => 'Комиссия Click (%)',                    'created_at' => now(), 'updated_at' => now()],
            ['key' => 'payme_fee_pct',        'value' => '2.00',   'type' => 'decimal', 'group' => 'billing', 'description' => 'Комиссия Payme (%)',                    'created_at' => now(), 'updated_at' => now()],
            ['key' => 'uzum_fee_pct',         'value' => '2.00',   'type' => 'decimal', 'group' => 'billing', 'description' => 'Комиссия Uzum (%)',                     'created_at' => now(), 'updated_at' => now()],
            ['key' => 'payout_min_amount',    'value' => '100000', 'type' => 'integer', 'group' => 'billing', 'description' => 'Минимальная сумма выплаты (UZS)',       'created_at' => now(), 'updated_at' => now()],
            ['key' => 'payout_cycle_days',    'value' => '14',     'type' => 'integer', 'group' => 'billing', 'description' => 'Цикл выплат (дни)',                     'created_at' => now(), 'updated_at' => now()],
            ['key' => 'dunning_max_retries',  'value' => '3',      'type' => 'integer', 'group' => 'billing', 'description' => 'Макс. попыток повторного списания',     'created_at' => now(), 'updated_at' => now()],
            ['key' => 'dunning_retry_days',   'value' => '3',      'type' => 'integer', 'group' => 'billing', 'description' => 'Интервал повтора списания (дни)',       'created_at' => now(), 'updated_at' => now()],
        ]);

        // =====================================================================
        // 5. agency_wallets — кошелёк агентства (баланс заработка)
        // =====================================================================
        Schema::create('agency_wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->unique()->constrained()->cascadeOnDelete();
            $table->bigInteger('balance')->default(0)->comment('Текущий баланс (UZS), может быть отрицательным');
            $table->bigInteger('total_earned')->default(0)->comment('Всего заработано');
            $table->bigInteger('total_deducted')->default(0)->comment('Всего удержано (подписка earn-first, комиссия)');
            $table->bigInteger('total_paid_out')->default(0)->comment('Всего выплачено');
            $table->bigInteger('pending_payout')->default(0)->comment('Ожидает выплаты');
            $table->timestamp('last_payout_at')->nullable();
            $table->timestamps();
        });

        // =====================================================================
        // 6. ledger_entries — двойная бухгалтерия
        // =====================================================================
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaction_id')->nullable()->index();
            $table->uuid('agency_id')->nullable()->index();
            $table->string('account', 50)->index()->comment('platform_revenue|agency_wallet|agency_subscription|client_payment|vat_payable|provider_fees');
            $table->bigInteger('debit')->default(0);
            $table->bigInteger('credit')->default(0);
            $table->string('currency', 3)->default('UZS');
            $table->string('description')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['account', 'created_at']);
        });

        // =====================================================================
        // 7. invoices — счета-фактуры
        // =====================================================================
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained()->cascadeOnDelete();
            $table->uuid('subscription_id')->nullable();
            $table->string('number', 30)->unique()->comment('INV-2026-0001');
            $table->enum('type', ['subscription', 'activation', 'addon', 'commission', 'payout'])->default('subscription');
            $table->enum('status', ['draft', 'issued', 'paid', 'overdue', 'cancelled', 'refunded'])->default('draft');
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('vat_amount')->default(0);
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('total');
            $table->string('currency', 3)->default('UZS');
            $table->jsonb('line_items')->default('[]');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'status']);
            $table->index('due_at');
        });

        // =====================================================================
        // 8. coupons — купоны / промокоды
        // =====================================================================
        Schema::create('coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 30)->unique();
            $table->string('description')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->unsignedInteger('discount_value')->comment('% или фиксированная сумма UZS');
            $table->unsignedSmallInteger('max_uses')->default(0)->comment('0=unlimited');
            $table->unsignedSmallInteger('used_count')->default(0);
            $table->string('plan_slug')->nullable()->comment('Только для конкретного плана');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
        });

        // =====================================================================
        // 9. plan_addons — дополнительные опции к тарифам
        // =====================================================================
        Schema::create('plan_addons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 50)->unique();
            $table->string('name');
            $table->string('name_uz')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('price_monthly_uzs')->default(0);
            $table->unsignedInteger('price_yearly_uzs')->default(0);
            $table->jsonb('limits')->default('{}')->comment('{"extra_managers":5,"extra_cases":100}');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // =====================================================================
        // 10. agency_addons — активированные аддоны для агентства
        // =====================================================================
        Schema::create('agency_addons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('addon_id')->constrained('plan_addons')->cascadeOnDelete();
            $table->uuid('subscription_id')->nullable();
            $table->enum('status', ['active', 'cancelled', 'expired'])->default('active');
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
        });

        // =====================================================================
        // 11. billing_events — аудит-лог всех биллинг-действий
        // =====================================================================
        Schema::create('billing_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agency_id')->nullable()->index();
            $table->uuid('actor_id')->nullable()->comment('user_id или system');
            $table->string('event', 60)->index()->comment('subscription.created, payment.succeeded, etc.');
            $table->string('entity_type', 50)->nullable();
            $table->uuid('entity_id')->nullable();
            $table->jsonb('payload')->default('{}');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_events');
        Schema::dropIfExists('agency_addons');
        Schema::dropIfExists('plan_addons');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('agency_wallets');
        Schema::dropIfExists('platform_settings');

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn(['type', 'direction', 'vat_amount', 'vat_rate']);
        });

        Schema::table('agency_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'payment_model', 'earn_first_deducted_total', 'earn_first_target',
                'activation_fee_paid', 'activation_paid_at', 'grace_ends_at',
                'auto_renew', 'coupon_id', 'discount_amount',
            ]);
        });

        Schema::table('billing_plans', function (Blueprint $table) {
            $table->dropColumn([
                'description', 'name_uz', 'activation_fee_uzs', 'earn_first_enabled',
                'earn_first_deduction_pct', 'max_leads_per_month', 'has_api_access',
                'has_custom_domain', 'has_white_label', 'has_analytics', 'trial_days',
                'grace_period_days', 'is_public', 'is_recommended',
            ]);
        });
    }
};
