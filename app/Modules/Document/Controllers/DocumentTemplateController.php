<?php

namespace App\Modules\Document\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Document\Models\DocumentTemplate;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentTemplateController extends Controller
{
    /**
     * GET /admin/document-templates
     * Список всех шаблонов с фильтрацией.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DocumentTemplate::query()->orderBy('sort_order');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        return ApiResponse::success($query->get());
    }

    /**
     * POST /admin/document-templates
     * Создать шаблон документа.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug'            => 'required|string|max:100|unique:document_templates,slug',
            'name'            => 'required|string|max:255',
            'category'        => ['required', Rule::in(['personal','financial','family','property','travel','employment','confirmation','other'])],
            'type'            => ['required', Rule::in(['upload','checkbox'])],
            'description'     => 'nullable|string',
            'is_repeatable'   => 'boolean',
            'metadata_schema' => 'nullable|array',
            'sort_order'      => 'integer',
            'is_active'       => 'boolean',
        ]);

        $template = DocumentTemplate::create($data);

        return ApiResponse::created($template, 'Шаблон документа создан');
    }

    /**
     * PATCH /admin/document-templates/{id}
     * Обновить шаблон.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $template = DocumentTemplate::findOrFail($id);

        $data = $request->validate([
            'slug'            => ['sometimes', 'string', 'max:100', Rule::unique('document_templates', 'slug')->ignore($id)],
            'name'            => 'sometimes|string|max:255',
            'category'        => ['sometimes', Rule::in(['personal','financial','family','property','travel','employment','confirmation','other'])],
            'type'            => ['sometimes', Rule::in(['upload','checkbox'])],
            'description'     => 'nullable|string',
            'is_repeatable'   => 'boolean',
            'metadata_schema' => 'nullable|array',
            'sort_order'      => 'integer',
            'is_active'       => 'boolean',
        ]);

        $template->update($data);

        return ApiResponse::success($template->fresh(), 'Шаблон обновлён');
    }

    /**
     * DELETE /admin/document-templates/{id}
     * Удалить шаблон (только если не используется).
     */
    public function destroy(string $id): JsonResponse
    {
        $template = DocumentTemplate::findOrFail($id);

        if ($template->countryRequirements()->exists()) {
            return ApiResponse::error('Нельзя удалить: шаблон привязан к требованиям стран. Сначала удалите привязки.', 422);
        }

        $template->delete();

        return ApiResponse::success(null, 'Шаблон удалён');
    }
}
