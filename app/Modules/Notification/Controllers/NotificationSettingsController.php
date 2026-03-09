<?php

namespace App\Modules\Notification\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notification\Models\NotificationSetting;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationSettingsController extends Controller
{
    /**
     * Get all notification settings for the agency.
     */
    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $allTypes = NotificationSetting::eventTypes();

        $saved = NotificationSetting::where('agency_id', $agencyId)->get()->keyBy('event_type');

        $result = [];
        foreach ($allTypes as $type => $defaults) {
            $setting = $saved->get($type);
            $result[] = [
                'event_type' => $type,
                'channels'   => $setting ? $setting->channels : $defaults['channels'],
                'recipients' => $setting ? $setting->recipients : $defaults['recipients'],
                'is_enabled' => $setting ? $setting->is_enabled : true,
                'is_custom'  => $setting !== null,
                'audience'   => $defaults['audience'] ?? 'agency',
            ];
        }

        return ApiResponse::success([
            'settings'           => $result,
            'available_channels' => ['database', 'email', 'telegram', 'sms', 'push', 'call'],
            'available_recipients' => ['owner', 'assigned_manager', 'all_managers'],
            'channel_status'     => [
                'database' => 'active',
                'email'    => 'active',
                'telegram' => 'active',
                'sms'      => 'stub',
                'push'     => 'stub',
                'call'     => 'stub',
            ],
        ]);
    }

    /**
     * Update a notification setting for a specific event type.
     */
    public function update(Request $request, string $eventType): JsonResponse
    {
        $allTypes = NotificationSetting::eventTypes();
        if (!isset($allTypes[$eventType])) {
            return ApiResponse::error('Неизвестный тип события.', null, 422);
        }

        $validator = Validator::make($request->all(), [
            'channels'   => ['required', 'array'],
            'channels.*' => ['string', 'in:database,email,telegram,sms,push,call'],
            'recipients' => ['required', 'array'],
            'recipients.*' => ['string', 'in:owner,assigned_manager,all_managers'],
            'is_enabled' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Ошибка валидации.', $validator->errors(), 422);
        }

        $data = $validator->validated();
        $agencyId = $request->user()->agency_id;

        $setting = NotificationSetting::updateOrCreate(
            ['agency_id' => $agencyId, 'event_type' => $eventType],
            [
                'channels'   => $data['channels'],
                'recipients' => $data['recipients'],
                'is_enabled' => $data['is_enabled'],
            ],
        );

        return ApiResponse::success($setting, 'Настройки уведомлений обновлены.');
    }
}
