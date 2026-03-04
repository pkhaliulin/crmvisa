<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_countries', function (Blueprint $table) {
            // Визовый режим
            $table->string('visa_regime', 20)->default('visa_required')->after('is_active');
            $table->smallInteger('visa_free_days')->nullable()->after('visa_regime');
            $table->smallInteger('visa_on_arrival_days')->nullable()->after('visa_free_days');
            $table->boolean('evisa_available')->default(false)->after('visa_on_arrival_days');
            $table->string('evisa_url', 500)->nullable()->after('evisa_available');
            $table->smallInteger('evisa_processing_days')->nullable()->after('evisa_url');

            // Требования
            $table->boolean('invitation_required')->default(false)->after('evisa_processing_days');
            $table->boolean('hotel_booking_required')->default(false)->after('invitation_required');
            $table->boolean('insurance_required')->default(false)->after('hotel_booking_required');
            $table->boolean('bank_statement_required')->default(true)->after('insurance_required');
            $table->boolean('return_ticket_required')->default(false)->after('bank_statement_required');

            // Стоимости (USD)
            $table->decimal('visa_fee_usd', 8, 2)->nullable()->after('return_ticket_required');
            $table->decimal('evisa_fee_usd', 8, 2)->nullable()->after('visa_fee_usd');
            $table->unsignedInteger('avg_flight_cost_usd')->nullable()->after('evisa_fee_usd');
            $table->unsignedInteger('avg_hotel_per_night_usd')->nullable()->after('avg_flight_cost_usd');

            // Аналитика
            $table->unsignedInteger('view_count')->default(0)->after('avg_hotel_per_night_usd');
            $table->unsignedInteger('lead_count')->default(0)->after('view_count');
            $table->unsignedInteger('case_count')->default(0)->after('lead_count');

            // Флаги
            $table->boolean('is_popular')->default(false)->after('case_count');
            $table->boolean('is_high_approval')->default(false)->after('is_popular');
            $table->boolean('is_high_refusal')->default(false)->after('is_high_approval');

            // Доп. информация
            $table->text('notes')->nullable()->after('is_high_refusal');
            $table->string('continent', 30)->nullable()->after('notes');

            // Индексы
            $table->index('visa_regime');
            $table->index('continent');
            $table->index('is_popular');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('portal_countries', function (Blueprint $table) {
            $table->dropIndex(['visa_regime']);
            $table->dropIndex(['continent']);
            $table->dropIndex(['is_popular']);
            $table->dropIndex(['is_active']);

            $table->dropColumn([
                'visa_regime', 'visa_free_days', 'visa_on_arrival_days',
                'evisa_available', 'evisa_url', 'evisa_processing_days',
                'invitation_required', 'hotel_booking_required', 'insurance_required',
                'bank_statement_required', 'return_ticket_required',
                'visa_fee_usd', 'evisa_fee_usd', 'avg_flight_cost_usd', 'avg_hotel_per_night_usd',
                'view_count', 'lead_count', 'case_count',
                'is_popular', 'is_high_approval', 'is_high_refusal',
                'notes', 'continent',
            ]);
        });
    }
};
