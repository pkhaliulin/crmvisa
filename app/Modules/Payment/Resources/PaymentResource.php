<?php

namespace App\Modules\Payment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'amount'         => $this->amount,
            'currency'       => $this->currency,
            'status'         => $this->status,
            'paid_at'        => $this->paid_at?->toDateTimeString(),
            'payment_method' => $this->provider,
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
