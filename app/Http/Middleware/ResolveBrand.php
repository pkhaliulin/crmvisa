<?php

namespace App\Http\Middleware;

use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Services\BrandService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveBrand
{
    public function __construct(
        private readonly BrandService $brandService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $agency = $this->resolveAgency($request);

        if ($agency) {
            $request->attributes->set('brand_agency', $agency);
        }

        $brand = $this->brandService->resolve($request);
        $request->attributes->set('brand', $brand);

        return $next($request);
    }

    private function resolveAgency(Request $request): ?Agency
    {
        // 1. Check X-Agency-Slug header
        $slug = $request->header('X-Agency-Slug');
        if ($slug) {
            return Agency::where('slug', $slug)->where('is_active', true)->first();
        }

        // 2. Check subdomain
        $host = $request->getHost();
        $baseDomain = config('app.domain', 'visabor.com');
        if (str_ends_with($host, '.' . $baseDomain) && $host !== $baseDomain && $host !== 'www.' . $baseDomain) {
            $subdomain = str_replace('.' . $baseDomain, '', $host);
            return Agency::where('slug', $subdomain)->where('is_active', true)->first();
        }

        // 3. Check custom domain
        if ($host !== $baseDomain && $host !== 'www.' . $baseDomain) {
            return Agency::where('custom_domain', $host)->where('is_active', true)->first();
        }

        return null;
    }
}
