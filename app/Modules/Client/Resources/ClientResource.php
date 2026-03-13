<?php

namespace App\Modules\Client\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Флаг для включения PII-полей (паспортные данные).
     */
    protected bool $withPii = false;

    /**
     * Включить PII-данные в ответ.
     */
    public function withPii(bool $value = true): static
    {
        $this->withPii = $value;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $data = [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'nationality' => $this->nationality,
            'source'      => $this->source,
            'public_user_id' => $this->public_user_id,
            'notes'       => $this->notes,
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];

        // PII-поля только при явном запросе
        if ($this->withPii || ($this->additional['withPii'] ?? false)) {
            $data['passport_number']     = $this->passport_number;
            $data['date_of_birth']       = $this->date_of_birth;
            $data['passport_expires_at'] = $this->passport_expires_at;
        }

        // Вложенные связи, если загружены
        $data['cases'] = $this->when(
            $this->relationLoaded('cases'),
            fn () => $this->cases
        );

        return $data;
    }
}
