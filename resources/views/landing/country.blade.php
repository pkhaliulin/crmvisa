@extends('landing.layout')

@section('meta')
<title>Виза в {{ $country->name }} для граждан Узбекистана -- visabor.uz</title>
<meta name="description" content="Виза в {{ $country->name }} для граждан Узбекистана. AI-скоринг шансов, требования, сроки, стоимость. Проверьте вероятность одобрения визы в {{ $country->name }}.">
<meta name="keywords" content="виза {{ $country->name }}, виза {{ $country->name }} Узбекистан, шансы на визу {{ $country->name }}, {{ $country->name }} виза требования, visabor">
<meta property="og:title" content="Виза в {{ $country->name }} для граждан Узбекистана -- visabor.uz">
<meta property="og:description" content="AI-скоринг шансов на визу в {{ $country->name }}. Требования, сроки, стоимость визы.">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url('/country/' . strtolower($country->country_code)) }}">
<link rel="canonical" href="{{ url('/country/' . strtolower($country->country_code)) }}">
@endsection

@section('structured_data')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {"@type": "ListItem", "position": 1, "name": "Главная", "item": "{{ url('/') }}"},
        {"@type": "ListItem", "position": 2, "name": "Направления", "item": "{{ url('/#destinations') }}"},
        {"@type": "ListItem", "position": 3, "name": "{{ $country->name }}"}
    ]
}
</script>
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        {
            "@type": "Question",
            "name": "Какие шансы на визу в {{ $country->name }} для граждан Узбекистана?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Шансы зависят от вашего профиля. AI-скоринг visabor.uz анализирует 12+ факторов и даёт персональную оценку. Средний минимальный скоринг для {{ $country->name }}: {{ $country->min_score ?? 'N/A' }}%."
            }
        },
        {
            "@type": "Question",
            "name": "Сколько стоит виза в {{ $country->name }}?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Консульский сбор составляет {{ $country->visa_fee_usd ? '$' . $country->visa_fee_usd : 'уточняется' }}. Дополнительные расходы зависят от типа визы и агентства."
            }
        },
        {
            "@type": "Question",
            "name": "Есть ли посольство {{ $country->name }} в Узбекистане?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "{{ $country->has_embassy ? 'Да, посольство ' . $country->name . ' расположено в Ташкенте.' : 'Информация уточняется. Подача может осуществляться через визовый центр или посольство в соседней стране.' }}"
            }
        }
    ]
}
</script>
@endsection

@section('content')
<section class="section" style="padding-top: 120px;">
    <div class="container" style="max-width: 900px;">
        <!-- Breadcrumbs -->
        <div style="margin-bottom: 24px; font-size: 0.85rem; color: var(--text-muted);">
            <a href="/" style="color: var(--text-muted); text-decoration: none;">Главная</a>
            <span style="margin: 0 8px;">/</span>
            <a href="/#destinations" style="color: var(--text-muted); text-decoration: none;">Направления</a>
            <span style="margin: 0 8px;">/</span>
            <span style="color: var(--text-primary);">{{ $country->name }}</span>
        </div>

        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 32px;">
            <span style="font-size: 4rem; line-height: 1;">{{ $country->flag_emoji }}</span>
            <div>
                <h1 style="font-size: clamp(1.8rem, 3.5vw, 2.6rem); margin-bottom: 8px;">Виза в {{ $country->name }}</h1>
                <p style="font-size: 1.1rem; color: var(--text-secondary);">для граждан Узбекистана</p>
            </div>
        </div>

        <!-- Key info cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 48px;">
            <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius); padding: 20px;">
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 6px;">Визовый режим</div>
                <div style="font-family: var(--font-display); font-weight: 700; font-size: 1.1rem; color: var(--text-primary);">
                    @php
                        $regimeLabels = [
                            'visa_required' => 'Визовое',
                            'visa_free' => 'Безвизовое',
                            'visa_on_arrival' => 'По прилёту',
                            'evisa' => 'Электронная виза',
                        ];
                    @endphp
                    {{ $regimeLabels[$country->visa_regime] ?? $country->visa_regime }}
                </div>
            </div>
            @if($country->visa_fee_usd)
            <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius); padding: 20px;">
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 6px;">Консульский сбор</div>
                <div style="font-family: var(--font-display); font-weight: 700; font-size: 1.1rem; color: var(--text-primary);">${{ $country->visa_fee_usd }}</div>
            </div>
            @endif
            @if($country->min_score)
            <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius); padding: 20px;">
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 6px;">Мин. скоринг</div>
                <div style="font-family: var(--font-display); font-weight: 700; font-size: 1.1rem; color: var(--blue-bright);">{{ $country->min_score }}%</div>
            </div>
            @endif
            <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius); padding: 20px;">
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 6px;">Уровень риска</div>
                <div style="font-family: var(--font-display); font-weight: 700; font-size: 1.1rem;">
                    @php
                        $riskColors = ['low' => 'var(--green)', 'medium' => 'var(--amber)', 'high' => 'var(--red)'];
                        $riskLabels = ['low' => 'Низкий', 'medium' => 'Средний', 'high' => 'Высокий'];
                    @endphp
                    <span style="color: {{ $riskColors[$country->risk_level] ?? 'var(--text-primary)' }}">
                        {{ $riskLabels[$country->risk_level] ?? $country->risk_level ?? 'N/A' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Embassy info -->
        @if($country->has_embassy)
        <div style="background: var(--gray-50); border-radius: var(--radius-lg); padding: 28px; margin-bottom: 32px;">
            <h2 style="font-size: 1.3rem; margin-bottom: 16px;">Посольство {{ $country->name }} в Узбекистане</h2>
            @if($country->embassy_name)
            <p style="margin-bottom: 8px;"><strong>Название:</strong> {{ $country->embassy_name }}</p>
            @endif
            @if($country->embassy_address)
            <p style="margin-bottom: 8px;"><strong>Адрес:</strong> {{ $country->embassy_address }}</p>
            @endif
            @if($country->embassy_website)
            <p style="margin-bottom: 8px;"><strong>Сайт:</strong> <a href="{{ $country->embassy_website }}" target="_blank" rel="noopener" style="color: var(--blue-bright);">{{ $country->embassy_website }}</a></p>
            @endif
        </div>
        @endif

        <!-- Agencies for this country -->
        @if($agencyCount > 0)
        <div style="margin-bottom: 48px;">
            <h2 style="font-size: 1.3rem; margin-bottom: 20px;">Агентства, работающие с {{ $country->name }}</h2>
            <p style="color: var(--text-secondary); margin-bottom: 20px;">{{ $agencyCount }} {{ $agencyCount == 1 ? 'агентство' : ($agencyCount < 5 ? 'агентства' : 'агентств') }} на платформе visabor.uz оформляют визу в {{ $country->name }}.</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px;">
                @foreach($agencies as $agency)
                <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius); padding: 20px;">
                    <div style="font-family: var(--font-display); font-weight: 700; margin-bottom: 4px;">{{ $agency->name }}</div>
                    <div style="font-size: 0.85rem; color: var(--text-secondary);">{{ $agency->city }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- CTA -->
        <div style="background: linear-gradient(135deg, var(--navy), var(--navy-light)); border-radius: var(--radius-xl); padding: 48px; text-align: center; color: white;">
            <h2 style="color: white; margin-bottom: 12px;">Проверьте свои шансы на визу в {{ $country->name }}</h2>
            <p style="color: rgba(255,255,255,0.6); margin-bottom: 24px;">AI-скоринг проанализирует ваш профиль и покажет персональную вероятность одобрения</p>
            <a href="/#scoring" class="btn btn-primary btn-lg">Проверить шансы</a>
        </div>

        <!-- SEO text -->
        <div style="margin-top: 48px; font-size: 0.95rem; line-height: 1.8; color: var(--text-secondary);">
            <h2 style="font-size: 1.3rem; margin-bottom: 16px; color: var(--text-primary);">Виза в {{ $country->name }} для граждан Узбекистана: полное руководство</h2>
            <p style="margin-bottom: 16px;">
                {{ $country->name }} -- одно из популярных направлений для граждан Узбекистана.
                Платформа visabor.uz помогает оценить ваши шансы на получение визы ещё до подачи документов.
                Наш AI-скоринг анализирует более 12 факторов вашего профиля и даёт персональную оценку вероятности одобрения.
            </p>
            <p style="margin-bottom: 16px;">
                Для оформления визы в {{ $country->name }} рекомендуем пройти бесплатный скоринг на visabor.uz,
                чтобы узнать свои сильные и слабые стороны профиля. После этого вы сможете выбрать проверенное
                визовое агентство на нашей платформе и начать оформление.
            </p>
        </div>
    </div>
</section>
@endsection
