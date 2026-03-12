<?php

namespace App\Modules\Document\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Document\Models\AiUsageLog;
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
     * GET /admin/ai-usage
     * Статистика использования AI: токены, стоимость, количество вызовов.
     */
    public function aiUsage(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);
        $since = now()->subDays($days);

        // Общая статистика
        $totals = AiUsageLog::where('created_at', '>=', $since)
            ->selectRaw("
                COUNT(*) as total_calls,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_calls,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_calls,
                SUM(prompt_tokens) as total_prompt_tokens,
                SUM(completion_tokens) as total_completion_tokens,
                SUM(total_tokens) as total_tokens,
                SUM(cost_usd) as total_cost_usd,
                AVG(duration_ms) as avg_duration_ms
            ")
            ->first();

        // По сервисам
        $byService = AiUsageLog::where('created_at', '>=', $since)
            ->selectRaw("
                service,
                COUNT(*) as calls,
                SUM(total_tokens) as tokens,
                SUM(cost_usd) as cost_usd
            ")
            ->groupBy('service')
            ->get();

        // По моделям
        $byModel = AiUsageLog::where('created_at', '>=', $since)
            ->selectRaw("
                model,
                provider,
                COUNT(*) as calls,
                SUM(total_tokens) as tokens,
                SUM(cost_usd) as cost_usd
            ")
            ->groupBy('model', 'provider')
            ->get();

        // По дням (для графика)
        $daily = AiUsageLog::where('created_at', '>=', $since)
            ->selectRaw("
                DATE(created_at) as date,
                COUNT(*) as calls,
                SUM(total_tokens) as tokens,
                SUM(cost_usd) as cost_usd
            ")
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        // По агентствам (топ-10)
        $byAgency = AiUsageLog::where('created_at', '>=', $since)
            ->whereNotNull('agency_id')
            ->selectRaw("
                agency_id,
                COUNT(*) as calls,
                SUM(total_tokens) as tokens,
                SUM(cost_usd) as cost_usd
            ")
            ->groupBy('agency_id')
            ->orderByDesc('cost_usd')
            ->limit(10)
            ->get();

        return ApiResponse::success([
            'period_days' => $days,
            'totals'      => $totals,
            'by_service'  => $byService,
            'by_model'    => $byModel,
            'daily'       => $daily,
            'by_agency'   => $byAgency,
            'balance_note' => 'OpenAI balance: check platform.openai.com/usage',
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
