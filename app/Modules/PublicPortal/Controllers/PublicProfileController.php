<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Helpers\ApiResponse;
use App\Support\Rules\ReferenceExists;
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
            'passport_number'    => ['sometimes', 'nullable', 'string', 'regex:/^[A-Z]{2}[0-9]{7}$/'],
            'passport_expires_at'=> 'sometimes|date|after:today',
            'employment_type'    => ['sometimes', new ReferenceExists('employment_type')],
            'monthly_income_usd' => 'sometimes|integer|min:0',
            'marital_status'     => ['sometimes', new ReferenceExists('marital_status')],
            'has_children'       => 'sometimes|boolean',
            'children_count'     => 'sometimes|integer|min:0|max:20',
            'has_property'       => 'sometimes|boolean',
            'has_car'            => 'sometimes|boolean',
            'has_schengen_visa'     => 'sometimes|boolean',
            'has_us_visa'           => 'sometimes|boolean',
            'had_visa_refusal'      => 'sometimes|boolean',
            'had_overstay'          => 'sometimes|boolean',
            'had_deportation'       => 'sometimes|boolean',
            'visas_obtained_count'  => 'sometimes|integer|min:0|max:50',
            'refusals_count'        => 'sometimes|integer|min:0|max:20',
            'refusal_countries'     => 'sometimes|array',
            'refusal_countries.*'   => 'string|size:2',
            'last_refusal_year'     => 'sometimes|nullable|integer|min:2000|max:2099',
            'employed_years'        => 'sometimes|integer|min:0|max:50',
            'education_level'       => ['sometimes', 'nullable', new ReferenceExists('education_level')],
            'recovery_email'        => 'sometimes|nullable|email|max:255',
        ]);

        $user->update($data);

        $freshUser = $user->fresh();

        return ApiResponse::success([
            'user'            => $freshUser,
            'profile_percent' => $freshUser->profileCompleteness(),
        ], 'Профиль обновлён');
    }

    /**
     * POST /public/me/passport
     * Загрузить фото паспорта для OCR (заглушка — реальный OCR через Queue).
     */
    public function uploadPassport(Request $request): JsonResponse
    {
        $request->validate([
            'passport' => ['required', 'image', 'max:10240', new \App\Rules\SafeFileName],
        ]);

        $user = $request->get('_public_user');

        $path = $request->file('passport')->store("passports/{$user->id}", 'documents');

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
