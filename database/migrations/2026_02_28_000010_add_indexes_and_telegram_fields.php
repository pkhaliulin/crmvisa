<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Индексы для таблицы cases (частые фильтры и сортировки)
        Schema::table('cases', function (Blueprint $table) {
            $table->index('stage');
            $table->index('critical_date');
            $table->index(['agency_id', 'stage']);
            $table->index(['agency_id', 'assigned_to']);
            $table->index(['agency_id', 'critical_date']);
        });

        // Индексы для документов
        Schema::table('documents', function (Blueprint $table) {
            $table->index('case_id');
            $table->index('client_id');
            $table->index('status');
        });

        // Индексы для clients
        Schema::table('clients', function (Blueprint $table) {
            $table->index('source'); // marketplace vs agency_created
        });

        // Поля для Telegram-уведомлений клиентов
        Schema::table('clients', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->after('email');
        });

        // Поля для брендинга агентства в Telegram
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('telegram_bot_token')->nullable()->after('settings');
            $table->string('telegram_bot_username')->nullable()->after('telegram_bot_token');
            $table->string('logo_url')->nullable()->after('telegram_bot_username');
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropIndex(['stage']);
            $table->dropIndex(['critical_date']);
            $table->dropIndex(['agency_id', 'stage']);
            $table->dropIndex(['agency_id', 'assigned_to']);
            $table->dropIndex(['agency_id', 'critical_date']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['case_id']);
            $table->dropIndex(['client_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn('telegram_chat_id');
        });

        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['telegram_bot_token', 'telegram_bot_username', 'logo_url']);
        });
    }
};
