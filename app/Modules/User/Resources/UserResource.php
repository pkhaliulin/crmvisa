<?php

namespace App\Modules\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'role'       => $this->role,
            'is_active'  => $this->is_active,
            'avatar_url' => $this->avatar_url,
            'created_at' => $this->created_at?->toDateString(),

            // Дополнительные поля, если загружены через additional()
            'cases_count'        => $this->when(isset($this->cases_count), $this->cases_count),
            'active_cases_count' => $this->when(isset($this->active_cases_count), $this->active_cases_count),
        ];
    }
}
