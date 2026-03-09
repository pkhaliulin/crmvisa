<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Models\AgencyWorkCountry;
use App\Modules\Owner\Models\PortalCountry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LandingController extends Controller
{
    public function __construct()
    {
        DB::statement("SET app.is_public_user = 'true'");
    }

    /**
     * GET / — Главная страница (Blade, SSR для SEO)
     */
    public function index()
    {
        $countries = Cache::remember('landing:countries', 300, function () {
            return PortalCountry::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['country_code', 'name', 'name_uz', 'flag_emoji', 'visa_regime',
                    'visa_fee_usd', 'min_score', 'continent', 'risk_level',
                    'is_popular', 'is_high_approval']);
        });

        $agencies = Cache::remember('landing:agencies', 300, function () {
            return Agency::where('is_active', true)
                ->whereNull('blocked_at')
                ->with(['workCountries' => fn($q) => $q->where('is_active', true)])
                ->withCount(['reviews as total_reviews'])
                ->orderByDesc('total_reviews')
                ->limit(6)
                ->get(['id', 'name', 'city', 'plan', 'created_at']);
        });

        $stats = Cache::remember('landing:stats', 600, function () {
            return [
                'countries' => PortalCountry::where('is_active', true)->count(),
                'agencies' => Agency::where('is_active', true)->whereNull('blocked_at')->count(),
            ];
        });

        $locale = app()->getLocale();

        return view('landing.index', compact('countries', 'agencies', 'stats', 'locale'));
    }

    /**
     * GET /country/{code} — SEO-страница страны
     */
    public function country(string $code)
    {
        $country = PortalCountry::where('country_code', strtoupper($code))
            ->where('is_active', true)
            ->firstOrFail();

        $country->increment('view_count');

        $agencyCount = AgencyWorkCountry::where('country_code', strtoupper($code))
            ->where('is_active', true)
            ->whereHas('agency', fn($q) => $q->where('is_active', true))
            ->count();

        $agencies = Agency::where('is_active', true)
            ->whereNull('blocked_at')
            ->whereHas('workCountries', fn($q) => $q->where('country_code', strtoupper($code))->where('is_active', true))
            ->limit(6)
            ->get(['id', 'name', 'city']);

        return view('landing.country', compact('country', 'agencyCount', 'agencies'));
    }

    /**
     * GET /about — О платформе
     */
    public function about()
    {
        return view('landing.about');
    }

    /**
     * GET /privacy — Политика конфиденциальности
     */
    public function privacy()
    {
        return view('landing.privacy');
    }

    /**
     * GET /terms — Пользовательское соглашение
     */
    public function terms()
    {
        return view('landing.terms');
    }

    /**
     * GET /sitemap.xml — Динамический sitemap
     */
    public function sitemap()
    {
        $countries = PortalCountry::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['country_code', 'updated_at']);

        $content = view('landing.sitemap', compact('countries'))->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }
}
