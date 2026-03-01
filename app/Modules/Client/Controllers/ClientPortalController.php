<?php

namespace App\Modules\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use App\Modules\Document\Services\ChecklistService;
use App\Modules\Scoring\Models\ClientScore;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientPortalController extends Controller
{
    public function __construct(private readonly ChecklistService $checklist) {}

    /**
     * GET /api/v1/client/me
     * Профиль текущего клиента
     */
    public function me(Request $request): JsonResponse
    {
        $client = $this->resolveClient($request);

        return ApiResponse::success([
            'id'                 => $client->id,
            'name'               => $client->name,
            'email'              => $client->email,
            'phone'              => $client->phone,
            'nationality'        => $client->nationality,
            'passport_number'    => $client->passport_number,
            'passport_expires_at' => $client->passport_expires_at,
        ]);
    }

    /**
     * GET /api/v1/client/me/journey
     * Прогресс клиента по шагам: Профиль → Скоринг → Документы → Заявка → Виза
     */
    public function journey(Request $request): JsonResponse
    {
        $client = $this->resolveClient($request);

        // --- Шаг 1: Профиль ---
        $profileFilled = $client->passport_number && $client->nationality && $client->date_of_birth;
        $profileScore  = $this->calcProfileCompleteness($client);

        // --- Шаг 2: Скоринг ---
        $scores       = ClientScore::where('client_id', $client->id)->get();
        $hasScoring   = $scores->isNotEmpty();
        $bestScore    = $scores->max('score') ?? 0;
        $bestCountry  = $scores->sortByDesc('score')->first()?->country_code;

        // --- Шаг 3: Документы ---
        $activeCase = VisaCase::where('client_id', $client->id)
                              ->whereNotIn('stage', ['result'])
                              ->latest()
                              ->first();

        $docProgress = ['total' => 0, 'uploaded' => 0, 'percent' => 0];
        if ($activeCase) {
            $checklist   = $this->checklist->getForCase($activeCase->id);
            $docProgress = $checklist['progress'];
        }

        $hasDocs = $docProgress['uploaded'] > 0;

        // --- Шаг 4: Заявка ---
        $hasCase = $activeCase !== null;

        // --- Шаг 5: Виза ---
        $resultCase = VisaCase::where('client_id', $client->id)->where('stage', 'result')->latest()->first();
        $hasResult  = $resultCase !== null;

        // Считаем общий прогресс
        $steps = [
            ['key' => 'profile',   'label' => 'Профиль',   'done' => $profileFilled, 'progress' => $profileScore,            'data' => ['completeness' => $profileScore]],
            ['key' => 'scoring',   'label' => 'Скоринг',   'done' => $hasScoring,    'progress' => $hasScoring ? 100 : 0,     'data' => ['best_score' => $bestScore, 'best_country' => $bestCountry]],
            ['key' => 'documents', 'label' => 'Документы', 'done' => $hasDocs,       'progress' => $docProgress['percent'],   'data' => $docProgress],
            ['key' => 'case',      'label' => 'Заявка',    'done' => $hasCase,       'progress' => $hasCase ? 100 : 0,        'data' => ['stage' => $activeCase?->stage, 'case_id' => $activeCase?->id]],
            ['key' => 'visa',      'label' => 'Виза',      'done' => $hasResult,     'progress' => $hasResult ? 100 : 0,      'data' => ['result_case_id' => $resultCase?->id]],
        ];

        $overall = (int) collect($steps)->avg('progress');

        return ApiResponse::success([
            'steps'   => $steps,
            'overall' => $overall,
        ]);
    }

    /**
     * GET /api/v1/client/me/case
     * Активная заявка клиента
     */
    public function myCase(Request $request): JsonResponse
    {
        $client = $this->resolveClient($request);

        $case = VisaCase::where('client_id', $client->id)
                        ->with(['assignee:id,name', 'stageHistory'])
                        ->latest()
                        ->first();

        if (! $case) {
            return ApiResponse::notFound('No active case');
        }

        return ApiResponse::success([
            'id'            => $case->id,
            'country_code'  => $case->country_code,
            'visa_type'     => $case->visa_type,
            'stage'         => $case->stage,
            'priority'      => $case->priority,
            'critical_date' => $case->critical_date,
            'days_left'     => $case->critical_date ? now()->diffInDays($case->critical_date, false) : null,
            'manager'       => $case->assignee ? ['name' => $case->assignee->name] : null,
            'stage_history' => $case->stageHistory,
        ]);
    }

    /**
     * GET /api/v1/client/me/checklist
     * Чек-лист документов по активной заявке
     */
    public function myChecklist(Request $request): JsonResponse
    {
        $client = $this->resolveClient($request);

        $case = VisaCase::where('client_id', $client->id)
                        ->whereNotIn('stage', ['result'])
                        ->latest()
                        ->first();

        if (! $case) {
            return ApiResponse::notFound('No active case');
        }

        $data = $this->checklist->getForCase($case->id);

        return ApiResponse::success($data);
    }

    /**
     * POST /api/v1/client/me/checklist/{itemId}/upload
     * Клиент загружает файл в слот чек-листа
     */
    public function uploadDocument(Request $request, string $itemId): JsonResponse
    {
        $client = $this->resolveClient($request);

        $case = VisaCase::where('client_id', $client->id)
                        ->whereNotIn('stage', ['result'])
                        ->latest()
                        ->firstOrFail();

        $item = \App\Modules\Document\Models\CaseChecklist::where('id', $itemId)
                                                           ->where('case_id', $case->id)
                                                           ->firstOrFail();

        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $result = $this->checklist->uploadToSlot($item, $request->file('file'), $case, $request->user()->id);

        return ApiResponse::success($result, 'Document uploaded');
    }

    /**
     * GET /api/v1/client/me/scoring
     * Скоринг клиента
     */
    public function myScoring(Request $request): JsonResponse
    {
        $client = $this->resolveClient($request);

        $scores = ClientScore::where('client_id', $client->id)
                             ->orderByDesc('score')
                             ->get(['country_code', 'score', 'breakdown', 'calculated_at']);

        return ApiResponse::success($scores);
    }

    // -------------------------------------------------------------------------

    private function resolveClient(Request $request): Client
    {
        $client = Client::where('user_id', $request->user()->id)->first();

        if (! $client) {
            abort(404, 'Client profile not found');
        }

        return $client;
    }

    private function calcProfileCompleteness(Client $client): int
    {
        $fields  = ['name', 'email', 'phone', 'nationality', 'passport_number', 'date_of_birth', 'passport_expires_at'];
        $filled  = collect($fields)->filter(fn ($f) => ! empty($client->$f))->count();

        return (int) round($filled / count($fields) * 100);
    }
}
