<?php

namespace App\Modules\Document\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Document\Services\DocumentGenerationService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentGenerationController extends Controller
{
    public function __construct(private DocumentGenerationService $generator) {}

    /**
     * GET /cases/{caseId}/generate-document/types
     * Список доступных типов документов для генерации.
     */
    public function types(): JsonResponse
    {
        return ApiResponse::success(DocumentGenerationService::availableTypes());
    }

    /**
     * POST /cases/{caseId}/generate-document
     * Сгенерировать документ через AI.
     * Роли: owner, manager.
     */
    public function generate(Request $request, string $caseId): JsonResponse
    {
        $case = VisaCase::where('id', $caseId)
            ->where('agency_id', $request->user()->agency_id)
            ->firstOrFail();

        $data = $request->validate([
            'type'             => ['required', 'string', 'in:cover_letter,employer_reference,sponsor_letter,travel_plan'],
            'language'         => ['sometimes', 'string', 'in:ru,uz,en'],
            'travel_purpose'   => ['sometimes', 'string', 'max:255'],
            'travel_date'      => ['sometimes', 'date'],
            'return_date'      => ['sometimes', 'date'],
            'employer_name'    => ['sometimes', 'string', 'max:255'],
            'position'         => ['sometimes', 'string', 'max:255'],
            'monthly_income'   => ['sometimes', 'numeric', 'min:0'],
            'years_employed'   => ['sometimes', 'numeric', 'min:0'],
            'sponsor_name'     => ['sometimes', 'string', 'max:255'],
            'sponsor_relation' => ['sometimes', 'string', 'max:255'],
            'cities'           => ['sometimes', 'string', 'max:500'],
            'applicant_name'   => ['sometimes', 'string', 'max:255'],
        ]);

        $this->generator->setContext(
            $request->user()->agency_id,
            $request->user()->id,
        );

        $result = $this->generator->generate($case, $data['type'], $data);

        return ApiResponse::success($result, 'Документ сгенерирован');
    }
}
