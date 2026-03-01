<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentRequirementsSeeder extends Seeder
{
    public function run(): void
    {
        $requirements = [
            // ----------------------------------------------------------------
            // Универсальные (для всех стран и типов виз)
            // ----------------------------------------------------------------
            ['country_code' => '*', 'visa_type' => '*', 'name' => 'Загранпаспорт', 'description' => 'Оригинал + копия всех страниц. Срок действия не менее 6 месяцев после даты возврата.', 'is_required' => true, 'sort_order' => 1],
            ['country_code' => '*', 'visa_type' => '*', 'name' => 'Фото 3×4', 'description' => 'Цветное фото на белом фоне, не старше 6 месяцев, 2 штуки.', 'is_required' => true, 'sort_order' => 2],
            ['country_code' => '*', 'visa_type' => '*', 'name' => 'Внутренний паспорт', 'description' => 'Копия всех заполненных страниц.', 'is_required' => true, 'sort_order' => 3],
            ['country_code' => '*', 'visa_type' => '*', 'name' => 'Анкета-заявление', 'description' => 'Заполненная и подписанная анкета по форме посольства.', 'is_required' => true, 'sort_order' => 4],

            // ----------------------------------------------------------------
            // Шенген — туристическая (DE, FR, IT, ES, CZ, PL и др.)
            // ----------------------------------------------------------------
            ['country_code' => 'DE', 'visa_type' => 'tourist', 'name' => 'Бронирование отеля', 'description' => 'Подтверждение бронирования гостиницы на весь период поездки.', 'is_required' => true, 'sort_order' => 5],
            ['country_code' => 'DE', 'visa_type' => 'tourist', 'name' => 'Авиабилеты (туда-обратно)', 'description' => 'Бронирование или оплаченные билеты.', 'is_required' => true, 'sort_order' => 6],
            ['country_code' => 'DE', 'visa_type' => 'tourist', 'name' => 'Выписка из банка', 'description' => 'За последние 3-6 месяцев. Остаток не менее €50/день поездки.', 'is_required' => true, 'sort_order' => 7],
            ['country_code' => 'DE', 'visa_type' => 'tourist', 'name' => 'Медицинская страховка', 'description' => 'Минимум €30 000, действует на всей территории Шенгена.', 'is_required' => true, 'sort_order' => 8],
            ['country_code' => 'DE', 'visa_type' => 'tourist', 'name' => 'Справка с работы / ИП', 'description' => 'С указанием должности, оклада, даты трудоустройства. На бланке организации с печатью.', 'is_required' => true, 'sort_order' => 9],
            ['country_code' => 'DE', 'visa_type' => 'tourist', 'name' => 'Маршрутный лист', 'description' => 'Примерный план поездки по дням.', 'is_required' => false, 'sort_order' => 10],

            ['country_code' => 'FR', 'visa_type' => 'tourist', 'name' => 'Бронирование отеля', 'description' => 'Подтверждение бронирования на весь период.', 'is_required' => true, 'sort_order' => 5],
            ['country_code' => 'FR', 'visa_type' => 'tourist', 'name' => 'Авиабилеты (туда-обратно)', 'description' => 'Бронирование или оплаченные билеты.', 'is_required' => true, 'sort_order' => 6],
            ['country_code' => 'FR', 'visa_type' => 'tourist', 'name' => 'Выписка из банка', 'description' => 'За последние 3 месяца.', 'is_required' => true, 'sort_order' => 7],
            ['country_code' => 'FR', 'visa_type' => 'tourist', 'name' => 'Медицинская страховка', 'description' => 'Минимум €30 000, зона Шенген.', 'is_required' => true, 'sort_order' => 8],
            ['country_code' => 'FR', 'visa_type' => 'tourist', 'name' => 'Справка с работы', 'description' => 'С печатью, указанием зарплаты и стажа.', 'is_required' => true, 'sort_order' => 9],

            ['country_code' => 'IT', 'visa_type' => 'tourist', 'name' => 'Бронирование отеля', 'description' => 'Подтверждение проживания.', 'is_required' => true, 'sort_order' => 5],
            ['country_code' => 'IT', 'visa_type' => 'tourist', 'name' => 'Авиабилеты (туда-обратно)', 'description' => 'Билеты или бронирование.', 'is_required' => true, 'sort_order' => 6],
            ['country_code' => 'IT', 'visa_type' => 'tourist', 'name' => 'Выписка из банка', 'description' => 'За последние 3 месяца.', 'is_required' => true, 'sort_order' => 7],
            ['country_code' => 'IT', 'visa_type' => 'tourist', 'name' => 'Медицинская страховка', 'description' => 'Минимум €30 000, зона Шенген.', 'is_required' => true, 'sort_order' => 8],
            ['country_code' => 'IT', 'visa_type' => 'tourist', 'name' => 'Справка с работы', 'description' => 'Официальное письмо от работодателя.', 'is_required' => true, 'sort_order' => 9],

            // ----------------------------------------------------------------
            // Шенген — деловая
            // ----------------------------------------------------------------
            ['country_code' => 'DE', 'visa_type' => 'business', 'name' => 'Приглашение от компании', 'description' => 'Официальное письмо от принимающей немецкой компании с указанием цели и сроков визита.', 'is_required' => true, 'sort_order' => 5],
            ['country_code' => 'DE', 'visa_type' => 'business', 'name' => 'Выписка из банка', 'description' => 'За последние 3 месяца.', 'is_required' => true, 'sort_order' => 6],
            ['country_code' => 'DE', 'visa_type' => 'business', 'name' => 'Медицинская страховка', 'description' => 'Минимум €30 000.', 'is_required' => true, 'sort_order' => 7],
            ['country_code' => 'DE', 'visa_type' => 'business', 'name' => 'Регистрационные документы компании', 'description' => 'Свидетельство о регистрации, устав или справка из налоговой.', 'is_required' => false, 'sort_order' => 8],
            ['country_code' => 'DE', 'visa_type' => 'business', 'name' => 'Авиабилеты', 'description' => 'Туда и обратно.', 'is_required' => true, 'sort_order' => 9],

            // ----------------------------------------------------------------
            // США
            // ----------------------------------------------------------------
            ['country_code' => 'US', 'visa_type' => 'tourist', 'name' => 'Форма DS-160', 'description' => 'Заполненная онлайн-анкета на сайте consular.state.gov', 'is_required' => true, 'sort_order' => 5],
            ['country_code' => 'US', 'visa_type' => 'tourist', 'name' => 'Квитанция об оплате визового сбора', 'description' => 'Подтверждение оплаты $160.', 'is_required' => true, 'sort_order' => 6],
            ['country_code' => 'US', 'visa_type' => 'tourist', 'name' => 'Выписка из банка', 'description' => 'За последние 6 месяцев. Должна показывать стабильный доход.', 'is_required' => true, 'sort_order' => 7],
            ['country_code' => 'US', 'visa_type' => 'tourist', 'name' => 'Справка с работы', 'description' => 'С указанием должности, оклада, разрешения на отпуск.', 'is_required' => true, 'sort_order' => 8],
            ['country_code' => 'US', 'visa_type' => 'tourist', 'name' => 'Документы на имущество', 'description' => 'Свидетельство о праве собственности (квартира, авто, земля).', 'is_required' => false, 'sort_order' => 9],
            ['country_code' => 'US', 'visa_type' => 'tourist', 'name' => 'Подтверждение маршрута', 'description' => 'Бронирование отеля, маршрут поездки.', 'is_required' => false, 'sort_order' => 10],

            // ----------------------------------------------------------------
            // ОАЭ
            // ----------------------------------------------------------------
            ['country_code' => 'AE', 'visa_type' => 'tourist', 'name' => 'Бронирование отеля', 'description' => 'Подтверждение проживания в ОАЭ.', 'is_required' => true, 'sort_order' => 5],
            ['country_code' => 'AE', 'visa_type' => 'tourist', 'name' => 'Авиабилеты', 'description' => 'Билеты туда и обратно.', 'is_required' => true, 'sort_order' => 6],
            ['country_code' => 'AE', 'visa_type' => 'tourist', 'name' => 'Выписка из банка', 'description' => 'За последние 3 месяца.', 'is_required' => false, 'sort_order' => 7],

            // ----------------------------------------------------------------
            // Великобритания
            // ----------------------------------------------------------------
            ['country_code' => 'GB', 'visa_type' => 'tourist', 'name' => 'Форма VAF1A (онлайн)', 'description' => 'Заполненная онлайн-анкета на gov.uk', 'is_required' => true, 'sort_order' => 5],
            ['country_code' => 'GB', 'visa_type' => 'tourist', 'name' => 'Выписка из банка', 'description' => 'За последние 6 месяцев.', 'is_required' => true, 'sort_order' => 6],
            ['country_code' => 'GB', 'visa_type' => 'tourist', 'name' => 'Бронирование отеля', 'description' => 'Подтверждение размещения.', 'is_required' => true, 'sort_order' => 7],
            ['country_code' => 'GB', 'visa_type' => 'tourist', 'name' => 'Авиабилеты', 'description' => 'Туда и обратно.', 'is_required' => true, 'sort_order' => 8],
            ['country_code' => 'GB', 'visa_type' => 'tourist', 'name' => 'Справка с работы', 'description' => 'С указанием должности, стажа, зарплаты.', 'is_required' => true, 'sort_order' => 9],

            // ----------------------------------------------------------------
            // Корея
            // ----------------------------------------------------------------
            ['country_code' => 'KR', 'visa_type' => 'tourist', 'name' => 'Бронирование отеля', 'description' => 'Подтверждение проживания.', 'is_required' => true, 'sort_order' => 5],
            ['country_code' => 'KR', 'visa_type' => 'tourist', 'name' => 'Авиабилеты', 'description' => 'Туда и обратно.', 'is_required' => true, 'sort_order' => 6],
            ['country_code' => 'KR', 'visa_type' => 'tourist', 'name' => 'Выписка из банка', 'description' => 'За последние 3 месяца.', 'is_required' => true, 'sort_order' => 7],
            ['country_code' => 'KR', 'visa_type' => 'tourist', 'name' => 'Справка с работы или учёбы', 'description' => 'Официальный документ от работодателя или учебного заведения.', 'is_required' => true, 'sort_order' => 8],
        ];

        foreach ($requirements as $req) {
            DB::table('document_requirements')->upsert(
                array_merge($req, [
                    'id'         => (string) Str::uuid(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
                ['country_code', 'visa_type', 'name'],
                ['description', 'is_required', 'sort_order', 'updated_at']
            );
        }
    }
}
