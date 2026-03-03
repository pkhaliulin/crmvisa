<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferenceItemsSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'lead_source' => [
                ['code' => 'direct',      'label_ru' => 'Прямое обращение',   'label_uz' => 'To\'g\'ridan-to\'g\'ri murojaat'],
                ['code' => 'referral',    'label_ru' => 'Рекомендация',       'label_uz' => 'Tavsiya'],
                ['code' => 'marketplace', 'label_ru' => 'Маркетплейс',       'label_uz' => 'Marketplace'],
                ['code' => 'website',     'label_ru' => 'Сайт',              'label_uz' => 'Sayt'],
                ['code' => 'social_media','label_ru' => 'Соцсети',           'label_uz' => 'Ijtimoiy tarmoqlar'],
                ['code' => 'partner',     'label_ru' => 'Партнёр',           'label_uz' => 'Hamkor'],
                ['code' => 'repeat',      'label_ru' => 'Повторное обращение','label_uz' => 'Qayta murojaat'],
                ['code' => 'other',       'label_ru' => 'Другое',            'label_uz' => 'Boshqa'],
            ],

            'employment_type' => [
                ['code' => 'government',     'label_ru' => 'Госслужба',          'label_uz' => 'Davlat xizmati'],
                ['code' => 'private',        'label_ru' => 'Частный сектор',     'label_uz' => 'Xususiy sektor'],
                ['code' => 'business_owner', 'label_ru' => 'Владелец бизнеса',   'label_uz' => 'Biznes egasi'],
                ['code' => 'self_employed',  'label_ru' => 'Самозанятый',        'label_uz' => 'O\'z-o\'zini band qilgan'],
                ['code' => 'retired',        'label_ru' => 'Пенсионер',          'label_uz' => 'Nafaqaxo\'r'],
                ['code' => 'student',        'label_ru' => 'Студент',            'label_uz' => 'Talaba'],
                ['code' => 'unemployed',     'label_ru' => 'Безработный',        'label_uz' => 'Ishsiz'],
                ['code' => 'freelancer',     'label_ru' => 'Фрилансер',          'label_uz' => 'Frilanser'],
            ],

            'marital_status' => [
                ['code' => 'married',  'label_ru' => 'Женат/Замужем', 'label_uz' => 'Turmush qurgan'],
                ['code' => 'single',   'label_ru' => 'Холост/Не замужем', 'label_uz' => 'Turmush qurmagan'],
                ['code' => 'divorced', 'label_ru' => 'Разведён(а)',   'label_uz' => 'Ajrashgan'],
                ['code' => 'widowed',  'label_ru' => 'Вдовец/Вдова',  'label_uz' => 'Beva'],
            ],

            'income_type' => [
                ['code' => 'official', 'label_ru' => 'Официальный',       'label_uz' => 'Rasmiy'],
                ['code' => 'informal', 'label_ru' => 'Неофициальный',     'label_uz' => 'Norasmiy'],
                ['code' => 'business', 'label_ru' => 'Доход от бизнеса',  'label_uz' => 'Biznes daromadi'],
                ['code' => 'mixed',    'label_ru' => 'Смешанный',         'label_uz' => 'Aralash'],
                ['code' => 'rental',   'label_ru' => 'Аренда/Инвестиции', 'label_uz' => 'Ijara/Investitsiya'],
            ],

            'travel_purpose' => [
                ['code' => 'tourism',      'label_ru' => 'Туризм',             'label_uz' => 'Turizm'],
                ['code' => 'business',     'label_ru' => 'Бизнес',             'label_uz' => 'Biznes'],
                ['code' => 'education',    'label_ru' => 'Образование',        'label_uz' => 'Ta\'lim'],
                ['code' => 'treatment',    'label_ru' => 'Лечение',            'label_uz' => 'Davolanish'],
                ['code' => 'family_visit', 'label_ru' => 'Посещение родных',   'label_uz' => 'Qarindoshlarni ziyorat'],
                ['code' => 'work',         'label_ru' => 'Работа',             'label_uz' => 'Ish'],
                ['code' => 'transit',      'label_ru' => 'Транзит',            'label_uz' => 'Tranzit'],
                ['code' => 'immigration',  'label_ru' => 'Иммиграция',         'label_uz' => 'Immigratsiya'],
            ],

            'education_level' => [
                ['code' => 'none',      'label_ru' => 'Без образования', 'label_uz' => 'Ta\'limsiz'],
                ['code' => 'secondary', 'label_ru' => 'Среднее',        'label_uz' => 'O\'rta'],
                ['code' => 'vocational','label_ru' => 'Среднее специальное', 'label_uz' => 'O\'rta maxsus'],
                ['code' => 'bachelor',  'label_ru' => 'Бакалавр',       'label_uz' => 'Bakalavr'],
                ['code' => 'master',    'label_ru' => 'Магистр',        'label_uz' => 'Magistr'],
                ['code' => 'phd',       'label_ru' => 'Доктор наук',    'label_uz' => 'Fan doktori'],
            ],

            'document_category' => [
                ['code' => 'personal',     'label_ru' => 'Личные',          'label_uz' => 'Shaxsiy'],
                ['code' => 'financial',    'label_ru' => 'Финансовые',      'label_uz' => 'Moliyaviy'],
                ['code' => 'family',       'label_ru' => 'Семейные',        'label_uz' => 'Oilaviy'],
                ['code' => 'property',     'label_ru' => 'Имущество',       'label_uz' => 'Mulk'],
                ['code' => 'travel',       'label_ru' => 'Путешествие',     'label_uz' => 'Sayohat'],
                ['code' => 'employment',   'label_ru' => 'Трудовые',        'label_uz' => 'Mehnat'],
                ['code' => 'confirmation', 'label_ru' => 'Подтверждения',   'label_uz' => 'Tasdiqlash'],
                ['code' => 'medical',      'label_ru' => 'Медицинские',     'label_uz' => 'Tibbiy'],
                ['code' => 'other',        'label_ru' => 'Прочее',          'label_uz' => 'Boshqa'],
            ],

            'payment_method' => [
                ['code' => 'cash',   'label_ru' => 'Наличные',   'label_uz' => 'Naqd pul'],
                ['code' => 'card',   'label_ru' => 'Карта',      'label_uz' => 'Karta'],
                ['code' => 'payme',  'label_ru' => 'Payme',      'label_uz' => 'Payme'],
                ['code' => 'click',  'label_ru' => 'Click',      'label_uz' => 'Click'],
                ['code' => 'stripe', 'label_ru' => 'Stripe',     'label_uz' => 'Stripe'],
                ['code' => 'transfer','label_ru' => 'Перевод',   'label_uz' => 'O\'tkazma'],
                ['code' => 'manual', 'label_ru' => 'Ручной ввод','label_uz' => 'Qo\'lda kiritish'],
            ],
        ];

        $now = now();

        foreach ($categories as $category => $items) {
            foreach ($items as $i => $item) {
                DB::table('reference_items')->updateOrInsert(
                    ['category' => $category, 'code' => $item['code']],
                    [
                        'id'         => Str::uuid()->toString(),
                        'label_ru'   => $item['label_ru'],
                        'label_uz'   => $item['label_uz'] ?? null,
                        'sort_order' => $i,
                        'is_active'  => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }
}
