<?php

namespace App\Modules\Case\Resources;

use App\Modules\Client\Resources\ClientResource;
use App\Modules\User\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'case_number'            => $this->case_number,
            'stage'                  => $this->stage,
            'public_status'          => $this->public_status,
            'priority'               => $this->priority,
            'country_code'           => $this->country_code,
            'visa_type'              => $this->visa_type,
            'travel_date'            => $this->travel_date?->toDateString(),
            'critical_date'          => $this->critical_date?->toDateString(),
            'return_date'            => $this->return_date?->toDateString(),
            'appointment_date'       => $this->appointment_date?->toDateString(),
            'appointment_time'       => $this->appointment_time,
            'appointment_location'   => $this->appointment_location,
            'notes'                  => $this->notes,
            'submitted_at'           => $this->submitted_at?->toDateString(),
            'expected_result_date'   => $this->expected_result_date?->toDateString(),
            'result_type'            => $this->result_type,
            'result_notes'           => $this->result_notes,
            'visa_issued_at'         => $this->visa_issued_at?->toDateString(),
            'visa_received_at'       => $this->visa_received_at?->toDateString(),
            'visa_validity'          => $this->visa_validity,
            'rejection_reason'       => $this->rejection_reason,
            'can_reapply'            => $this->can_reapply,
            'reapply_recommendation' => $this->reapply_recommendation,
            'payment_status'         => $this->payment_status,
            'lock_version'           => $this->lock_version ?? 0,
            'lead_source'            => $this->lead_source,

            // Visa Case Engine
            'visa_case_rule_id'    => $this->visa_case_rule_id,
            'visa_subtype'         => $this->visa_subtype,
            'applicant_type'       => $this->applicant_type,
            'embassy_platform'     => $this->embassy_platform,
            'submission_method'    => $this->submission_method,
            'readiness_score'      => $this->readiness_score,
            'reference_number'     => $this->reference_number,
            'created_at'             => $this->created_at?->toDateTimeString(),
            'updated_at'             => $this->updated_at?->toDateTimeString(),

            // Computed fields (selectRaw в index)
            'docs_total'    => $this->when(isset($this->docs_total), (int) $this->docs_total),
            'docs_uploaded' => $this->when(isset($this->docs_uploaded), (int) $this->docs_uploaded),

            // Вложенные ресурсы
            'client'   => $this->when(
                $this->relationLoaded('client'),
                fn () => $this->client ? new ClientResource($this->client) : null
            ),
            'assignee' => $this->when(
                $this->relationLoaded('assignee'),
                fn () => $this->assignee ? new UserResource($this->assignee) : null
            ),
            'stageHistory' => $this->when(
                $this->relationLoaded('stageHistory'),
                fn () => $this->stageHistory
            ),
        ];
    }
}
