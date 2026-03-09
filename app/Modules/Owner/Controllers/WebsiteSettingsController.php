<?php

namespace App\Modules\Owner\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WebsiteSettingsController extends Controller
{
    /**
     * GET /owner/website-settings — Все настройки сайта, сгруппированные
     */
    public function index(): JsonResponse
    {
        $settings = DB::table('site_settings')
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->mapWithKeys(fn($row) => [$row->key => json_decode($row->value)])
            ->toArray();

        return ApiResponse::success([
            'settings' => $settings,
            'groups' => [
                'site' => 'Состояние сайта',
                'seo' => 'SEO-параметры',
                'content' => 'Контент главной',
                'contact' => 'Контакты',
                'sections' => 'Видимость блоков',
            ],
        ]);
    }

    /**
     * PUT /owner/website-settings — Обновить настройки (batch)
     */
    public function update(Request $request): JsonResponse
    {
        $settings = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'present',
        ]);

        foreach ($settings['settings'] as $key => $value) {
            DB::table('site_settings')
                ->where('key', $key)
                ->update([
                    'value' => json_encode($value),
                    'updated_at' => now(),
                ]);
        }

        // Очищаем кэш лендинга
        Cache::forget('landing:settings');

        return ApiResponse::success(null, 'Настройки сохранены');
    }

    /**
     * POST /owner/website-settings/clear-cache — Очистить весь кэш лендинга
     */
    public function clearCache(): JsonResponse
    {
        Cache::forget('landing:countries');
        Cache::forget('landing:agencies');
        Cache::forget('landing:stats');
        Cache::forget('landing:settings');

        return ApiResponse::success(null, 'Кэш лендинга очищен');
    }
}
