<?php

namespace App\Modules\Document\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Document\Models\CountryVisaRequirement;
use App\Modules\Document\Models\DocumentTemplate;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CountryRequirementController extends Controller
{
    /**
     * GET /admin/country-requirements
     * Список требований с фильтрами.
     *
     * ?country_code=DE&visa_type=tourist
     */
    public function index(Request $request): JsonResponse
    {
        $query = CountryVisaRequirement::with('template')->orderBy('country_code')->orderBy('display_order');

        if ($request->filled('country_code')) {
            $query->where('country_code', $request->country_code);
        }

        if ($request->filled('visa_type')) {
            $query->where('visa_type', $request->visa_type);
        }

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        return ApiResponse::success($query->get());
    }

    /**
     * GET /admin/country-requirements/countries
     * Список уникальных комбинаций страна+тип визы.
     */
    public function countries(): JsonResponse
    {
        $countries = CountryVisaRequirement::select('country_code', 'visa_type')
            ->distinct()
            ->orderBy('country_code')
            ->orderBy('visa_type')
            ->get();

        return ApiResponse::success($countries);
    }

    /**
     * POST /admin/country-requirements
     * Создать привязку шаблона к стране+типу визы.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country_code'         => 'required|string|size:2',
            'visa_type'            => 'required|string|max:50',
            'document_template_id' => 'required|uuid|exists:document_templates,id',
            'requirement_level'    => ['required', Rule::in(['required','recommended','confirmation_only'])],
            'notes'                => 'nullable|string',
            'override_metadata'    => 'nullable|array',
            'display_order'        => 'integer',
            'is_active'            => 'boolean',
            'effective_from'       => 'nullable|date',
            'effective_to'         => 'nullable|date|after_or_equal:effective_from',
        ]);

        // Проверяем уникальность
        $exists = CountryVisaRequirement::where('country_code', $data['country_code'])
            ->where('visa_type', $data['visa_type'])
            ->where('document_template_id', $data['document_template_id'])
            ->exists();

        if ($exists) {
            return ApiResponse::error('Такая привязка уже существует', 422);
        }

        $req = CountryVisaRequirement::create($data);

        return ApiResponse::created($req->load('template'), 'Требование создано');
    }

    /**
     * PATCH /admin/country-requirements/{id}
     * Обновить требование.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $req = CountryVisaRequirement::findOrFail($id);

        $data = $request->validate([
            'requirement_level' => ['sometimes', Rule::in(['required','recommended','confirmation_only'])],
            'notes'             => 'nullable|string',
            'override_metadata' => 'nullable|array',
            'display_order'     => 'integer',
            'is_active'         => 'boolean',
            'effective_from'    => 'nullable|date',
            'effective_to'      => 'nullable|date|after_or_equal:effective_from',
        ]);

        $req->update($data);

        return ApiResponse::success($req->fresh(['template']), 'Требование обновлено');
    }

    /**
     * DELETE /admin/country-requirements/{id}
     * Удалить требование.
     */
    public function destroy(string $id): JsonResponse
    {
        $req = CountryVisaRequirement::findOrFail($id);
        $req->delete();

        return ApiResponse::success(null, 'Требование удалено');
    }
}
