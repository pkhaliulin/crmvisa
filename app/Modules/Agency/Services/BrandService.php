<?php

namespace App\Modules\Agency\Services;

use App\Modules\Agency\Models\Agency;
use Illuminate\Http\Request;

class BrandService
{
    public function resolve(Request $request): BrandConfig
    {
        $agency = $request->attributes->get('brand_agency');

        if (! $agency) {
            return $this->defaultBrand();
        }

        return new BrandConfig(
            agencyName: $agency->name ?? 'VisaBor',
            logoUrl: $agency->logo_url,
            primaryColor: $agency->primary_color ?? '#0A1F44',
            secondaryColor: $agency->secondary_color ?? '#1BA97F',
            faviconUrl: $agency->favicon_url,
            agencySlug: $agency->slug,
            isWhiteLabel: true,
        );
    }

    public function resolveBySlug(string $slug): ?BrandConfig
    {
        $agency = Agency::where('slug', $slug)->where('is_active', true)->first();

        if (! $agency) {
            return null;
        }

        return new BrandConfig(
            agencyName: $agency->name ?? 'VisaBor',
            logoUrl: $agency->logo_url,
            primaryColor: $agency->primary_color ?? '#0A1F44',
            secondaryColor: $agency->secondary_color ?? '#1BA97F',
            faviconUrl: $agency->favicon_url,
            agencySlug: $agency->slug,
            isWhiteLabel: true,
        );
    }

    private function defaultBrand(): BrandConfig
    {
        return new BrandConfig(
            agencyName: 'VisaBor',
            logoUrl: null,
            primaryColor: '#0A1F44',
            secondaryColor: '#1BA97F',
            faviconUrl: null,
            agencySlug: null,
            isWhiteLabel: false,
        );
    }
}
