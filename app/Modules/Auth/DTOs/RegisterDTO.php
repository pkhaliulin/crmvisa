<?php

namespace App\Modules\Auth\DTOs;

use Illuminate\Support\Str;

readonly class RegisterDTO
{
    public string $agencySlug;

    public function __construct(
        public string $agencyName,
        public string $email,
        public string $password,
        public string $ownerName,
        public ?string $phone = null,
        public string $country = 'UZ',
        public string $timezone = 'Asia/Tashkent',
    ) {
        $this->agencySlug = Str::slug($agencyName);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            agencyName: $data['agency_name'],
            email:      $data['email'],
            password:   $data['password'],
            ownerName:  $data['owner_name'],
            phone:      $data['phone'] ?? null,
            country:    $data['country'] ?? 'UZ',
            timezone:   $data['timezone'] ?? 'Asia/Tashkent',
        );
    }
}
