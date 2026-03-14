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
        // Подтягиваем данные из public_users (единый источник)
        $pu = $this->public_user_id ? $this->publicUser : null;

        $data = [
            'id'          => $this->id,
            'name'        => $this->name ?: $pu?->name,
            'email'       => $this->email,
            'phone'       => $this->phone ?: $pu?->phone,
            'nationality' => $this->nationality ?: ($pu?->citizenship ? $this->iso2ToIso3($pu->citizenship) : null),
            'source'      => $this->source,
            'public_user_id' => $this->public_user_id,
            'notes'       => $this->notes,
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];

        // PII-поля только при явном запросе
        if ($this->withPii || ($this->additional['withPii'] ?? false)) {
            $data['passport_number']     = $this->passport_number ?: $pu?->passport_number;
            $data['date_of_birth']       = $this->date_of_birth ?: $pu?->dob;
            $data['passport_expires_at'] = $this->passport_expires_at ?: $pu?->passport_expires_at;
        }

        // Вложенные связи, если загружены
        $data['cases'] = $this->when(
            $this->relationLoaded('cases'),
            fn () => $this->cases
        );

        return $data;
    }

    private function iso2ToIso3(?string $iso2): ?string
    {
        if (!$iso2) return null;
        $map = [
            'UZ' => 'UZB', 'RU' => 'RUS', 'KZ' => 'KAZ', 'TJ' => 'TJK',
            'KG' => 'KGZ', 'TM' => 'TKM', 'UA' => 'UKR', 'GE' => 'GEO',
            'AZ' => 'AZE', 'AM' => 'ARM', 'BY' => 'BLR', 'MD' => 'MDA',
            'TR' => 'TUR', 'CN' => 'CHN', 'IN' => 'IND', 'AF' => 'AFG',
            'PK' => 'PAK', 'BD' => 'BGD', 'IR' => 'IRN', 'IQ' => 'IRQ',
        ];
        return $map[strtoupper(trim($iso2))] ?? strtoupper($iso2);
    }
}
