<?php

namespace App\Modules\TelegramBot\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TelegramBot\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TelegramBotController extends Controller
{
    public function __construct(private TelegramBotService $bot) {}

    /**
     * Webhook — принимает апдейты от Telegram.
     * Всегда возвращает 200, чтобы Telegram не повторял запросы.
     */
    public function webhook(Request $request): Response
    {
        $update = $request->all();

        // Базовая проверка секретного токена
        $secret = config('services.telegram.webhook_secret');
        if ($secret && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            return response('', 403);
        }

        dispatch(function () use ($update) {
            app(TelegramBotService::class)->handle($update);
        })->afterResponse();

        return response('ok', 200);
    }
}
