<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentRequirementsSeeder extends Seeder
{
    public function run(): void
    {
        // type = 'upload'   → клиент/менеджер загружает скан/файл
        // type = 'checkbox' → физический документ, просто отмечают "есть"

        $requirements = [
            // ----------------------------------------------------------------
            // Универсальные (для всех стран и типов виз)
            // ----------------------------------------------------------------
            ['country_code' => '*', 'visa_type' => '*', 'type' => 'upload',   'name' => 'Загранпаспорт',    'description' => 'Скан главного разворота + все страницы с визами. Срок действия не менее 6 месяцев после даты возврата.', 'is_required' => true, 'sort_order' => 1],
            ['country_code' => '*', 'visa_type' => '*', 'type' => 'upload',   'name' => 'Внутренний паспорт', 'description' => 'Скан всех заполненных страниц (прописка, семейное положение, дети).', 'is_required' => true, 'sort_order' => 2],
            ['country_code' => '*', 'visa_type' => '*', 'type' => 'checkbox', 'name' => 'Фото 3×4',          'description' => 'Цветное фото на белом фоне, не старше 6 месяцев, 2 штуки. Приносить на подачу.', 'is_required' => true, 'sort_order' => 3],
            ['country_code' => '*', 'visa_type' => '*', 'type' => 'checkbox', 'name' => 'Анкета-заявление',  'description' => 'Заполнить и подписать анкету по форме посольства. Менеджер выдаст бланк.', 'is_required' => true, 'sort_order' => 4],

            // ----------------------------------------------------------------
            // Германия — туристическая
            // ----------------------------------------------------------------
            ['country_code' => 'DE', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Бронирование отеля',       'description' => 'Подтверждение бронирования на весь период поездки (необязательно оплачивать).', 'is_required' => true,  'sort_order' => 5],
            ['country_code' => 'DE', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Авиабилеты (туда-обратно)', 'description' => 'Бронирование или оплаченные билеты.', 'is_required' => true,  'sort_order' => 6],
            ['country_code' => 'DE', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Выписка из банка',          'description' => 'За последние 3–6 месяцев. Остаток не менее €50/день поездки.', 'is_required' => true,  'sort_order' => 7],
            ['country_code' => 'DE', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Медицинская страховка',     'description' => 'Минимум €30 000, действует на всей территории Шенгена.', 'is_required' => true,  'sort_order' => 8],
            ['country_code' => 'DE', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Справка с работы / от ИП',  'description' => 'С указанием должности, оклада, даты трудоустройства. На бланке с печатью.', 'is_required' => true,  'sort_order' => 9],
            ['country_code' => 'DE', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Маршрутный лист',           'description' => 'Примерный план поездки по дням (города, даты).', 'is_required' => false, 'sort_order' => 10],

            // ----------------------------------------------------------------
            // Германия — деловая
            // ----------------------------------------------------------------
            ['country_code' => 'DE', 'visa_type' => 'business', 'type' => 'upload',   'name' => 'Приглашение от компании',         'description' => 'Официальное письмо от принимающей немецкой компании с целью и сроками визита.', 'is_required' => true,  'sort_order' => 5],
            ['country_code' => 'DE', 'visa_type' => 'business', 'type' => 'upload',   'name' => 'Выписка из банка',                 'description' => 'За последние 3 месяца.', 'is_required' => true,  'sort_order' => 6],
            ['country_code' => 'DE', 'visa_type' => 'business', 'type' => 'upload',   'name' => 'Медицинская страховка',            'description' => 'Минимум €30 000.', 'is_required' => true,  'sort_order' => 7],
            ['country_code' => 'DE', 'visa_type' => 'business', 'type' => 'upload',   'name' => 'Авиабилеты',                       'description' => 'Туда и обратно.', 'is_required' => true,  'sort_order' => 8],
            ['country_code' => 'DE', 'visa_type' => 'business', 'type' => 'upload',   'name' => 'Регистрационные документы компании', 'description' => 'Свидетельство о регистрации или справка из налоговой (отправителя).', 'is_required' => false, 'sort_order' => 9],

            // ----------------------------------------------------------------
            // Франция — туристическая
            // ----------------------------------------------------------------
            ['country_code' => 'FR', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Бронирование отеля',        'description' => 'Подтверждение проживания на весь период.', 'is_required' => true,  'sort_order' => 5],
            ['country_code' => 'FR', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Авиабилеты (туда-обратно)', 'description' => 'Бронирование или оплаченные билеты.', 'is_required' => true,  'sort_order' => 6],
            ['country_code' => 'FR', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Выписка из банка',           'description' => 'За последние 3 месяца.', 'is_required' => true,  'sort_order' => 7],
            ['country_code' => 'FR', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Медицинская страховка',      'description' => 'Минимум €30 000, зона Шенген.', 'is_required' => true,  'sort_order' => 8],
            ['country_code' => 'FR', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Справка с работы',           'description' => 'С печатью, указанием зарплаты и стажа.', 'is_required' => true,  'sort_order' => 9],

            // ----------------------------------------------------------------
            // Италия — туристическая
            // ----------------------------------------------------------------
            ['country_code' => 'IT', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Бронирование отеля',        'description' => 'Подтверждение проживания.', 'is_required' => true,  'sort_order' => 5],
            ['country_code' => 'IT', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Авиабилеты (туда-обратно)', 'description' => 'Билеты или бронирование.', 'is_required' => true,  'sort_order' => 6],
            ['country_code' => 'IT', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Выписка из банка',           'description' => 'За последние 3 месяца.', 'is_required' => true,  'sort_order' => 7],
            ['country_code' => 'IT', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Медицинская страховка',      'description' => 'Минимум €30 000, зона Шенген.', 'is_required' => true,  'sort_order' => 8],
            ['country_code' => 'IT', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Справка с работы',           'description' => 'Официальное письмо от работодателя.', 'is_required' => true,  'sort_order' => 9],

            // ----------------------------------------------------------------
            // США — туристическая (B1/B2)
            // ----------------------------------------------------------------
            ['country_code' => 'US', 'visa_type' => 'tourist', 'type' => 'checkbox', 'name' => 'Форма DS-160 (заполнена)',      'description' => 'Заполнить онлайн на сайте ceac.state.gov, распечатать страницу подтверждения.', 'is_required' => true,  'sort_order' => 5],
            ['country_code' => 'US', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Квитанция об оплате визового сбора', 'description' => 'Подтверждение оплаты $160 (MRV fee).', 'is_required' => true,  'sort_order' => 6],
            ['country_code' => 'US', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Выписка из банка',               'description' => 'За последние 6 месяцев. Должна показывать стабильный доход.', 'is_required' => true,  'sort_order' => 7],
            ['country_code' => 'US', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Справка с работы',               'description' => 'С указанием должности, оклада, разрешения на отпуск.', 'is_required' => true,  'sort_order' => 8],
            ['country_code' => 'US', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Документы на имущество',         'description' => 'Свидетельство о праве собственности (квартира, авто, земля).', 'is_required' => false, 'sort_order' => 9],

            // ----------------------------------------------------------------
            // ОАЭ — туристическая
            // ----------------------------------------------------------------
            ['country_code' => 'AE', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Бронирование отеля', 'description' => 'Подтверждение проживания в ОАЭ.', 'is_required' => true,  'sort_order' => 5],
            ['country_code' => 'AE', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Авиабилеты',         'description' => 'Билеты туда и обратно.', 'is_required' => true,  'sort_order' => 6],
            ['country_code' => 'AE', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Выписка из банка',   'description' => 'За последние 3 месяца.', 'is_required' => false, 'sort_order' => 7],

            // ----------------------------------------------------------------
            // Великобритания — туристическая
            // ----------------------------------------------------------------
            ['country_code' => 'GB', 'visa_type' => 'tourist', 'type' => 'checkbox', 'name' => 'Форма VAF1A (заполнена онлайн)', 'description' => 'Заполнить анкету на gov.uk/visas-immigration.', 'is_required' => true,  'sort_order' => 5],
            ['country_code' => 'GB', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Выписка из банка',               'description' => 'За последние 6 месяцев.', 'is_required' => true,  'sort_order' => 6],
            ['country_code' => 'GB', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Бронирование отеля',             'description' => 'Подтверждение размещения.', 'is_required' => true,  'sort_order' => 7],
            ['country_code' => 'GB', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Авиабилеты',                     'description' => 'Туда и обратно.', 'is_required' => true,  'sort_order' => 8],
            ['country_code' => 'GB', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Справка с работы',               'description' => 'С указанием должности, стажа, зарплаты.', 'is_required' => true,  'sort_order' => 9],

            // ----------------------------------------------------------------
            // Корея — туристическая
            // ----------------------------------------------------------------
            ['country_code' => 'KR', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Бронирование отеля',      'description' => 'Подтверждение проживания.', 'is_required' => true,  'sort_order' => 5],
            ['country_code' => 'KR', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Авиабилеты',              'description' => 'Туда и обратно.', 'is_required' => true,  'sort_order' => 6],
            ['country_code' => 'KR', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Выписка из банка',        'description' => 'За последние 3 месяца.', 'is_required' => true,  'sort_order' => 7],
            ['country_code' => 'KR', 'visa_type' => 'tourist', 'type' => 'upload',   'name' => 'Справка с работы или учёбы', 'description' => 'Официальный документ от работодателя или учебного заведения.', 'is_required' => true,  'sort_order' => 8],
        ];

        foreach ($requirements as $req) {
            DB::table('document_requirements')->upsert(
                array_merge($req, [
                    'id'         => (string) Str::uuid(),
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
                ['country_code', 'visa_type', 'name'],
                ['type', 'description', 'is_required', 'sort_order', 'updated_at']
            );
        }
    }
}
