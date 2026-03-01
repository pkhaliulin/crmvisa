<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicProfileController extends Controller
{
    /**
     * GET /public/me
     * Профиль + процент заполненности.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');

        return ApiResponse::success([
            'user'            => $user,
            'profile_percent' => $user->profileCompleteness(),
            'has_pin'         => (bool) $user->pin_hash,
        ]);
    }

    /**
     * PATCH /public/me
     * Обновить профиль (ручной ввод или после OCR).
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');

        $data = $request->validate([
            'name'               => 'sometimes|string|max:255',
            'dob'                => 'sometimes|date|before:today',
            'citizenship'        => 'sometimes|string|size:2',
            'gender'             => ['sometimes', Rule::in(['M', 'F'])],
            'passport_number'    => 'sometimes|string|max:50',
            'passport_expires_at'=> 'sometimes|date|after:today',
            'employment_type'    => ['sometimes', Rule::in(['employed','self_employed','business_owner','student','retired','unemployed'])],
            'monthly_income_usd' => 'sometimes|integer|min:0',
            'marital_status'     => ['sometimes', Rule::in(['single','married','divorced','widowed'])],
            'has_children'       => 'sometimes|boolean',
            'children_count'     => 'sometimes|integer|min:0|max:20',
            'has_property'       => 'sometimes|boolean',
            'has_car'            => 'sometimes|boolean',
            'has_schengen_visa'  => 'sometimes|boolean',
            'has_us_visa'        => 'sometimes|boolean',
            'had_visa_refusal'   => 'sometimes|boolean',
            'had_overstay'       => 'sometimes|boolean',
        ]);

        $user->update($data);

        return ApiResponse::success([
            'user'            => $user->fresh(),
            'profile_percent' => $user->fresh()->profileCompleteness(),
        ], 'Профиль обновлён');
    }

    /**
     * GET /public/me/cases
     * Заявки клиента, совпадающего по номеру телефона с публичным пользователем.
     */
    public function cases(Request $request): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $stages = config('stages');

        $cases = VisaCase::whereHas('client', fn ($q) => $q->where('phone', $publicUser->phone))
            ->with(['agency:id,name,city', 'assignee:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (VisaCase $case) use ($stages) {
                $stageConfig = $stages[$case->stage] ?? null;
                return [
                    'id'           => $case->id,
                    'country_code' => $case->country_code,
                    'visa_type'    => $case->visa_type,
                    'stage'        => $case->stage,
                    'stage_label'  => $stageConfig['label'] ?? $case->stage,
                    'stage_order'  => $stageConfig['order'] ?? 0,
                    'stage_msg'    => $stageConfig['client_msg'] ?? $case->stage,
                    'priority'     => $case->priority,
                    'critical_date'=> $case->critical_date?->toDateString(),
                    'travel_date'  => $case->travel_date?->toDateString(),
                    'created_at'   => $case->created_at->toDateString(),
                    'agency'       => $case->agency ? ['name' => $case->agency->name, 'city' => $case->agency->city] : null,
                    'assignee'     => $case->assignee ? ['name' => $case->assignee->name] : null,
                ];
            });

        return ApiResponse::success($cases);
    }

    /**
     * POST /public/me/passport
     * Загрузить фото паспорта для OCR (заглушка — реальный OCR через Queue).
     */
    public function uploadPassport(Request $request): JsonResponse
    {
        $request->validate([
            'passport' => 'required|image|max:10240',
        ]);

        $user = $request->get('_public_user');

        $path = $request->file('passport')->store("public-passports/{$user->id}", 'public');

        $user->update([
            'ocr_status'   => 'pending',
            'ocr_raw_data' => ['file_path' => $path],
        ]);

        // TODO: dispatch(new ProcessPassportOcr($user->id, $path));

        return ApiResponse::success([
            'ocr_status' => 'pending',
            'message'    => 'Паспорт загружен. Данные распознаются автоматически (обычно до 30 сек).',
        ]);
    }
}
