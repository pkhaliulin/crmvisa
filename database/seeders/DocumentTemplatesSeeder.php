<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        // Глобальный каталог документов — каждый документ создаётся ОДИН РАЗ.
        // Привязка к странам — в CountryVisaRequirementsSeeder.
        //
        // type:          'upload'   → загружают скан
        //                'checkbox' → физический / онлайн документ, просто отмечают «готово»
        // is_repeatable: true → можно добавить несколько экземпляров (напр. метрики детей)
        // metadata_schema: JSON-схема параметров (min_balance_usd, period_months, min_coverage_eur)

        $templates = [
            // ----------------------------------------------------------------
            // ЛИЧНЫЕ ДОКУМЕНТЫ
            // ----------------------------------------------------------------
            [
                'slug'            => 'foreign_passport',
                'name'            => 'Загранпаспорт',
                'category'        => 'personal',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 1,
                'description'     => 'Скан главного разворота + все страницы с визами и штампами. Срок действия — не менее 3–6 мес. после даты возврата.',
                'metadata_schema' => null,
            ],
            [
                'slug'            => 'internal_passport',
                'name'            => 'Внутренний паспорт',
                'category'        => 'personal',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 2,
                'description'     => 'Скан всех заполненных страниц: прописка, семейное положение, дети.',
                'metadata_schema' => null,
            ],
            [
                'slug'            => 'photo_3x4',
                'name'            => 'Фото 3×4',
                'category'        => 'confirmation',
                'type'            => 'checkbox',
                'is_repeatable'   => false,
                'sort_order'      => 3,
                'description'     => 'Цветное, на белом фоне, не старше 6 мес., 2 штуки. Приносить на подачу лично.',
                'metadata_schema' => null,
            ],
            [
                'slug'            => 'application_form',
                'name'            => 'Анкета-заявление',
                'category'        => 'confirmation',
                'type'            => 'checkbox',
                'is_repeatable'   => false,
                'sort_order'      => 4,
                'description'     => 'Заполнить и подписать анкету по форме посольства. Менеджер выдаст бланк.',
                'metadata_schema' => null,
            ],
            [
                'slug'            => 'criminal_record',
                'name'            => 'Справка о несудимости',
                'category'        => 'personal',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 12,
                'description'     => 'Официальная справка из МВД/суда об отсутствии судимости.',
                'metadata_schema' => null,
            ],

            // ----------------------------------------------------------------
            // ФИНАНСОВЫЕ ДОКУМЕНТЫ
            // ----------------------------------------------------------------
            [
                'slug'            => 'income_certificate',
                'name'            => 'Справка о доходах',
                'category'        => 'financial',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 5,
                'description'     => 'Справка с места работы с указанием должности, оклада и стажа (не старше 1 мес.). Для ИП — налоговая декларация.',
                'metadata_schema' => null,
            ],
            [
                'slug'            => 'bank_balance_certificate',
                'name'            => 'Справка об остатке на счёте',
                'category'        => 'financial',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 6,
                'description'     => 'Официальная справка из банка с текущим остатком на счёте. Заверенная печатью банка, не старше 1 мес.',
                'metadata_schema' => ['min_balance_usd' => 500],
            ],
            [
                'slug'            => 'bank_statement',
                'name'            => 'Выписка по карте (оборот за 3–6 мес.)',
                'category'        => 'financial',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 7,
                'description'     => 'Выписка транзакций по банковской карте за последние 3–6 месяцев. Показывает регулярные доходы и расходы.',
                'metadata_schema' => ['period_months' => 3],
            ],
            [
                'slug'            => 'guarantor_letter',
                'name'            => 'Письмо финансового гаранта',
                'category'        => 'financial',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 30,
                'description'     => 'Если другой человек (супруг/родственник) берёт на себя расходы — его письмо-гарантия.',
                'metadata_schema' => null,
            ],
            [
                'slug'            => 'guarantor_income',
                'name'            => 'Справка о доходах гаранта',
                'category'        => 'financial',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 31,
                'description'     => 'Справка с работы финансового спонсора. Нужна если путешествуете как иждивенец.',
                'metadata_schema' => null,
            ],
            [
                'slug'            => 'guarantor_bank_statement',
                'name'            => 'Выписка из банка гаранта',
                'category'        => 'financial',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 32,
                'description'     => 'Выписка банковского счёта спонсора за 3–6 мес. Доказывает, что гарант способен покрыть расходы.',
                'metadata_schema' => ['period_months' => 3],
            ],
            [
                'slug'            => 'visa_fee_receipt',
                'name'            => 'Квитанция об оплате визового сбора',
                'category'        => 'financial',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 33,
                'description'     => 'Подтверждение оплаты визового сбора (консульский сбор).',
                'metadata_schema' => null,
            ],

            // ----------------------------------------------------------------
            // СЕМЕЙНЫЕ ДОКУМЕНТЫ
            // ----------------------------------------------------------------
            [
                'slug'            => 'marriage_certificate',
                'name'            => 'Свидетельство о браке',
                'category'        => 'family',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 8,
                'description'     => 'Скан + нотариально заверенный перевод (если документ не на русском языке).',
                'metadata_schema' => null,
            ],
            [
                'slug'            => 'child_birth_certificate',
                'name'            => 'Метрика ребёнка',
                'category'        => 'family',
                'type'            => 'upload',
                'is_repeatable'   => true,
                'sort_order'      => 9,
                'description'     => 'Свидетельство о рождении ребёнка + перевод. Добавьте отдельно для каждого ребёнка.',
                'metadata_schema' => null,
            ],

            // ----------------------------------------------------------------
            // ИМУЩЕСТВО И АКТИВЫ
            // ----------------------------------------------------------------
            [
                'slug'            => 'property_certificate',
                'name'            => 'Выписка о недвижимости (mygov.uz)',
                'category'        => 'property',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 10,
                'description'     => 'Выписка из Государственного реестра недвижимости через портал my.gov.uz — подтверждение права собственности.',
                'metadata_schema' => null,
            ],
            [
                'slug'            => 'car_registration',
                'name'            => 'Копия техпаспорта автомобиля',
                'category'        => 'property',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 11,
                'description'     => 'Свидетельство о регистрации транспортного средства (если есть авто).',
                'metadata_schema' => null,
            ],
            [
                'slug'            => 'property_documents',
                'name'            => 'Документы на недвижимость',
                'category'        => 'property',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 34,
                'description'     => 'Свидетельство о праве собственности (квартира, дом, земля).',
                'metadata_schema' => null,
            ],

            // ----------------------------------------------------------------
            // ТУРИСТИЧЕСКИЕ ДОКУМЕНТЫ
            // ----------------------------------------------------------------
            [
                'slug'            => 'hotel_booking',
                'name'            => 'Бронирование отеля',
                'category'        => 'travel',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 20,
                'description'     => 'Подтверждение бронирования на весь период. Оплачивать не обязательно.',
                'metadata_schema' => null,
            ],
            [
                'slug'            => 'air_tickets',
                'name'            => 'Авиабилеты (туда-обратно)',
                'category'        => 'travel',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 21,
                'description'     => 'Бронирование или оплаченные билеты туда и обратно.',
                'metadata_schema' => null,
            ],
            [
                'slug'            => 'travel_insurance',
                'name'            => 'Медицинская страховка',
                'category'        => 'travel',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 22,
                'description'     => 'Страховка на весь период поездки.',
                'metadata_schema' => ['min_coverage_eur' => 30000],
            ],
            [
                'slug'            => 'itinerary',
                'name'            => 'Маршрутный лист',
                'category'        => 'travel',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 23,
                'description'     => 'Примерный план поездки по дням (города, даты, достопримечательности).',
                'metadata_schema' => null,
            ],

            // ----------------------------------------------------------------
            // ЗАНЯТОСТЬ
            // ----------------------------------------------------------------
            [
                'slug'            => 'business_invitation',
                'name'            => 'Приглашение от компании',
                'category'        => 'employment',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 24,
                'description'     => 'Письмо от принимающей компании с целью и сроками визита.',
                'metadata_schema' => null,
            ],
            [
                'slug'            => 'company_registration',
                'name'            => 'Регистрационные документы компании',
                'category'        => 'employment',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 25,
                'description'     => 'Свидетельство о регистрации или справка из налоговой.',
                'metadata_schema' => null,
            ],
            [
                'slug'            => 'employment_certificate',
                'name'            => 'Справка с работы или учёбы',
                'category'        => 'employment',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 26,
                'description'     => 'Официальный документ от работодателя или учебного заведения.',
                'metadata_schema' => null,
            ],

            // ----------------------------------------------------------------
            // ПОДТВЕРЖДЕНИЯ И ПРОЧЕЕ
            // ----------------------------------------------------------------
            [
                'slug'            => 'cover_letter',
                'name'            => 'Сопроводительное письмо',
                'category'        => 'other',
                'type'            => 'upload',
                'is_repeatable'   => false,
                'sort_order'      => 27,
                'description'     => 'Письмо: кто вы, цель визита, маршрут, почему вернётесь домой. Рекомендуется для первого шенгена.',
                'metadata_schema' => null,
            ],
            [
                'slug'            => 'ds160_form',
                'name'            => 'Форма DS-160 (заполнена)',
                'category'        => 'confirmation',
                'type'            => 'checkbox',
                'is_repeatable'   => false,
                'sort_order'      => 28,
                'description'     => 'Заполнить онлайн на ceac.state.gov, распечатать страницу подтверждения с штрих-кодом.',
                'metadata_schema' => null,
            ],
            [
                'slug'            => 'vaf1a_form',
                'name'            => 'Анкета VAF1A (заполнена онлайн)',
                'category'        => 'confirmation',
                'type'            => 'checkbox',
                'is_repeatable'   => false,
                'sort_order'      => 29,
                'description'     => 'Заполнить на gov.uk/visas-immigration, распечатать подтверждение.',
                'metadata_schema' => null,
            ],
        ];

        foreach ($templates as $tpl) {
            $schema = $tpl['metadata_schema'] ? json_encode($tpl['metadata_schema']) : null;

            DB::table('document_templates')->upsert(
                [
                    'id'              => (string) Str::uuid(),
                    'slug'            => $tpl['slug'],
                    'name'            => $tpl['name'],
                    'category'        => $tpl['category'],
                    'type'            => $tpl['type'],
                    'is_repeatable'   => $tpl['is_repeatable'],
                    'sort_order'      => $tpl['sort_order'],
                    'description'     => $tpl['description'],
                    'metadata_schema' => $schema,
                    'is_active'       => true,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ],
                ['slug'],
                ['name', 'category', 'type', 'is_repeatable', 'description', 'metadata_schema', 'sort_order', 'updated_at']
            );
        }
    }
}
