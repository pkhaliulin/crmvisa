<?php

namespace App\Modules\Agency\DTOs;

use Illuminate\Support\Str;

readonly class RegisterAgencyDTO
{
    public string $slug;

    public function __construct(
        public string  $name,
        public string  $email,
        public string  $country,
        public string  $timezone = 'UTC',
        public ?string $phone    = null,
        public ?string $logoPath = null,
        ?string        $slug     = null,
    ) {
        $this->slug = $slug ?? Str::slug($name);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name:     $data['name'],
            email:    $data['email'],
            country:  $data['country'],
            timezone: $data['timezone'] ?? 'UTC',
            phone:    $data['phone'] ?? null,
            logoPath: $data['logo_path'] ?? null,
            slug:     $data['slug'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name'     => $this->name,
            'slug'     => $this->slug,
            'email'    => $this->email,
            'phone'    => $this->phone,
            'country'  => $this->country,
            'timezone' => $this->timezone,
            'logo_path' => $this->logoPath,
        ];
    }
}
