<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentRequirementsSeeder extends Seeder
{
    public function run(): void
    {
        // type:          'upload'   → загружают файл (скан)
        //                'checkbox' → физический / онлайн документ, просто отмечают «готово»
        // is_required:   true  → обязательный
        //                false → опциональный (если есть)
        // is_repeatable: true  → можно добавить несколько (напр. метрики детей)

        $requirements = [

            // ================================================================
            // УНИВЕРСАЛЬНЫЕ — для всех стран и типов виз (* = любая)
            // ================================================================

            // --- Документы удостоверения личности ---
            ['country_code' => '*', 'visa_type' => '*', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Загранпаспорт',      'description' => 'Скан главного разворота + все страницы с визами и штампами. Срок действия — не менее 3–6 мес. после даты возврата.',  'sort_order' => 1],
            ['country_code' => '*', 'visa_type' => '*', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Внутренний паспорт', 'description' => 'Скан всех заполненных страниц: прописка, семейное положение, дети.',                                                       'sort_order' => 2],
            ['country_code' => '*', 'visa_type' => '*', 'type' => 'checkbox', 'is_required' => true,  'is_repeatable' => false, 'name' => 'Фото 3×4',           'description' => 'Цветное, на белом фоне, не старше 6 мес., 2 штуки. Приносить на подачу.',                                                  'sort_order' => 3],
            ['country_code' => '*', 'visa_type' => '*', 'type' => 'checkbox', 'is_required' => true,  'is_repeatable' => false, 'name' => 'Анкета-заявление',   'description' => 'Заполнить и подписать анкету по форме посольства. Менеджер выдаст бланк.',                                                 'sort_order' => 4],

            // --- Финансовые документы ---
            ['country_code' => '*', 'visa_type' => '*', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Справка о доходах',                   'description' => 'Справка с места работы с указанием должности, оклада и стажа (не старше 1 мес.). Для ИП — налоговая декларация.',       'sort_order' => 5],
            ['country_code' => '*', 'visa_type' => '*', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Справка об остатке на счёте',          'description' => 'Официальная справка из банка с текущим остатком на счёте. Заверенная печатью банка, не старше 1 мес.',               'sort_order' => 6],
            ['country_code' => '*', 'visa_type' => '*', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Выписка по карте (оборот за 3–6 мес.)', 'description' => 'Выписка транзакций по банковской карте за последние 3–6 месяцев. Показывает регулярные доходы и расходы.',         'sort_order' => 7],

            // --- Семейное положение ---
            ['country_code' => '*', 'visa_type' => '*', 'type' => 'upload',   'is_required' => false, 'is_repeatable' => false, 'name' => 'Свидетельство о браке',          'description' => 'Скан + нотариально заверенный перевод (если документ не на русском языке).',                                    'sort_order' => 8],
            ['country_code' => '*', 'visa_type' => '*', 'type' => 'upload',   'is_required' => false, 'is_repeatable' => true,  'name' => 'Метрика ребёнка',                'description' => 'Свидетельство о рождении ребёнка + перевод. Добавьте отдельно для каждого ребёнка.',                              'sort_order' => 9],

            // --- Имущество и активы ---
            ['country_code' => '*', 'visa_type' => '*', 'type' => 'upload',   'is_required' => false, 'is_repeatable' => false, 'name' => 'Выписка о недвижимости (mygov.uz)', 'description' => 'Выписка из Государственного реестра недвижимости через портал my.gov.uz — подтверждение права собственности.', 'sort_order' => 10],
            ['country_code' => '*', 'visa_type' => '*', 'type' => 'upload',   'is_required' => false, 'is_repeatable' => false, 'name' => 'Копия техпаспорта автомобиля',      'description' => 'Свидетельство о регистрации транспортного средства (если есть авто).',                                         'sort_order' => 11],

            // --- Прочее ---
            ['country_code' => '*', 'visa_type' => '*', 'type' => 'upload',   'is_required' => false, 'is_repeatable' => false, 'name' => 'Справка о несудимости', 'description' => 'Официальная справка из МВД/суда об отсутствии судимости.',  'sort_order' => 12],

            // ================================================================
            // ГЕРМАНИЯ — туристическая
            // ================================================================
            ['country_code' => 'DE', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Бронирование отеля',        'description' => 'Подтверждение бронирования на весь период. Оплачивать не обязательно.',                                     'sort_order' => 20],
            ['country_code' => 'DE', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Авиабилеты (туда-обратно)', 'description' => 'Бронирование или оплаченные билеты.',                                                                           'sort_order' => 21],
            ['country_code' => 'DE', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Медицинская страховка',      'description' => 'Минимум €30 000, действует на всей территории Шенгена.',                                                        'sort_order' => 22],
            ['country_code' => 'DE', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => false, 'is_repeatable' => false, 'name' => 'Маршрутный лист',            'description' => 'Примерный план поездки по дням (города, даты).',                                                               'sort_order' => 23],

            // ================================================================
            // ГЕРМАНИЯ — деловая
            // ================================================================
            ['country_code' => 'DE', 'visa_type' => 'business', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Приглашение от компании',           'description' => 'Письмо от принимающей немецкой компании с целью и сроками визита.',  'sort_order' => 20],
            ['country_code' => 'DE', 'visa_type' => 'business', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Медицинская страховка',               'description' => 'Минимум €30 000.',                                                   'sort_order' => 21],
            ['country_code' => 'DE', 'visa_type' => 'business', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Авиабилеты',                          'description' => 'Туда и обратно.',                                                    'sort_order' => 22],
            ['country_code' => 'DE', 'visa_type' => 'business', 'type' => 'upload',   'is_required' => false, 'is_repeatable' => false, 'name' => 'Регистрационные документы компании',  'description' => 'Свидетельство о регистрации или справка из налоговой.',             'sort_order' => 23],

            // ================================================================
            // ИСПАНИЯ — туристическая (самый большой список)
            // ================================================================
            ['country_code' => 'ES', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Бронирование отеля / жилья',       'description' => 'Подтверждение проживания на весь период поездки.',                                                                                         'sort_order' => 20],
            ['country_code' => 'ES', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Авиабилеты (туда-обратно)',        'description' => 'Бронирование перелётов. Прямых рейсов из Узбекистана может не быть — допустима стыковка.',                                                 'sort_order' => 21],
            ['country_code' => 'ES', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Медицинская страховка',            'description' => 'Минимум €30 000, зона Шенген + Испания.',                                                                                                  'sort_order' => 22],
            ['country_code' => 'ES', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => false, 'is_repeatable' => false, 'name' => 'Маршрутный лист',                  'description' => 'Детальный план поездки по дням (города, достопримечательности, активности).',                                                             'sort_order' => 23],
            ['country_code' => 'ES', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => false, 'is_repeatable' => false, 'name' => 'Сопроводительное письмо (эссе)',   'description' => 'Короткое письмо: кто вы, цель визита, маршрут, почему вернётесь домой. Рекомендуется для первого шенгена.',                               'sort_order' => 24],
            ['country_code' => 'ES', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => false, 'is_repeatable' => false, 'name' => 'Письмо финансового гаранта',       'description' => 'Если другой человек (супруг/родственник) берёт на себя расходы — его письмо-гарантия + его справка о доходах + выписка из банка.',       'sort_order' => 25],
            ['country_code' => 'ES', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => false, 'is_repeatable' => false, 'name' => 'Справка о доходах гаранта',        'description' => 'Справка с работы финансового спонсора. Нужна если вы путешествуете как иждивенец (супруг/ребёнок).',                                     'sort_order' => 26],
            ['country_code' => 'ES', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => false, 'is_repeatable' => false, 'name' => 'Выписка из банка гаранта',         'description' => 'Выписка банковского счёта спонсора за 3–6 мес. Доказывает, что гарант способен покрыть расходы.',                                        'sort_order' => 27],

            // ================================================================
            // ФРАНЦИЯ — туристическая
            // ================================================================
            ['country_code' => 'FR', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Бронирование отеля',        'description' => 'Подтверждение проживания на весь период.',                     'sort_order' => 20],
            ['country_code' => 'FR', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Авиабилеты (туда-обратно)', 'description' => 'Бронирование или оплаченные билеты.',                          'sort_order' => 21],
            ['country_code' => 'FR', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Медицинская страховка',      'description' => 'Минимум €30 000, зона Шенген.',                                'sort_order' => 22],

            // ================================================================
            // ИТАЛИЯ — туристическая
            // ================================================================
            ['country_code' => 'IT', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Бронирование отеля',        'description' => 'Подтверждение проживания.',              'sort_order' => 20],
            ['country_code' => 'IT', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Авиабилеты (туда-обратно)', 'description' => 'Билеты или бронирование.',               'sort_order' => 21],
            ['country_code' => 'IT', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Медицинская страховка',      'description' => 'Минимум €30 000, зона Шенген.',          'sort_order' => 22],

            // ================================================================
            // США — туристическая (B1/B2)
            // ================================================================
            ['country_code' => 'US', 'visa_type' => 'tourist', 'type' => 'checkbox', 'is_required' => true,  'is_repeatable' => false, 'name' => 'Форма DS-160 (заполнена)',           'description' => 'Заполнить онлайн на ceac.state.gov, распечатать страницу подтверждения с штрих-кодом.',     'sort_order' => 20],
            ['country_code' => 'US', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Квитанция об оплате визового сбора', 'description' => 'Подтверждение оплаты $160 (MRV fee).',                                                        'sort_order' => 21],
            ['country_code' => 'US', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => false, 'is_repeatable' => false, 'name' => 'Документы на недвижимость',          'description' => 'Свидетельство о праве собственности (квартира, дом, земля).',                                'sort_order' => 22],
            ['country_code' => 'US', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => false, 'is_repeatable' => false, 'name' => 'Бронирование отеля / маршрут',       'description' => 'Примерный план поездки, отель.',                                                             'sort_order' => 23],

            // ================================================================
            // ОАЭ — туристическая
            // ================================================================
            ['country_code' => 'AE', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Бронирование отеля', 'description' => 'Подтверждение проживания в ОАЭ.', 'sort_order' => 20],
            ['country_code' => 'AE', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Авиабилеты',         'description' => 'Билеты туда и обратно.',          'sort_order' => 21],

            // ================================================================
            // ВЕЛИКОБРИТАНИЯ — туристическая
            // ================================================================
            ['country_code' => 'GB', 'visa_type' => 'tourist', 'type' => 'checkbox', 'is_required' => true,  'is_repeatable' => false, 'name' => 'Анкета VAF1A (заполнена онлайн)', 'description' => 'Заполнить на gov.uk/visas-immigration, распечатать подтверждение.', 'sort_order' => 20],
            ['country_code' => 'GB', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Бронирование отеля',              'description' => 'Подтверждение размещения.',                                          'sort_order' => 21],
            ['country_code' => 'GB', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Авиабилеты',                      'description' => 'Туда и обратно.',                                                    'sort_order' => 22],
            ['country_code' => 'GB', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Медицинская страховка',           'description' => 'Покрытие для посещения Великобритании.',                             'sort_order' => 23],

            // ================================================================
            // КОРЕЯ — туристическая
            // ================================================================
            ['country_code' => 'KR', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Бронирование отеля',         'description' => 'Подтверждение проживания.', 'sort_order' => 20],
            ['country_code' => 'KR', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Авиабилеты',                 'description' => 'Туда и обратно.',           'sort_order' => 21],
            ['country_code' => 'KR', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Медицинская страховка',       'description' => 'Для въезда в Корею.',       'sort_order' => 22],
            ['country_code' => 'KR', 'visa_type' => 'tourist', 'type' => 'upload',   'is_required' => true,  'is_repeatable' => false, 'name' => 'Справка с работы или учёбы', 'description' => 'Официальный документ от работодателя или учебного заведения.', 'sort_order' => 23],
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
                ['type', 'description', 'is_required', 'is_repeatable', 'sort_order', 'updated_at']
            );
        }
    }
}
