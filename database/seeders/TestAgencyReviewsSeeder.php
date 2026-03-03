<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestAgencyReviewsSeeder extends Seeder
{
    public function run(): void
    {
        // RLS bypass для сидирования
        DB::statement("SET app.is_superadmin = 'true'");

        $slugs = ['silk-road-visa', 'euro-visa-pro', 'asia-passport', 'visa-grand', 'travel-docs-uz'];

        // Имена фиктивных рецензентов
        $reviewerNames = [
            'Азиз Каримов', 'Нилуфар Рашидова', 'Бахром Юсупов',
            'Гулнора Мирзаева', 'Санжар Исмоилов', 'Дилноза Хасанова',
            'Отабек Усмонов', 'Зулфия Ахмедова', 'Фирдавс Муродов',
            'Мадина Шарипова', 'Темур Тошматов', 'Наргиза Абдуллаева',
        ];

        // Шаблоны комментариев
        $positiveComments = [
            'Очень профессиональная команда. Всё сделали быстро и без лишних вопросов.',
            'Оформили визу за 10 дней. Документы собрали сами, только подписи от меня. Рекомендую!',
            'Обратился второй раз — снова всё отлично. Постоянный клиент теперь.',
            'Менеджер Алишер был очень внимателен, объяснил каждый шаг. Спасибо!',
            'Получили визы для всей семьи. Цена разумная, результат отличный.',
            'Агентство работает чётко. Не первый раз и не последний обращаюсь.',
        ];

        $neutralComments = [
            'В целом доволен. Небольшая задержка с документами, но всё решилось.',
            'Визу получили, но пришлось несколько раз напоминать о статусе.',
            'Нормально. Всё как обещали, но ожидал чуть лучшего сервиса.',
            'Цена немного выше рынка, зато документы оформили правильно с первого раза.',
        ];

        $negativeComments = [
            'Задержали сроки на неделю. Пришлось менять билеты. Без предупреждения.',
            'Общение не очень. На вопросы отвечали медленно.',
        ];

        // Данные отзывов для каждого агентства: [punctuality, quality, communication, professionalism, price_quality, comment]
        $reviewSets = [
            'silk-road-visa' => [
                [5, 5, 5, 5, 4, 'Лучшее агентство в Ташкенте! Шенгенскую визу оформили за 12 дней. Менеджер на связи 24/7.'],
                [5, 5, 4, 5, 5, 'Уже третий раз обращаюсь. Всегда быстро и надёжно. Рекомендую всем!'],
                [4, 5, 5, 5, 4, 'Получили визы в Германию для двух человек. Всё прошло отлично, документы подготовили идеально.'],
                [5, 4, 5, 4, 4, 'Очень профессиональная команда. Помогли собрать все документы, дали советы по собеседованию.'],
                [5, 5, 5, 5, 5, 'Лучшее агентство в городе. Дороже, чем у конкурентов, но результат стоит каждой тийин.'],
                [4, 5, 4, 5, 3, 'Визу в Испанию получили без проблем. Немного дорого по сравнению с другими.'],
                [5, 5, 5, 4, 4, 'Оформляли визу в Италию. Агентство предупреждало обо всех этапах, никаких сюрпризов.'],
                [3, 4, 4, 4, 5, 'В целом доволен. Небольшая задержка с записью в посольство, но всё решилось.'],
            ],
            'euro-visa-pro' => [
                [5, 5, 5, 5, 4, 'Получили визы в Великобританию для всей семьи. Очень рекомендую!'],
                [4, 5, 4, 5, 4, 'Профессиональный подход. Сделали всё за 15 дней, как и обещали.'],
                [5, 4, 5, 4, 3, 'Хорошее агентство. Цена немного выше рынка, но качество на высоте.'],
                [4, 4, 5, 5, 4, 'Менеджер Дилшод помог с документами для визы в Германию. Очень внимательный специалист.'],
                [5, 5, 4, 5, 5, 'Уже второй раз. Работают стабильно, никаких проблем.'],
                [3, 4, 3, 4, 4, 'Нормально. Пришлось несколько раз напоминать о статусе заявки, но результат хороший.'],
                [5, 5, 5, 5, 4, 'Получили визу в Чехию. Все документы подготовили сами, нам осталось только подписать.'],
            ],
            'asia-passport' => [
                [5, 5, 5, 5, 5, 'Отличное агентство для виз в ОАЭ! Оформили за 2 дня, всё онлайн.'],
                [4, 5, 4, 4, 5, 'Турецкую визу сделали очень быстро. Приятные цены, хороший сервис.'],
                [5, 4, 5, 4, 4, 'Помогли с визой в Таиланд. Документы проверили, ничего лишнего не попросили.'],
                [4, 4, 4, 5, 5, 'Отличное соотношение цены и качества. Буду обращаться снова.'],
                [5, 5, 5, 5, 4, 'Вся семья поехала в ОАЭ. Визы оформили быстро и без нервов.'],
                [3, 4, 3, 3, 4, 'В целом нормально. Ожидал чуть быстрее, но документы сделали правильно.'],
                [5, 4, 4, 4, 5, 'Хорошее агентство в Бухаре. Быстро, дёшево, без лишних вопросов.'],
                [4, 5, 5, 5, 4, 'Помогли с китайской визой. Ничего не упустили, всё оформили с первого раза.'],
            ],
            'visa-grand' => [
                [5, 5, 4, 5, 3, 'Получили американскую визу! Агентство помогло с подготовкой к собеседованию. Очень важная услуга.'],
                [4, 4, 4, 4, 3, 'Виза в США — дорогое удовольствие, но агентство сделало всё правильно.'],
                [3, 4, 3, 4, 4, 'Немного долго ждали ответа от менеджера, но визу в Канаду получили.'],
                [5, 5, 5, 5, 4, 'Оформили австралийскую визу. Долго, но это требование посольства. Агентство сопроводило отлично.'],
                [4, 4, 4, 4, 3, 'Работают профессионально. Цены немного выше среднего, но за качество платить стоит.'],
                [5, 4, 5, 4, 4, 'Обратился за визой в Великобританию. Всё оформили правильно, получил с первого раза.'],
                [3, 3, 3, 4, 4, 'Задержали документы на 3 дня. Пришлось звонить самому. Но в итоге визу дали.'],
            ],
            'travel-docs-uz' => [
                [5, 4, 4, 4, 5, 'Турецкую визу сделали за сутки. Цена отличная, сервис хороший.'],
                [4, 4, 5, 4, 5, 'Итальянская виза. Всё прошло гладко, цена ниже чем у других агентств.'],
                [3, 4, 3, 3, 4, 'Нормально. Пара недочётов по коммуникации, но визу в итоге дали.'],
                [5, 5, 4, 5, 4, 'Отличная работа! Быстро, чётко, профессионально. Буду рекомендовать друзьям.'],
                [4, 3, 4, 4, 5, 'Цены ниже рынка — главное преимущество. Качество тоже на уровне.'],
                [4, 4, 4, 4, 4, 'Работаю через это агентство уже 2 года. Всегда стабильный результат.'],
                [2, 3, 2, 3, 3, 'Были задержки с уведомлениями. На телефон не отвечали быстро. Но визу дали.'],
                [5, 4, 5, 4, 5, 'Виза в Грецию за разумные деньги. Всё сопровождение было чётким.'],
            ],
        ];

        // Собираем уникальные имена → создаём/находим fake public_users
        $userMap = [];
        foreach ($reviewerNames as $name) {
            $phone = '+99890' . str_pad((string) rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);
            // Создаём или находим по имени (идемпотентно через upsert по phone)
            $userId = Str::uuid()->toString();
            DB::table('public_users')->insertOrIgnore([
                'id'         => $userId,
                'phone'      => $phone,
                'name'       => $name,
                'created_at' => now()->subDays(rand(60, 400)),
                'updated_at' => now(),
            ]);
            // Берём свежесозданный id
            $row = DB::table('public_users')->where('phone', $phone)->first();
            $userMap[$name] = $row->id;
        }

        // Создаём отзывы
        foreach ($slugs as $slug) {
            $agency = DB::table('agencies')->where('slug', $slug)->first();
            if (! $agency) continue;

            // Удаляем старые seed-отзывы для чистоты
            DB::table('agency_reviews')->where('agency_id', $agency->id)
                ->whereNull('case_id')
                ->delete();

            $set = $reviewSets[$slug] ?? [];
            shuffle($reviewerNames);
            $usedNames = array_slice($reviewerNames, 0, count($set));

            foreach ($set as $i => $review) {
                [$p, $q, $c, $pro, $pq, $comment] = $review;
                $overall = round(($p + $q + $c + $pro + $pq) / 5, 1);
                $name    = $usedNames[$i] ?? ('Клиент ' . ($i + 1));
                $userId  = $userMap[$name] ?? null;

                // Если userId не нашёлся — создаём одноразового user
                if (! $userId) {
                    $phone  = '+99890' . str_pad((string) rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);
                    $userId = Str::uuid()->toString();
                    DB::table('public_users')->insertOrIgnore([
                        'id'         => $userId,
                        'phone'      => $phone,
                        'name'       => $name,
                        'created_at' => now()->subDays(rand(30, 300)),
                        'updated_at' => now(),
                    ]);
                    $row    = DB::table('public_users')->where('phone', $phone)->first();
                    $userId = $row->id;
                }

                // Проверяем что такого отзыва ещё нет
                $exists = DB::table('agency_reviews')
                    ->where('agency_id', $agency->id)
                    ->where('public_user_id', $userId)
                    ->exists();
                if ($exists) continue;

                DB::table('agency_reviews')->insert([
                    'id'              => Str::uuid()->toString(),
                    'agency_id'       => $agency->id,
                    'public_user_id'  => $userId,
                    'case_id'         => null,
                    'client_name'     => $name,
                    'rating'          => $overall,
                    'punctuality'     => $p,
                    'quality'         => $q,
                    'communication'   => $c,
                    'professionalism' => $pro,
                    'price_quality'   => $pq,
                    'comment'         => $comment,
                    'is_published'    => true,
                    'created_at'      => now()->subDays(rand(1, 180))->subHours(rand(0, 23)),
                    'updated_at'      => now(),
                ]);
            }

            // Пересчитываем рейтинг и top_criterion
            $reviews = DB::table('agency_reviews')
                ->where('agency_id', $agency->id)
                ->where('is_published', true)
                ->get();

            $count = $reviews->count();
            $avgRating = $count > 0 ? round($reviews->avg('rating'), 2) : null;

            // Лучший критерий
            $criteria = ['punctuality', 'quality', 'communication', 'professionalism', 'price_quality'];
            $criteriaAvg = [];
            foreach ($criteria as $cr) {
                $vals = $reviews->pluck($cr)->filter()->map(fn($v) => (float)$v);
                if ($vals->count() > 0) $criteriaAvg[$cr] = $vals->average();
            }
            $topCriterion = null;
            if (! empty($criteriaAvg)) {
                arsort($criteriaAvg);
                $topCriterion = array_key_first($criteriaAvg);
            }

            DB::table('agencies')->where('id', $agency->id)->update([
                'rating'        => $avgRating,
                'reviews_count' => $count,
                'top_criterion' => $topCriterion,
                'updated_at'    => now(),
            ]);

            $this->command->info("  {$slug}: {$count} отзывов, рейтинг {$avgRating}, лучший критерий: {$topCriterion}");
        }

        // Обновляем пакеты — ставим цены в UZS (убираем старые USD цены)
        $uzsPrices = [
            'tourist'  => [1_200_000, 1_500_000, 1_800_000, 2_000_000],
            'business' => [2_000_000, 2_500_000, 3_000_000, 3_500_000],
            'student'  => [1_800_000, 2_200_000, 2_800_000],
        ];

        foreach ($slugs as $slug) {
            $agency = DB::table('agencies')->where('slug', $slug)->first();
            if (! $agency) continue;

            $pkgs = DB::table('agency_service_packages')
                ->where('agency_id', $agency->id)
                ->get();

            foreach ($pkgs as $pkg) {
                $type   = $pkg->visa_type ?? 'tourist';
                $prices = $uzsPrices[$type] ?? $uzsPrices['tourist'];
                $price  = $prices[array_rand($prices)];
                DB::table('agency_service_packages')
                    ->where('id', $pkg->id)
                    ->update(['price' => $price, 'currency' => 'UZS', 'updated_at' => now()]);
            }
        }

        $this->command->info('TestAgencyReviewsSeeder: done');
    }
}
