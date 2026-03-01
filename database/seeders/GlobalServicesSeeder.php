<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GlobalServicesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $services = [
            // consultation (4)
            ['slug' => 'consultation-initial',        'name' => 'Первичная консультация',             'category' => 'consultation'],
            ['slug' => 'consultation-document-check', 'name' => 'Проверка документов',                'category' => 'consultation'],
            ['slug' => 'consultation-refusal-review', 'name' => 'Анализ отказа и стратегия',          'category' => 'consultation'],
            ['slug' => 'consultation-online',         'name' => 'Онлайн-консультация (видеозвонок)',   'category' => 'consultation'],

            // documents (7)
            ['slug' => 'docs-application-filling',   'name' => 'Заполнение анкеты/заявления',         'category' => 'documents'],
            ['slug' => 'docs-checklist',             'name' => 'Составление чек-листа документов',    'category' => 'documents'],
            ['slug' => 'docs-collection-support',    'name' => 'Сопровождение сбора документов',      'category' => 'documents'],
            ['slug' => 'docs-invitation-letter',     'name' => 'Подготовка приглашения',              'category' => 'documents'],
            ['slug' => 'docs-cover-letter',          'name' => 'Сопроводительное письмо',             'category' => 'documents'],
            ['slug' => 'docs-itinerary',             'name' => 'Маршрутный лист',                     'category' => 'documents'],
            ['slug' => 'docs-hotel-booking',         'name' => 'Бронирование отеля (для визы)',        'category' => 'documents'],

            // translation (4)
            ['slug' => 'translation-standard',       'name' => 'Перевод документов',                  'category' => 'translation'],
            ['slug' => 'translation-notarized',      'name' => 'Нотариально заверенный перевод',       'category' => 'translation'],
            ['slug' => 'translation-apostille',      'name' => 'Апостиль',                            'category' => 'translation'],
            ['slug' => 'translation-legalization',   'name' => 'Легализация документов',              'category' => 'translation'],

            // visa_center (5)
            ['slug' => 'vc-appointment-booking',     'name' => 'Запись в визовый центр',              'category' => 'visa_center'],
            ['slug' => 'vc-biometrics-support',      'name' => 'Сопровождение на биометрию',         'category' => 'visa_center'],
            ['slug' => 'vc-document-submission',     'name' => 'Подача документов в визовый центр',   'category' => 'visa_center'],
            ['slug' => 'vc-vip-service',             'name' => 'VIP-обслуживание',                    'category' => 'visa_center'],
            ['slug' => 'vc-tracking',                'name' => 'Отслеживание статуса визы',           'category' => 'visa_center'],

            // financial (3)
            ['slug' => 'fin-bank-statement',         'name' => 'Помощь с выпиской из банка',          'category' => 'financial'],
            ['slug' => 'fin-sponsorship-letter',     'name' => 'Письмо спонсора',                     'category' => 'financial'],
            ['slug' => 'fin-insurance',              'name' => 'Туристическая страховка',              'category' => 'financial'],

            // other (3)
            ['slug' => 'other-photo',                'name' => 'Фото для визы',                       'category' => 'other'],
            ['slug' => 'other-courier',              'name' => 'Курьерская доставка документов',       'category' => 'other'],
            ['slug' => 'other-urgent',               'name' => 'Срочное оформление',                  'category' => 'other'],
        ];

        $rows = array_map(function (array $item, int $i) use ($now) {
            return [
                'id'            => (string) Str::uuid(),
                'slug'          => $item['slug'],
                'name'          => $item['name'],
                'category'      => $item['category'],
                'description'   => null,
                'is_combinable' => true,
                'is_optional'   => true,
                'sort_order'    => $i,
                'is_active'     => true,
                'created_at'    => $now,
                'updated_at'    => $now,
                'deleted_at'    => null,
            ];
        }, $services, array_keys($services));

        DB::table('global_services')->upsert(
            $rows,
            ['slug'],
            ['name', 'category', 'sort_order', 'is_active', 'updated_at']
        );
    }
}
