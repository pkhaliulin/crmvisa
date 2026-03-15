<?php

namespace App\Modules\Scoring\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Client\Models\Client;
use App\Modules\Scoring\Jobs\CalculateClientScoreJob;
use App\Modules\Scoring\Models\ClientProfile;
use App\Modules\Scoring\Models\ClientScore;
use App\Modules\Scoring\Services\ScoringDataAdapter;
use App\Modules\Scoring\Services\UnifiedScoringEngine;
use App\Support\Helpers\ApiResponse;
use App\Support\Rules\ReferenceExists;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScoringController extends Controller
{
    public function __construct(private UnifiedScoringEngine $engine) {}

    /**
     * GET /clients/{id}/profile — получить или создать профиль
     */
    public function getProfile(Request $request, string $clientId): JsonResponse
    {
        $client  = $this->resolveClient($request, $clientId);
        $profile = ClientProfile::firstOrCreate(['client_id' => $client->id]);

        return ApiResponse::success($profile);
    }

    /**
     * POST /clients/{id}/profile — сохранить профиль и запустить пересчёт
     */
    public function saveProfile(Request $request, string $clientId): JsonResponse
    {
        $client = $this->resolveClient($request, $clientId);

        $data = $request->validate([
            // Block F
            'monthly_income'       => ['sometimes', 'integer', 'min:0'],
            'income_type'          => ['sometimes', new ReferenceExists('income_type')],
            'bank_balance'         => ['sometimes', 'integer', 'min:0'],
            'bank_history_months'  => ['sometimes', 'integer', 'min:0'],
            'bank_balance_stable'  => ['sometimes', 'boolean'],
            'has_fixed_deposit'    => ['sometimes', 'boolean'],
            'fixed_deposit_amount' => ['sometimes', 'integer', 'min:0'],
            'has_investments'      => ['sometimes', 'boolean'],
            'investments_amount'   => ['sometimes', 'integer', 'min:0'],
            // Block E
            'employment_type'      => ['sometimes', new ReferenceExists('employment_type')],
            'employer_name'        => ['sometimes', 'nullable', 'string', 'max:255'],
            'position'             => ['sometimes', 'nullable', 'string', 'max:255'],
            'position_level'       => ['sometimes', 'nullable', new ReferenceExists('position_level')],
            'years_at_current_job' => ['sometimes', 'numeric', 'min:0'],
            'total_work_experience'=> ['sometimes', 'numeric', 'min:0'],
            'has_employment_gaps'  => ['sometimes', 'boolean'],
            // Block FM
            'marital_status'       => ['sometimes', new ReferenceExists('marital_status')],
            'spouse_employed'      => ['sometimes', 'boolean'],
            'children_count'       => ['sometimes', 'integer', 'min:0'],
            'children_staying_home'=> ['sometimes', 'boolean'],
            'dependents_count'     => ['sometimes', 'integer', 'min:0'],
            // Block A
            'has_real_estate'      => ['sometimes', 'boolean'],
            'has_car'              => ['sometimes', 'boolean'],
            'has_business'         => ['sometimes', 'boolean'],
            // Block T
            'has_schengen_visa'    => ['sometimes', 'boolean'],
            'has_us_visa'          => ['sometimes', 'boolean'],
            'has_uk_visa'          => ['sometimes', 'boolean'],
            'previous_refusals'    => ['sometimes', 'integer', 'min:0'],
            'has_overstay'         => ['sometimes', 'boolean'],
            // Block P
            'education_level'      => ['sometimes', new ReferenceExists('education_level')],
            'has_criminal_record'  => ['sometimes', 'boolean'],
            'age'                  => ['sometimes', 'integer', 'min:18', 'max:100'],
            // Block G (legacy, принимаем но не используем в новом движке)
            'travel_purpose'       => ['sometimes', new ReferenceExists('travel_purpose')],
            'has_return_ticket'    => ['sometimes', 'boolean'],
            'has_hotel_booking'    => ['sometimes', 'boolean'],
            'has_invitation_letter'=> ['sometimes', 'boolean'],
            'trip_duration_days'   => ['sometimes', 'integer', 'min:0'],
            'sponsor_covers_expenses' => ['sometimes', 'boolean'],
        ]);

        $profile = ClientProfile::updateOrCreate(
            ['client_id' => $client->id],
            $data
        );

        // Асинхронный пересчёт через UnifiedScoringEngine
        CalculateClientScoreJob::dispatch($profile);

        return ApiResponse::success($profile, 'Profile saved. Score recalculation queued.');
    }

    /**
     * GET /clients/{id}/scoring — все страны
     */
    public function scores(Request $request, string $clientId): JsonResponse
    {
        $client = $this->resolveClient($request, $clientId);

        $scores = ClientScore::where('client_id', $client->id)
            ->orderByDesc('score')
            ->get();

        return ApiResponse::success($scores);
    }

    /**
     * GET /clients/{id}/scoring/{country} — детали по стране
     */
    public function scoreByCountry(Request $request, string $clientId, string $country): JsonResponse
    {
        $client = $this->resolveClient($request, $clientId);

        $score = ClientScore::where('client_id', $client->id)
            ->where('country_code', strtoupper($country))
            ->first();

        if (! $score) {
            // Рассчитать на лету через UnifiedScoringEngine
            $profile = ClientProfile::firstOrCreate(['client_id' => $client->id]);
            $data = ScoringDataAdapter::fromClientProfile($profile);
            $result = $this->engine->scoreForCountry($data, strtoupper($country));

            // Сохранить результат
            $score = ClientScore::updateOrCreate(
                ['client_id' => $client->id, 'country_code' => strtoupper($country)],
                [
                    'score'           => $result['score'],
                    'block_scores'    => $result['blocks'],
                    'flags'           => $result['flags'],
                    'recommendations' => $result['recommendations'],
                    'weak_blocks'     => collect($result['blocks'])->filter(fn ($v) => $v < 50)->keys()->toArray(),
                    'is_blocked'      => $result['is_blocked'],
                    'calculated_at'   => now(),
                ]
            );
        }

        return ApiResponse::success($score);
    }

    /**
     * GET /clients/{id}/scoring/recommendations — рекомендации по профилю
     */
    public function recommendations(Request $request, string $clientId): JsonResponse
    {
        $client  = $this->resolveClient($request, $clientId);
        $profile = ClientProfile::firstOrCreate(['client_id' => $client->id]);

        $data = ScoringDataAdapter::fromClientProfile($profile);
        $result = $this->engine->scoreProfile($data);

        return ApiResponse::success([
            'score'           => $result['score'],
            'blocks'          => $result['blocks'],
            'recommendations' => $result['recommendations'],
            'flags'           => $result['flags'],
        ]);
    }

    /**
     * POST /clients/{id}/scoring/recalculate — немедленный синхронный пересчёт
     */
    public function recalculate(Request $request, string $clientId): JsonResponse
    {
        $client  = $this->resolveClient($request, $clientId);
        $profile = ClientProfile::firstOrCreate(['client_id' => $client->id]);
        $data    = ScoringDataAdapter::fromClientProfile($profile);

        // Получить все страны из scoring_country_weights
        $countries = DB::table('scoring_country_weights')
            ->distinct()
            ->pluck('country_code');

        if ($countries->isEmpty()) {
            $countries = collect(['DE', 'ES', 'FR', 'IT', 'PL', 'CZ', 'GB', 'US', 'CA', 'KR', 'AE']);
        }

        $results = $countries->map(function ($cc) use ($data, $profile) {
            $result = $this->engine->scoreForCountry($data, $cc);

            return ClientScore::updateOrCreate(
                ['client_id' => $profile->client_id, 'country_code' => $cc],
                [
                    'score'           => $result['score'],
                    'block_scores'    => $result['blocks'],
                    'flags'           => $result['flags'],
                    'recommendations' => $result['recommendations'],
                    'weak_blocks'     => collect($result['blocks'])->filter(fn ($v) => $v < 50)->keys()->toArray(),
                    'is_blocked'      => $result['is_blocked'],
                    'calculated_at'   => now(),
                ]
            );
        });

        return ApiResponse::success(
            $results->sortByDesc('score')->values(),
            'Scores recalculated.'
        );
    }

    /**
     * GET /scoring/countries — список стран с весами
     */
    public function countries(): JsonResponse
    {
        $data = DB::table('scoring_country_weights')
            ->get()
            ->groupBy('country_code')
            ->map(fn ($rows) => $rows->pluck('weight', 'block_code'));

        return ApiResponse::success($data);
    }

    // -------------------------------------------------------------------------

    private function resolveClient(Request $request, string $clientId): Client
    {
        return Client::where('id', $clientId)
            ->where('agency_id', $request->user()->agency_id)
            ->firstOrFail();
    }
}
