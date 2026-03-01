<?php

namespace App\Modules\Scoring\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Client\Models\Client;
use App\Modules\Scoring\Jobs\CalculateClientScoreJob;
use App\Modules\Scoring\Models\ClientProfile;
use App\Modules\Scoring\Models\ClientScore;
use App\Modules\Scoring\Services\ScoringEngine;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScoringController extends Controller
{
    public function __construct(private ScoringEngine $engine) {}

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
            'income_type'          => ['sometimes', 'in:official,informal,business,mixed'],
            'bank_balance'         => ['sometimes', 'integer', 'min:0'],
            'bank_history_months'  => ['sometimes', 'integer', 'min:0'],
            'bank_balance_stable'  => ['sometimes', 'boolean'],
            'has_fixed_deposit'    => ['sometimes', 'boolean'],
            'fixed_deposit_amount' => ['sometimes', 'integer', 'min:0'],
            'has_investments'      => ['sometimes', 'boolean'],
            'investments_amount'   => ['sometimes', 'integer', 'min:0'],
            // Block E
            'employment_type'      => ['sometimes', 'in:government,private,business_owner,self_employed,retired,student,unemployed'],
            'employer_name'        => ['sometimes', 'nullable', 'string', 'max:255'],
            'position'             => ['sometimes', 'nullable', 'string', 'max:255'],
            'position_level'       => ['sometimes', 'nullable', 'in:executive,senior,mid,junior,intern'],
            'years_at_current_job' => ['sometimes', 'numeric', 'min:0'],
            'total_work_experience'=> ['sometimes', 'numeric', 'min:0'],
            'has_employment_gaps'  => ['sometimes', 'boolean'],
            // Block FM
            'marital_status'       => ['sometimes', 'in:married,single,divorced,widowed'],
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
            'education_level'      => ['sometimes', 'in:none,secondary,bachelor,master,phd'],
            'has_criminal_record'  => ['sometimes', 'boolean'],
            'age'                  => ['sometimes', 'integer', 'min:18', 'max:100'],
            // Block G
            'travel_purpose'       => ['sometimes', 'in:tourism,business,education,treatment,family_visit'],
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

        // Асинхронный пересчёт
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
            return ApiResponse::notFound('Score not calculated yet. Trigger recalculation first.');
        }

        return ApiResponse::success($score);
    }

    /**
     * POST /clients/{id}/scoring/recalculate — немедленный синхронный пересчёт
     */
    public function recalculate(Request $request, string $clientId): JsonResponse
    {
        $client  = $this->resolveClient($request, $clientId);
        $profile = ClientProfile::firstOrCreate(['client_id' => $client->id]);

        $scores = $this->engine->calculateAll($profile);

        return ApiResponse::success(
            $scores->sortByDesc('score')->values(),
            'Scores recalculated.'
        );
    }

    /**
     * GET /scoring/countries — список стран с весами
     */
    public function countries(): JsonResponse
    {
        $data = \Illuminate\Support\Facades\DB::table('scoring_country_weights')
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
