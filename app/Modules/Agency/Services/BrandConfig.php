<?php

namespace App\Modules\Agency\Services;

class BrandConfig
{
    public function __construct(
        public readonly string $agencyName,
        public readonly ?string $logoUrl,
        public readonly string $primaryColor,
        public readonly string $secondaryColor,
        public readonly ?string $faviconUrl,
        public readonly ?string $agencySlug,
        public readonly bool $isWhiteLabel,
    ) {}

    public function toArray(): array
    {
        return [
            'agency_name'     => $this->agencyName,
            'logo_url'        => $this->logoUrl,
            'primary_color'   => $this->primaryColor,
            'secondary_color' => $this->secondaryColor,
            'favicon_url'     => $this->faviconUrl,
            'agency_slug'     => $this->agencySlug,
            'is_white_label'  => $this->isWhiteLabel,
        ];
    }
}
