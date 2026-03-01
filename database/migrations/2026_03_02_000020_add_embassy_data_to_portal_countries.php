<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_countries', function (Blueprint $table) {
            // Посольство / Визовый центр
            $table->string('embassy_website')->nullable()->after('flag_emoji');
            $table->string('appointment_url')->nullable()->after('embassy_website');
            $table->text('embassy_description')->nullable()->after('appointment_url');
            $table->text('embassy_rules')->nullable()->after('embassy_description');

            // Сроки обработки (дни)
            $table->unsignedSmallInteger('processing_days_standard')->nullable()->after('embassy_rules');
            $table->unsignedSmallInteger('processing_days_expedited')->nullable()->after('processing_days_standard');
            $table->unsignedSmallInteger('appointment_wait_days')->default(0)->after('processing_days_expedited');
            $table->unsignedSmallInteger('buffer_days_recommended')->default(14)->after('appointment_wait_days');

            // Комиссия VisaBor (%)
            $table->decimal('commission_rate', 5, 2)->default(5.00)->after('buffer_days_recommended');
        });
    }

    public function down(): void
    {
        Schema::table('portal_countries', function (Blueprint $table) {
            $table->dropColumn([
                'embassy_website', 'appointment_url', 'embassy_description', 'embassy_rules',
                'processing_days_standard', 'processing_days_expedited',
                'appointment_wait_days', 'buffer_days_recommended', 'commission_rate',
            ]);
        });
    }
};
