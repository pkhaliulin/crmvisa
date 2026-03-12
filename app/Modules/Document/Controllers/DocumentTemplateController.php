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
     */
    public function index(Request $request): JsonResponse
    {
        $query = DocumentTemplate::query()
            ->withCount('countryRequirements')
            ->orderBy('sort_order');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('active_only') && !$request->boolean('active_only')) {
            // show all including inactive
        } else {
            $query->where('is_active', true);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('slug', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        return ApiResponse::success($query->get());
    }

    /**
     * GET /admin/document-templates/{id}
     */
    public function show(string $id): JsonResponse
    {
        $template = DocumentTemplate::withCount('countryRequirements')
            ->with(['countryRequirements' => function ($q) {
                $q->orderBy('country_code')->orderBy('visa_type');
            }])
            ->findOrFail($id);

        return ApiResponse::success($template);
    }

    /**
     * POST /admin/document-templates
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->validationRules());

        $template = DocumentTemplate::create($data);

        return ApiResponse::created($template, 'Шаблон документа создан');
    }

    /**
     * PATCH /admin/document-templates/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $template = DocumentTemplate::findOrFail($id);

        $rules = $this->validationRules($id);
        // Make all fields optional on update
        foreach ($rules as $key => $rule) {
            if (is_string($rule)) {
                $rules[$key] = str_replace('required|', 'sometimes|', $rule);
            } elseif (is_array($rule)) {
                $rules[$key] = array_map(fn($r) => $r === 'required' ? 'sometimes' : $r, $rule);
            }
        }

        $data = $request->validate($rules);
        $template->update($data);

        return ApiResponse::success($template->fresh(), 'Шаблон обновлён');
    }

    /**
     * PATCH /admin/document-templates/{id}/toggle-ai
     */
    public function toggleAi(string $id): JsonResponse
    {
        $template = DocumentTemplate::findOrFail($id);
        $template->update(['ai_enabled' => !$template->ai_enabled]);

        return ApiResponse::success([
            'ai_enabled' => $template->fresh()->ai_enabled,
        ], $template->ai_enabled ? 'AI включён' : 'AI выключён');
    }

    /**
     * GET /admin/ai-providers
     */
    public function aiProviders(): JsonResponse
    {
        $providers = [
            [
                'value'         => 'openai',
                'label'         => 'GPT-4o-mini (OpenAI)',
                'cost_per_scan' => '$0.0003',
                'configured'    => !empty(config('services.ocr.openai_key')),
            ],
            [
                'value'         => 'claude',
                'label'         => 'Claude Haiku (Anthropic)',
                'cost_per_scan' => '$0.004',
                'configured'    => !empty(config('services.ocr.claude_key')),
            ],
            [
                'value'         => 'google',
                'label'         => 'Google Vision AI',
                'cost_per_scan' => '$0.0015',
                'configured'    => !empty(config('services.ocr.google_key')),
            ],
        ];

        return ApiResponse::success([
            'providers' => $providers,
            'current'   => config('services.ocr.provider', 'openai'),
        ]);
    }

    /**
     * DELETE /admin/document-templates/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $template = DocumentTemplate::findOrFail($id);

        if ($template->countryRequirements()->exists()) {
            return ApiResponse::error('Нельзя удалить: шаблон привязан к требованиям стран. Сначала удалите привязки.', null, 422);
        }

        $template->delete();

        return ApiResponse::success(null, 'Шаблон удалён');
    }

    private function validationRules(?string $ignoreId = null): array
    {
        return [
            'slug'                   => [
                'required', 'string', 'max:100',
                $ignoreId
                    ? Rule::unique('document_templates', 'slug')->ignore($ignoreId)
                    : 'unique:document_templates,slug',
            ],
            'name'                   => 'required|string|max:255',
            'category'               => ['required', Rule::in(['personal','financial','family','property','travel','employment','confirmation','other'])],
            'type'                   => ['required', Rule::in(['upload','checkbox'])],
            'description'            => 'nullable|string|max:1000',
            'default_responsibility' => ['sometimes', Rule::in(['client','agency'])],
            'is_repeatable'          => 'boolean',
            'metadata_schema'        => 'nullable|array',
            'sort_order'             => 'integer',
            'is_active'              => 'boolean',
            // AI fields
            'ai_enabled'             => 'boolean',
            'ai_extraction_schema'   => 'nullable|array',
            'ai_validation_rules'    => 'nullable|array',
            'ai_stop_factors'        => 'nullable|array',
            'ai_success_factors'     => 'nullable|array',
            'ai_risk_indicators'     => 'nullable|array',
            'manager_instructions'   => 'nullable|string|max:5000',
            'translation_required'   => 'boolean',
            'max_age_days'           => 'nullable|integer|min:1|max:3650',
            'confidence_criteria'    => 'nullable|array',
        ];
    }
}
