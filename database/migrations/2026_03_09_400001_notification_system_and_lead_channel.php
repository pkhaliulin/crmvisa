<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Настройки уведомлений per-agency
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agency_id');
            $table->string('event_type', 100);
            $table->jsonb('channels')->default('["database"]');
            $table->jsonb('recipients')->default('["owner"]');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['agency_id', 'event_type']);
            $table->foreign('agency_id')->references('id')->on('agencies')->cascadeOnDelete();
        });

        // Привязка лида к каналу лидогенерации
        Schema::table('cases', function (Blueprint $table) {
            $table->string('lead_channel_code', 50)->nullable()->after('lead_source');
            $table->index('lead_channel_code');
        });

        // Подключённые каналы агентства
        Schema::create('agency_lead_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agency_id');
            $table->uuid('channel_id');
            $table->boolean('is_active')->default(true);
            $table->timestamp('connected_at')->useCurrent();
            $table->jsonb('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['agency_id', 'channel_id']);
            $table->foreign('agency_id')->references('id')->on('agencies')->cascadeOnDelete();
            $table->foreign('channel_id')->references('id')->on('lead_channels')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_lead_channels');
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn('lead_channel_code');
        });
        Schema::dropIfExists('notification_settings');
    }
};
