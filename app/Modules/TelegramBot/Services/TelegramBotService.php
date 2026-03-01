<?php

namespace App\Modules\TelegramBot\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Telegram-бот VisaBor — скоринговый мини-опрос.
 *
 * Поток:
 *   /start → employment → income → marital → children → property → visa_history → result
 *
 * Состояние хранится в Cache (Redis) по ключу tg_bot:conv:{chat_id} — TTL 24ч.
 */
class TelegramBotService
{
    private string $apiBase;

    public function __construct()
    {
        $token = config('services.telegram.bot_token');
        $this->apiBase = "https://api.telegram.org/bot{$token}";
    }

    // -------------------------------------------------------------------------
    // Точка входа — роутинг входящих апдейтов
    // -------------------------------------------------------------------------

    public function handle(array $update): void
    {
        // Callback от кнопки
        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
            return;
        }

        // Обычное сообщение
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        }
    }

    // -------------------------------------------------------------------------
    // Входящие сообщения
    // -------------------------------------------------------------------------

    private function handleMessage(array $msg): void
    {
        $chatId = $msg['chat']['id'];
        $text   = trim($msg['text'] ?? '');

        if (str_starts_with($text, '/start')) {
            $this->clearState($chatId);
            $this->sendWelcome($chatId, $msg['from']['first_name'] ?? 'Друг');
            return;
        }

        // Если состояние есть — ждём кнопку, подсказываем
        $state = $this->getState($chatId);
        if ($state) {
            $this->sendMessage($chatId, 'Пожалуйста, выберите один из вариантов ниже.');
        } else {
            $this->sendWelcome($chatId, $msg['from']['first_name'] ?? 'Друг');
        }
    }

    // -------------------------------------------------------------------------
    // Callback от inline-кнопок
    // -------------------------------------------------------------------------

    private function handleCallback(array $cb): void
    {
        $chatId   = $cb['message']['chat']['id'];
        $msgId    = $cb['message']['message_id'];
        $callbackQueryId = $cb['id'];
        $data     = $cb['data'];

        $this->answerCallbackQuery($callbackQueryId);

        $state = $this->getState($chatId) ?? [];

        // Разбираем формат: "step:value"
        [$step, $value] = explode(':', $data, 2);

        // Сохраняем ответ
        $state[$step] = $value;
        $this->setState($chatId, $state);

        // Следующий шаг
        match ($step) {
            'start'       => $this->askEmployment($chatId),
            'employment'  => $this->askIncome($chatId),
            'income'      => $this->askMarital($chatId),
            'marital'     => $this->askChildren($chatId),
            'children'    => $this->askProperty($chatId),
            'property'    => $this->askVisaHistory($chatId),
            'visa_history'=> $this->showResult($chatId, $state),
            default       => $this->sendWelcome($chatId, ''),
        };
    }

    // -------------------------------------------------------------------------
    // Шаги диалога
    // -------------------------------------------------------------------------

    private function sendWelcome(string $chatId, string $firstName): void
    {
        $name = $firstName ? ", {$firstName}" : '';
        $this->sendMessage($chatId,
            "Привет{$name}! Я *VisaBor Bot* — помогаю узнать шансы на получение визы.\n\n" .
            "За 30 секунд отвечу — в какую страну вам проще всего получить визу.\n\n" .
            "Готов начать?",
            [[['text' => 'Узнать шансы на визу', 'callback_data' => 'start:yes']]],
            'Markdown'
        );
    }

    private function askEmployment(string $chatId): void
    {
        $this->sendMessage($chatId,
            "Шаг 1 из 6: *Ваша занятость*",
            [[
                ['text' => '🏢 Наёмный сотрудник', 'callback_data' => 'employment:employed'],
                ['text' => '💼 Бизнес / ИП',       'callback_data' => 'employment:business_owner'],
            ],[
                ['text' => '🎓 Студент',            'callback_data' => 'employment:student'],
                ['text' => '🏠 Другое',             'callback_data' => 'employment:other'],
            ]],
            'Markdown'
        );
    }

    private function askIncome(string $chatId): void
    {
        $this->sendMessage($chatId,
            "Шаг 2 из 6: *Ежемесячный доход*",
            [[
                ['text' => 'До $500',       'callback_data' => 'income:300'],
            ],[
                ['text' => '$500 – $1 000', 'callback_data' => 'income:800'],
                ['text' => '$1 000 – $2 000','callback_data' => 'income:1500'],
            ],[
                ['text' => '$2 000 – $4 000','callback_data' => 'income:3000'],
                ['text' => 'Более $4 000',  'callback_data' => 'income:5000'],
            ]],
            'Markdown'
        );
    }

    private function askMarital(string $chatId): void
    {
        $this->sendMessage($chatId,
            "Шаг 3 из 6: *Семейное положение*",
            [[
                ['text' => '💍 Женат / замужем',  'callback_data' => 'marital:married'],
                ['text' => '🙋 Холост / одинок',   'callback_data' => 'marital:single'],
            ]],
            'Markdown'
        );
    }

    private function askChildren(string $chatId): void
    {
        $this->sendMessage($chatId,
            "Шаг 4 из 6: *Есть ли дети, которые остаются дома?*\n_(повышает привязанность к стране)_",
            [[
                ['text' => 'Да, есть дети', 'callback_data' => 'children:yes'],
                ['text' => 'Нет',           'callback_data' => 'children:no'],
            ]],
            'Markdown'
        );
    }

    private function askProperty(string $chatId): void
    {
        $this->sendMessage($chatId,
            "Шаг 5 из 6: *Есть ли у вас недвижимость или автомобиль?*",
            [[
                ['text' => '🏠 Есть недвижимость', 'callback_data' => 'property:property'],
                ['text' => '🚗 Есть авто',          'callback_data' => 'property:car'],
            ],[
                ['text' => 'Есть и то, и другое', 'callback_data' => 'property:both'],
                ['text' => 'Нет',                  'callback_data' => 'property:none'],
            ]],
            'Markdown'
        );
    }

    private function askVisaHistory(string $chatId): void
    {
        $this->sendMessage($chatId,
            "Шаг 6 из 6: *Какие визы у вас уже есть / были?*",
            [[
                ['text' => '🇪🇺 Шенгенская виза', 'callback_data' => 'visa_history:schengen'],
                ['text' => '🇺🇸 Виза США',         'callback_data' => 'visa_history:us'],
            ],[
                ['text' => 'Обе визы',             'callback_data' => 'visa_history:both'],
                ['text' => 'Нет виз',              'callback_data' => 'visa_history:none'],
            ]],
            'Markdown'
        );
    }

    private function showResult(string $chatId, array $state): void
    {
        $scores  = $this->calcScores($state);
        $top     = array_slice($scores, 0, 3);

        $flags = [
            'DE' => '🇩🇪', 'ES' => '🇪🇸', 'FR' => '🇫🇷', 'IT' => '🇮🇹',
            'PL' => '🇵🇱', 'CZ' => '🇨🇿', 'GB' => '🇬🇧', 'US' => '🇺🇸',
            'CA' => '🇨🇦', 'KR' => '🇰🇷', 'AE' => '🇦🇪',
        ];
        $names = [
            'DE' => 'Германия',      'ES' => 'Испания',    'FR' => 'Франция',
            'IT' => 'Италия',        'PL' => 'Польша',     'CZ' => 'Чехия',
            'GB' => 'Великобритания','US' => 'США',         'CA' => 'Канада',
            'KR' => 'Южная Корея',   'AE' => 'ОАЭ',
        ];

        $text = "*Ваши шансы на визу:*\n\n";
        foreach ($top as $s) {
            $cc    = $s['country_code'];
            $score = $s['score'];
            $bar   = $this->progressBar($score);
            $label = $s['label'];
            $flag  = $flags[$cc] ?? '🌍';
            $name  = $names[$cc] ?? $cc;
            $text .= "{$flag} *{$name}* — {$score}% ({$label})\n{$bar}\n\n";
        }

        $best = $top[0] ?? null;
        if ($best) {
            $text .= "Лучший вариант — *{$names[$best['country_code']] ?? $best['country_code']}*.\n";
        }

        $appUrl = rtrim(config('app.url'), '/');
        $text .= "\nПодробный скоринг со всеми 11 странами и рекомендациями:\n";

        $this->sendMessage($chatId, $text,
            [[['text' => 'Открыть детальный скоринг', 'url' => "{$appUrl}/scoring"]]],
            'Markdown'
        );

        // Предлагаем найти агентство
        $this->sendMessage($chatId,
            "Хотите, чтобы с вами связалось агентство?\n" .
            "Оставьте заявку на сайте — агентство подберёт вам нужные документы и поможет с подачей.",
            [[['text' => 'Найти агентство', 'url' => "{$appUrl}/#agencies"]]],
        );

        $this->clearState($chatId);
    }

    // -------------------------------------------------------------------------
    // Подсчёт скора по сырым данным
    // -------------------------------------------------------------------------

    private function calcScores(array $d): array
    {
        $employment = $d['employment'] ?? 'other';
        $income     = (int) ($d['income'] ?? 0);
        $married    = ($d['marital'] ?? '') === 'married';
        $children   = ($d['children'] ?? 'no') === 'yes';
        $property   = $d['property'] ?? 'none';
        $visaHist   = $d['visa_history'] ?? 'none';

        $hasProperty = in_array($property, ['property', 'both']);
        $hasCar      = in_array($property, ['car', 'both']);
        $hasSchengen = in_array($visaHist, ['schengen', 'both']);
        $hasUs       = in_array($visaHist, ['us', 'both']);

        // Блок Finance
        $finance = match ($employment) {
            'employed'       => 40,
            'business_owner' => 50,
            'student'        => 10,
            default          => 25,
        };
        if ($income >= 3000)     $finance += 50;
        elseif ($income >= 1500) $finance += 35;
        elseif ($income >= 800)  $finance += 20;
        elseif ($income >= 400)  $finance += 10;
        $finance = min(100, $finance);

        // Блок Ties
        $ties = 0;
        if ($married)     $ties += 25;
        if ($children)    $ties += 25;
        if ($hasProperty) $ties += 25;
        if ($hasCar)      $ties += 10;
        if ($employment === 'employed') $ties += 15;
        $ties = min(100, $ties);

        // Блок Travel
        $travel = 50;
        if ($hasSchengen) $travel += 20;
        if ($hasUs)       $travel += 25;
        $travel = min(100, max(0, $travel));

        // Веса по странам
        $weights = [
            'DE' => ['finance' => 0.40, 'ties' => 0.35, 'travel' => 0.15, 'profile' => 0.10],
            'ES' => ['finance' => 0.35, 'ties' => 0.40, 'travel' => 0.15, 'profile' => 0.10],
            'FR' => ['finance' => 0.40, 'ties' => 0.35, 'travel' => 0.15, 'profile' => 0.10],
            'IT' => ['finance' => 0.35, 'ties' => 0.40, 'travel' => 0.15, 'profile' => 0.10],
            'PL' => ['finance' => 0.35, 'ties' => 0.40, 'travel' => 0.15, 'profile' => 0.10],
            'CZ' => ['finance' => 0.35, 'ties' => 0.40, 'travel' => 0.15, 'profile' => 0.10],
            'GB' => ['finance' => 0.35, 'ties' => 0.40, 'travel' => 0.15, 'profile' => 0.10],
            'US' => ['finance' => 0.25, 'ties' => 0.50, 'travel' => 0.15, 'profile' => 0.10],
            'CA' => ['finance' => 0.25, 'ties' => 0.50, 'travel' => 0.15, 'profile' => 0.10],
            'KR' => ['finance' => 0.30, 'ties' => 0.45, 'travel' => 0.15, 'profile' => 0.10],
            'AE' => ['finance' => 0.35, 'ties' => 0.35, 'travel' => 0.20, 'profile' => 0.10],
        ];

        $scores = [];
        foreach ($weights as $cc => $w) {
            // profile = 50 (частично заполнен — через бота)
            $total = (int) round(
                $finance * $w['finance'] +
                $ties    * $w['ties']    +
                $travel  * $w['travel']  +
                50       * $w['profile']
            );
            $total = max(5, min(100, $total));
            $scores[] = [
                'country_code' => $cc,
                'score'        => $total,
                'label'        => $this->scoreLabel($total),
            ];
        }

        usort($scores, fn ($a, $b) => $b['score'] - $a['score']);
        return $scores;
    }

    private function progressBar(int $score): string
    {
        $filled = (int) round($score / 10);
        $empty  = 10 - $filled;
        return str_repeat('▓', $filled) . str_repeat('░', $empty) . " {$score}%";
    }

    private function scoreLabel(int $score): string
    {
        return match (true) {
            $score >= 75 => 'Высокий',
            $score >= 55 => 'Средний',
            $score >= 35 => 'Ниже среднего',
            default      => 'Низкий',
        };
    }

    // -------------------------------------------------------------------------
    // Telegram API
    // -------------------------------------------------------------------------

    private function sendMessage(
        string $chatId,
        string $text,
        ?array $inlineKeyboard = null,
        string $parseMode = ''
    ): void {
        $payload = [
            'chat_id'    => $chatId,
            'text'       => $text,
        ];

        if ($parseMode) {
            $payload['parse_mode'] = $parseMode;
        }

        if ($inlineKeyboard !== null) {
            $payload['reply_markup'] = json_encode(['inline_keyboard' => $inlineKeyboard]);
        }

        $this->post('sendMessage', $payload);
    }

    private function answerCallbackQuery(string $callbackQueryId): void
    {
        $this->post('answerCallbackQuery', ['callback_query_id' => $callbackQueryId]);
    }

    private function post(string $method, array $payload): void
    {
        try {
            Http::timeout(5)->post("{$this->apiBase}/{$method}", $payload);
        } catch (\Throwable $e) {
            Log::warning("TelegramBot {$method} failed: " . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // State (Redis / Cache)
    // -------------------------------------------------------------------------

    private function getState(string $chatId): ?array
    {
        return Cache::get("tg_bot:conv:{$chatId}");
    }

    private function setState(string $chatId, array $state): void
    {
        Cache::put("tg_bot:conv:{$chatId}", $state, now()->addHours(24));
    }

    private function clearState(string $chatId): void
    {
        Cache::forget("tg_bot:conv:{$chatId}");
    }
}
