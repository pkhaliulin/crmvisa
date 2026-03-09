<?php

namespace App\Modules\LeadGen\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class IncomingLeadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'         => ['required', 'string', 'max:255'],
            'phone'        => ['required', 'string', 'max:30'],
            'email'        => ['nullable', 'email', 'max:255'],
            'country'      => ['nullable', 'string', 'size:2'],
            'visa_type'    => ['nullable', 'string', 'max:50'],
            'source'       => ['nullable', 'string', 'max:50'],
            'message'      => ['nullable', 'string', 'max:2000'],
            'travel_date'  => ['nullable', 'date'],
            'extra'        => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Ошибка валидации.', $validator->errors(), 422);
        }

        $data    = $validator->validated();
        $agency  = $request->attributes->get('agency');

        try {
            $result = DB::transaction(function () use ($data, $agency) {
                // 1. Создаём или находим клиента по телефону в рамках агентства
                $client = Client::withoutGlobalScopes()
                    ->where('agency_id', $agency->id)
                    ->where('phone', $data['phone'])
                    ->first();

                if (! $client) {
                    $client = new Client();
                    $client->agency_id = $agency->id;
                    $client->name      = $data['name'];
                    $client->phone     = $data['phone'];
                    $client->email     = $data['email'] ?? null;
                    $client->source    = 'other';
                    $client->notes     = $data['message'] ?? null;
                    $client->save();
                }

                // 2. Создаём заявку (лид)
                $caseData = [
                    'agency_id'     => $agency->id,
                    'client_id'     => $client->id,
                    'stage'         => 'lead',
                    'public_status' => 'submitted',
                    'priority'      => 'normal',
                    'lead_source'   => $data['source'] ?? 'api',
                    'notes'         => $this->buildNotes($data),
                    'country_code'  => ! empty($data['country']) ? strtoupper($data['country']) : 'XX',
                    'visa_type'     => $data['visa_type'] ?? 'tourist',
                ];

                if (! empty($data['travel_date'])) {
                    $caseData['travel_date'] = $data['travel_date'];
                }

                $case = new VisaCase();
                $case->forceFill($caseData);
                $case->save();

                return [
                    'client_id' => $client->id,
                    'case_id'   => $case->id,
                    'case_number' => $case->case_number ?? $case->id,
                    'status'    => 'lead',
                ];
            });

            Log::channel('daily')->info('Incoming lead via API', [
                'agency_id' => $agency->id,
                'client_id' => $result['client_id'],
                'case_id'   => $result['case_id'],
                'source'    => $data['source'] ?? 'api',
            ]);

            return ApiResponse::success($result, 'Лид принят.', 201);

        } catch (\Throwable $e) {
            Log::error('Incoming lead error', [
                'agency_id' => $agency->id,
                'error'     => $e->getMessage(),
            ]);

            return ApiResponse::error('Не удалось создать лид.', null, 500);
        }
    }

    private function buildNotes(array $data): string
    {
        $parts = [];

        if (! empty($data['message'])) {
            $parts[] = $data['message'];
        }

        if (! empty($data['source'])) {
            $parts[] = "Источник: {$data['source']}";
        }

        if (! empty($data['extra']) && is_array($data['extra'])) {
            foreach ($data['extra'] as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $parts[] = "{$key}: {$value}";
                }
            }
        }

        return implode("\n", $parts) ?: 'Лид из API';
    }
}
