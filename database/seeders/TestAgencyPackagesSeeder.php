<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestAgencyPackagesSeeder extends Seeder
{
    public function run(): void
    {
        // Тестовые агентства: slug → страны
        $agencyData = [
            'silk-road-visa' => [
                'countries'    => ['DE', 'FR', 'IT', 'ES', 'GB'],
                'description'  => 'Ведущее визовое агентство Ташкента. Работаем с европейскими направлениями с 2011 года. Более 5 000 успешных виз.',
                'address'      => 'Ташкент, ул. Мустакиллик, 14, офис 305',
                'rating'       => 4.8,
                'is_verified'  => true,
                'latitude'     => 41.2995,
                'longitude'    => 69.2401,
            ],
            'euro-visa-pro' => [
                'countries'    => ['DE', 'GB', 'TR', 'CZ', 'PL'],
                'description'  => 'Специализируемся на визах в Великобританию, Германию и страны Шенгена. Высокий процент одобрения — 94%.',
                'address'      => 'Самарканд, просп. Регистан, 3, этаж 2',
                'rating'       => 4.6,
                'is_verified'  => true,
                'latitude'     => 39.6270,
                'longitude'    => 66.9750,
            ],
            'asia-passport' => [
                'countries'    => ['AE', 'TR', 'CN', 'TH', 'MY'],
                'description'  => 'Визы в страны Азии и ОАЭ. Экспресс-оформление за 3–5 дней. Офис в историческом центре Бухары.',
                'address'      => 'Бухара, ул. Накшбанди, 8',
                'rating'       => 4.5,
                'is_verified'  => false,
                'latitude'     => 39.7747,
                'longitude'    => 64.4286,
            ],
            'visa-grand' => [
                'countries'    => ['US', 'GB', 'DE', 'CA', 'AU'],
                'description'  => 'Помогаем получить визы в США, Канаду и Австралию. Опыт работы более 8 лет. Бесплатная консультация.',
                'address'      => 'Нукус, ул. Достлик, 22',
                'rating'       => 4.3,
                'is_verified'  => false,
                'latitude'     => 42.4600,
                'longitude'    => 59.6100,
            ],
            'travel-docs-uz' => [
                'countries'    => ['TR', 'AE', 'ES', 'IT', 'GR'],
                'description'  => 'Туристические и бизнес-визы. Работаем с популярными туристическими направлениями. Быстрое оформление.',
                'address'      => 'Фергана, ул. Мустакиллик, 55, офис 12',
                'rating'       => 4.1,
                'is_verified'  => false,
                'latitude'     => 40.3864,
                'longitude'    => 71.7864,
            ],
        ];

        // Пакеты по типам виз
        $packages = [
            'tourist' => [
                'name'            => 'Туристическая виза',
                'description'     => 'Полное сопровождение подачи туристической визы: от подготовки документов до получения ответа.',
                'price'           => 150,
                'processing_days' => 14,
            ],
            'business' => [
                'name'            => 'Бизнес-виза',
                'description'     => 'Оформление бизнес-визы для деловых поездок. Подготовка приглашений, финансовых документов.',
                'price'           => 250,
                'processing_days' => 10,
            ],
            'student' => [
                'name'            => 'Студенческая виза',
                'description'     => 'Помощь с получением студенческой визы. Перевод документов, апостиль, подача в консульство.',
                'price'           => 200,
                'processing_days' => 21,
            ],
        ];

        // Получаем ID сервисов (несколько популярных)
        $serviceIds = DB::table('global_services')
            ->whereIn('slug', [
                'consultation-initial', 'document-collection', 'document-translation',
                'insurance', 'visa-application-form', 'appointment-booking',
            ])
            ->pluck('id', 'slug');

        foreach ($agencyData as $slug => $data) {
            $agency = DB::table('agencies')->where('slug', $slug)->first();
            if (! $agency) continue;

            // Обновляем агентство: описание, адрес, рейтинг, координаты
            DB::table('agencies')->where('id', $agency->id)->update([
                'description'      => $data['description'],
                'address'          => $data['address'],
                'rating'           => $data['rating'],
                'is_verified'      => $data['is_verified'],
                'latitude'         => $data['latitude'],
                'longitude'        => $data['longitude'],
                'experience_years' => rand(5, 15),
            ]);

            foreach ($data['countries'] as $cc) {
                foreach ($packages as $visaType => $pkg) {
                    // Пропускаем если уже есть
                    $exists = DB::table('agency_service_packages')
                        ->where('agency_id', $agency->id)
                        ->where('country_code', $cc)
                        ->where('visa_type', $visaType)
                        ->exists();
                    if ($exists) continue;

                    $pkgId = Str::uuid()->toString();

                    DB::table('agency_service_packages')->insert([
                        'id'              => $pkgId,
                        'agency_id'       => $agency->id,
                        'name'            => $pkg['name'] . ' — ' . $cc,
                        'description'     => $pkg['description'],
                        'country_code'    => $cc,
                        'visa_type'       => $visaType,
                        'price'           => $pkg['price'] + rand(-20, 50),
                        'currency'        => 'USD',
                        'processing_days' => $pkg['processing_days'],
                        'is_active'       => true,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);

                    // Добавляем 2-3 услуги в пакет
                    $svcSlugs = array_slice(array_keys($serviceIds->toArray()), 0, 3);
                    foreach ($svcSlugs as $svcSlug) {
                        $svcId = $serviceIds[$svcSlug] ?? null;
                        if (! $svcId) continue;
                        DB::table('agency_service_package_items')->insert([
                            'id'         => Str::uuid()->toString(),
                            'package_id' => $pkgId,
                            'service_id' => $svcId,
                            'notes'      => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // Заполняем embassy_data в portal_countries
        $embassyData = [
            'DE' => ['processing_days_standard' => 15, 'processing_days_expedited' => 7,  'appointment_wait_days' => 14, 'buffer_days_recommended' => 21, 'embassy_website' => 'https://usbekistan.diplo.de', 'embassy_description' => 'Посольство Германии выдаёт шенгенские визы. Запись за 2-3 недели, рассмотрение 15 рабочих дней.'],
            'FR' => ['processing_days_standard' => 15, 'processing_days_expedited' => 5,  'appointment_wait_days' => 21, 'buffer_days_recommended' => 28, 'embassy_website' => 'https://uz.ambafrance.org',    'embassy_description' => 'Посольство Франции. Высокий спрос — слоты заканчиваются быстро, записывайтесь заранее.'],
            'IT' => ['processing_days_standard' => 15, 'processing_days_expedited' => 8,  'appointment_wait_days' => 10, 'buffer_days_recommended' => 21, 'embassy_website' => 'https://ambashkent.esteri.it',  'embassy_description' => 'Визовый центр Италии VFS Global в Ташкенте.'],
            'ES' => ['processing_days_standard' => 45, 'processing_days_expedited' => 30, 'appointment_wait_days' => 7,  'buffer_days_recommended' => 60, 'embassy_website' => 'https://www.exteriores.gob.es',   'embassy_description' => 'Испания рассматривает до 45 дней. Практически ответы приходят за 30 дней. Подавайте за 55-60 дней до поездки.'],
            'GB' => ['processing_days_standard' => 21, 'processing_days_expedited' => 5,  'appointment_wait_days' => 14, 'buffer_days_recommended' => 35, 'embassy_website' => 'https://www.gov.uk/apply-uk-visa', 'embassy_description' => 'Визы в Великобританию через VFS Global. Стандартный срок 21 рабочий день.'],
            'AE' => ['processing_days_standard' => 3,  'processing_days_expedited' => 1,  'appointment_wait_days' => 0,  'buffer_days_recommended' => 7,  'embassy_website' => 'https://www.icp.gov.ae',           'embassy_description' => 'Визы в ОАЭ оформляются онлайн за 2-3 рабочих дня.'],
            'TR' => ['processing_days_standard' => 2,  'processing_days_expedited' => 1,  'appointment_wait_days' => 0,  'buffer_days_recommended' => 5,  'embassy_website' => 'https://www.evisa.gov.tr',          'embassy_description' => 'Электронная виза в Турцию — онлайн за 24-72 часа.'],
            'US' => ['processing_days_standard' => 60, 'processing_days_expedited' => 15, 'appointment_wait_days' => 90, 'buffer_days_recommended' => 120,'embassy_website' => 'https://uz.usembassy.gov',          'embassy_description' => 'Посольство США. Очередь на интервью 2-3 месяца. Планируйте поездку минимум за 4-5 месяцев.'],
            'CN' => ['processing_days_standard' => 10, 'processing_days_expedited' => 4,  'appointment_wait_days' => 7,  'buffer_days_recommended' => 21, 'embassy_website' => 'https://uz.china-embassy.gov.cn',  'embassy_description' => 'Посольство Китая в Ташкенте. Запись за неделю, обработка 10 рабочих дней.'],
            'TH' => ['processing_days_standard' => 5,  'processing_days_expedited' => 2,  'appointment_wait_days' => 3,  'buffer_days_recommended' => 14, 'embassy_website' => 'https://thaiconsulate-tashkent.com','embassy_description' => 'Туристическая виза Таиланд за 3-5 рабочих дней.'],
            'KR' => ['processing_days_standard' => 10, 'processing_days_expedited' => 5,  'appointment_wait_days' => 5,  'buffer_days_recommended' => 21, 'embassy_website' => 'https://overseas.mofa.go.kr/uz',    'embassy_description' => 'Посольство Кореи. Обработка 10 рабочих дней, запись за 5-7 дней.'],
        ];

        foreach ($embassyData as $cc => $info) {
            DB::table('portal_countries')
                ->where('country_code', $cc)
                ->update(array_merge($info, ['updated_at' => now()]));
        }

        $this->command->info('TestAgencyPackagesSeeder: done');
    }
}
