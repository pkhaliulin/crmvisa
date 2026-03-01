<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CountryVisaRequirementsSeeder extends Seeder
{
    public function run(): void
    {
        // Загружаем все шаблоны slug → id
        $templates = DB::table('document_templates')->pluck('id', 'slug');

        // requirement_level:
        //   'required'          → обязательный, без него подача невозможна
        //   'recommended'       → повышает шансы, рекомендуется
        //   'confirmation_only' → только галочка (фото сделано, форма заполнена)
        //
        // Формат: ['country_code', 'visa_type', 'template_slug', 'level', 'notes', 'override', 'order']

        $requirements = [
            // ================================================================
            // УНИВЕРСАЛЬНЫЕ — для всех стран и типов виз (* = любая)
            // ================================================================
            ['*',  '*',       'foreign_passport',         'required',           null, null, 1],
            ['*',  '*',       'internal_passport',        'required',           null, null, 2],
            ['*',  '*',       'photo_3x4',                'confirmation_only',  null, null, 3],
            ['*',  '*',       'application_form',         'confirmation_only',  null, null, 4],
            ['*',  '*',       'income_certificate',       'required',           null, null, 5],
            ['*',  '*',       'bank_balance_certificate', 'required',           null, null, 6],
            ['*',  '*',       'bank_statement',           'required',           null, null, 7],
            ['*',  '*',       'marriage_certificate',     'recommended',        null, null, 8],
            ['*',  '*',       'child_birth_certificate',  'recommended',        null, null, 9],
            ['*',  '*',       'property_certificate',     'recommended',        null, null, 10],
            ['*',  '*',       'car_registration',         'recommended',        null, null, 11],
            ['*',  '*',       'criminal_record',          'recommended',        null, null, 12],

            // ================================================================
            // ГЕРМАНИЯ — туристическая
            // ================================================================
            ['DE', 'tourist', 'hotel_booking',    'required',    'Подтверждение бронирования на весь период. Оплачивать не обязательно.', null, 20],
            ['DE', 'tourist', 'air_tickets',      'required',    null, null, 21],
            ['DE', 'tourist', 'travel_insurance', 'required',    'Минимум €30 000, действует на всей территории Шенгена.', ['min_coverage_eur' => 30000], 22],
            ['DE', 'tourist', 'itinerary',        'recommended', 'Примерный план поездки по дням (города, даты).', null, 23],

            // ================================================================
            // ГЕРМАНИЯ — деловая
            // ================================================================
            ['DE', 'business', 'business_invitation',  'required',    'Письмо от принимающей немецкой компании с целью и сроками визита.', null, 20],
            ['DE', 'business', 'travel_insurance',     'required',    'Минимум €30 000.', ['min_coverage_eur' => 30000], 21],
            ['DE', 'business', 'air_tickets',          'required',    null, null, 22],
            ['DE', 'business', 'company_registration', 'recommended', null, null, 23],

            // ================================================================
            // ИСПАНИЯ — туристическая
            // ================================================================
            ['ES', 'tourist', 'hotel_booking',           'required',    'Подтверждение проживания на весь период поездки.', null, 20],
            ['ES', 'tourist', 'air_tickets',             'required',    'Прямых рейсов из Узбекистана может не быть — допустима стыковка.', null, 21],
            ['ES', 'tourist', 'travel_insurance',        'required',    'Минимум €30 000, зона Шенген + Испания.', ['min_coverage_eur' => 30000], 22],
            ['ES', 'tourist', 'itinerary',               'recommended', 'Детальный план поездки по дням (города, достопримечательности).', null, 23],
            ['ES', 'tourist', 'cover_letter',            'recommended', 'Рекомендуется для первого шенгена: кто вы, цель, маршрут, почему вернётесь.', null, 24],
            ['ES', 'tourist', 'guarantor_letter',        'recommended', 'Нужно если другой человек (супруг/родственник) берёт расходы на себя.', null, 25],
            ['ES', 'tourist', 'guarantor_income',        'recommended', 'Нужно если путешествуете как иждивенец (супруг/ребёнок).', null, 26],
            ['ES', 'tourist', 'guarantor_bank_statement','recommended', 'Выписка счёта спонсора за 3–6 мес.', ['period_months' => 3], 27],

            // ================================================================
            // ФРАНЦИЯ — туристическая
            // ================================================================
            ['FR', 'tourist', 'hotel_booking',    'required', null, null, 20],
            ['FR', 'tourist', 'air_tickets',      'required', null, null, 21],
            ['FR', 'tourist', 'travel_insurance', 'required', 'Минимум €30 000, зона Шенген.', ['min_coverage_eur' => 30000], 22],

            // ================================================================
            // ИТАЛИЯ — туристическая
            // ================================================================
            ['IT', 'tourist', 'hotel_booking',    'required', null, null, 20],
            ['IT', 'tourist', 'air_tickets',      'required', null, null, 21],
            ['IT', 'tourist', 'travel_insurance', 'required', 'Минимум €30 000, зона Шенген.', ['min_coverage_eur' => 30000], 22],

            // ================================================================
            // США — туристическая (B1/B2)
            // ================================================================
            ['US', 'tourist', 'ds160_form',         'confirmation_only', 'Заполнить онлайн на ceac.state.gov, распечатать страницу с штрих-кодом.', null, 20],
            ['US', 'tourist', 'visa_fee_receipt',   'required',          'Подтверждение оплаты $160 (MRV fee).', null, 21],
            ['US', 'tourist', 'property_documents', 'recommended',       null, null, 22],
            ['US', 'tourist', 'hotel_booking',      'recommended',       'Примерный план поездки, отель (не обязателен, но повышает шансы).', null, 23],

            // ================================================================
            // ОАЭ — туристическая
            // ================================================================
            ['AE', 'tourist', 'hotel_booking', 'required', 'Подтверждение проживания в ОАЭ.', null, 20],
            ['AE', 'tourist', 'air_tickets',   'required', null, null, 21],

            // ================================================================
            // ВЕЛИКОБРИТАНИЯ — туристическая
            // ================================================================
            ['GB', 'tourist', 'vaf1a_form',      'confirmation_only', 'Заполнить на gov.uk/visas-immigration, распечатать подтверждение.', null, 20],
            ['GB', 'tourist', 'hotel_booking',   'required',          null, null, 21],
            ['GB', 'tourist', 'air_tickets',     'required',          null, null, 22],
            ['GB', 'tourist', 'travel_insurance','required',          'Покрытие для посещения Великобритании.', null, 23],

            // ================================================================
            // КОРЕЯ — туристическая
            // ================================================================
            ['KR', 'tourist', 'hotel_booking',          'required', null, null, 20],
            ['KR', 'tourist', 'air_tickets',            'required', null, null, 21],
            ['KR', 'tourist', 'travel_insurance',       'required', 'Для въезда в Корею.', null, 22],
            ['KR', 'tourist', 'employment_certificate', 'required', 'Официальный документ от работодателя или учебного заведения.', null, 23],
        ];

        foreach ($requirements as [$countryCode, $visaType, $slug, $level, $notes, $override, $order]) {
            $templateId = $templates[$slug] ?? null;

            if (! $templateId) {
                $this->command->warn("Шаблон не найден: {$slug}");
                continue;
            }

            DB::table('country_visa_requirements')->upsert(
                [
                    'id'                   => (string) Str::uuid(),
                    'country_code'         => $countryCode,
                    'visa_type'            => $visaType,
                    'document_template_id' => $templateId,
                    'requirement_level'    => $level,
                    'notes'                => $notes,
                    'override_metadata'    => $override ? json_encode($override) : null,
                    'display_order'        => $order,
                    'is_active'            => true,
                    'effective_from'       => null,
                    'effective_to'         => null,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ],
                ['country_code', 'visa_type', 'document_template_id'],
                ['requirement_level', 'notes', 'override_metadata', 'display_order', 'updated_at']
            );
        }
    }
}
