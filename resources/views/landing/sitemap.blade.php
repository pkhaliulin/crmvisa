<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
    <url>
        <loc>{{ url('/') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
        <xhtml:link rel="alternate" hreflang="ru" href="{{ url('/') }}?lang=ru" />
        <xhtml:link rel="alternate" hreflang="uz" href="{{ url('/') }}?lang=uz" />
    </url>
    <url>
        <loc>{{ url('/about') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>{{ url('/privacy') }}</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc>{{ url('/terms') }}</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
@foreach($countries as $country)
    <url>
        <loc>{{ url('/country/' . strtolower($country->country_code)) }}</loc>
        <lastmod>{{ $country->updated_at?->toW3cString() ?? now()->toW3cString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
@endforeach
</urlset>
