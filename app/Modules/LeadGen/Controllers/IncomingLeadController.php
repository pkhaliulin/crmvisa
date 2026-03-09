<?php

namespace App\Modules\LeadGen\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use App\Modules\LeadGen\Events\LeadIncoming;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class IncomingLeadController extends Controller
{
    /**
     * Valid ISO 3166-1 alpha-2 country codes.
     */
    private const VALID_COUNTRIES = [
        'AF','AL','DZ','AD','AO','AG','AR','AM','AU','AT','AZ','BS','BH','BD','BB','BY','BE','BZ','BJ','BT',
        'BO','BA','BW','BR','BN','BG','BF','BI','KH','CM','CA','CV','CF','TD','CL','CN','CO','KM','CG','CD',
        'CR','CI','HR','CU','CY','CZ','DK','DJ','DM','DO','EC','EG','SV','GQ','ER','EE','SZ','ET','FJ','FI',
        'FR','GA','GM','GE','DE','GH','GR','GD','GT','GN','GW','GY','HT','HN','HU','IS','IN','ID','IR','IQ',
        'IE','IL','IT','JM','JP','JO','KZ','KE','KI','KP','KR','KW','KG','LA','LV','LB','LS','LR','LY','LI',
        'LT','LU','MG','MW','MY','MV','ML','MT','MH','MR','MU','MX','FM','MD','MC','MN','ME','MA','MZ','MM',
        'NA','NR','NP','NL','NZ','NI','NE','NG','MK','NO','OM','PK','PW','PA','PG','PY','PE','PH','PL','PT',
        'QA','RO','RU','RW','KN','LC','VC','WS','SM','ST','SA','SN','RS','SC','SL','SG','SK','SI','SB','SO',
        'ZA','SS','ES','LK','SD','SR','SE','CH','SY','TW','TJ','TZ','TH','TL','TG','TO','TT','TN','TR','TM',
        'TV','UG','UA','AE','GB','US','UY','UZ','VU','VE','VN','YE','ZM','ZW','XX',
    ];

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'         => ['required', 'string', 'max:255'],
            'phone'        => ['required', 'string', 'max:30'],
            'email'        => ['nullable', 'email', 'max:255'],
            'country'      => ['nullable', 'string', 'size:2'],
            'visa_type'    => ['nullable', 'string', 'max:50'],
            'source'       => ['nullable', 'string', 'max:50'],
            'channel_code' => ['nullable', 'string', 'max:50'],
            'message'      => ['nullable', 'string', 'max:2000'],
            'travel_date'  => ['nullable', 'date'],
            'extra'        => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Ошибка валидации.', $validator->errors(), 422);
        }

        $data    = $validator->validated();
        $agency  = $request->attributes->get('agency');

        // Validate country code
        $countryCode = 'XX';
        if (!empty($data['country'])) {
            $upper = strtoupper($data['country']);
            $countryCode = in_array($upper, self::VALID_COUNTRIES) ? $upper : 'XX';
        }

        try {
            $result = DB::transaction(function () use ($data, $agency, $countryCode) {
                $client = new Client();
                $client->agency_id = $agency->id;
                $client->name      = $data['name'];
                $client->phone     = $data['phone'];
                $client->email     = $data['email'] ?? null;
                $client->source    = 'other';
                $client->notes     = $data['message'] ?? null;
                $client->save();

                $caseData = [
                    'agency_id'         => $agency->id,
                    'client_id'         => $client->id,
                    'stage'             => 'lead',
                    'public_status'     => 'submitted',
                    'priority'          => 'normal',
                    'lead_source'       => $data['source'] ?? 'api',
                    'lead_channel_code' => $data['channel_code'] ?? null,
                    'notes'             => $this->buildNotes($data),
                    'country_code'      => $countryCode,
                    'visa_type'         => $data['visa_type'] ?? 'tourist',
                ];

                if (!empty($data['travel_date'])) {
                    $caseData['travel_date'] = $data['travel_date'];
                }

                $case = new VisaCase();
                $case->forceFill($caseData);
                $case->save();

                return ['client' => $client, 'case' => $case];
            });

            $client = $result['client'];
            $case   = $result['case'];

            // Fire event for notification system
            LeadIncoming::dispatch(
                $case,
                $client,
                $agency->id,
                $data['source'] ?? 'api',
                $data['channel_code'] ?? null,
            );

            Log::channel('daily')->info('Incoming lead via API', [
                'agency_id'    => $agency->id,
                'client_id'    => $client->id,
                'case_id'      => $case->id,
                'source'       => $data['source'] ?? 'api',
                'channel_code' => $data['channel_code'] ?? null,
            ]);

            return ApiResponse::success([
                'client_id'   => $client->id,
                'case_id'     => $case->id,
                'case_number' => $case->case_number ?? $case->id,
                'status'      => 'lead',
            ], 'Лид принят.', 201);

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

        if (!empty($data['message'])) {
            $parts[] = $data['message'];
        }

        if (!empty($data['source'])) {
            $parts[] = "Источник: {$data['source']}";
        }

        if (!empty($data['channel_code'])) {
            $parts[] = "Канал: {$data['channel_code']}";
        }

        if (!empty($data['extra']) && is_array($data['extra'])) {
            foreach ($data['extra'] as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $parts[] = "{$key}: {$value}";
                }
            }
        }

        return implode("\n", $parts) ?: 'Лид из API';
    }
}
