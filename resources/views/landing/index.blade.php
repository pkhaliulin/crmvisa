@extends('landing.layout')

@section('meta')
<title>{{ __('landing.meta_title') }}</title>
<meta name="description" content="{{ __('landing.meta_desc') }}">
<meta name="keywords" content="{{ __('landing.meta_keywords') }}">
<meta property="og:title" content="{{ __('landing.og_title') }}">
<meta property="og:description" content="{{ __('landing.og_desc') }}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url('/') }}">
<link rel="canonical" href="{{ url('/') }}">
@endsection

@section('structured_data')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "Organization",
  "name": "visabor.uz",
  "url": "https://visabor.uz",
  "logo": "https://visabor.uz/logo.png",
  "contactPoint": {
    "@@type": "ContactPoint",
    "telephone": "+998-71-200-00-00",
    "contactType": "customer service",
    "availableLanguage": ["Russian", "Uzbek"]
  }
}
</script>
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "WebSite",
  "name": "visabor.uz",
  "url": "https://visabor.uz",
  "description": "{!! __('landing.schema_desc') !!}",
  "potentialAction": {
    "@@type": "SearchAction",
    "target": "https://visabor.uz/search?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "FAQPage",
  "mainEntity": [
    {
      "@@type": "Question",
      "name": "{!! __('landing.faq_q1') !!}",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "{!! __('landing.faq_a1') !!}"
      }
    },
    {
      "@@type": "Question",
      "name": "{!! __('landing.faq_q2') !!}",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "{!! __('landing.faq_a2') !!}"
      }
    },
    {
      "@@type": "Question",
      "name": "{!! __('landing.faq_q3') !!}",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "{!! __('landing.faq_a3') !!}"
      }
    },
    {
      "@@type": "Question",
      "name": "{!! __('landing.faq_q4') !!}",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "{!! __('landing.faq_a4') !!}"
      }
    },
    {
      "@@type": "Question",
      "name": "{!! __('landing.faq_q5') !!}",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "{!! __('landing.faq_a5') !!}"
      }
    },
    {
      "@@type": "Question",
      "name": "{!! __('landing.faq_q6') !!}",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "{!! __('landing.faq_a6') !!}"
      }
    }
  ]
}
</script>
@endsection

@section('content')
<!-- HERO -->
<section class="hero" id="hero">
  <div class="container">
    <div class="hero-inner">
      <div class="hero-content fade-up">
        <div class="hero-badge">
          <span class="dot"></span>
          {{ __('landing.hero_badge') }}
        </div>
        <h1>{!! __('landing.hero_h1') !!}</h1>
        <p class="hero-sub">
          {{ __('landing.hero_sub') }}
        </p>
        <div class="hero-actions">
          <a href="#scoring" class="btn btn-primary btn-lg">
            {{ __('landing.hero_cta') }}
          </a>
          <a href="#destinations" class="btn btn-secondary btn-lg">
            {{ __('landing.hero_cta2') }}
          </a>
        </div>
        <div class="hero-trust">
          <div class="trust-item">
            <span class="trust-icon">🛡️</span>
            <span class="trust-text">{!! __('landing.hero_trust1') !!}</span>
          </div>
          <div class="trust-item">
            <span class="trust-icon">⚡</span>
            <span class="trust-text">{!! __('landing.hero_trust2') !!}</span>
          </div>
          <div class="trust-item">
            <span class="trust-icon">🇺🇿</span>
            <span class="trust-text">{!! __('landing.hero_trust3') !!}</span>
          </div>
        </div>
      </div>
      <div class="hero-visual fade-up fade-up-delay-2">
        <div style="position:relative;">
          <div class="score-card">
            <div class="score-card-header">
              <span class="score-card-title">{{ __('landing.hero_score_title') }}</span>
              <span class="score-live"><span class="live-dot"></span>{{ __('landing.hero_score_live') }}</span>
            </div>
            <div class="ring-wrapper">
              <div class="score-ring">
                <svg width="180" height="180" viewBox="0 0 180 180">
                  <defs>
                    <linearGradient id="ringGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                      <stop offset="0%" style="stop-color:#0d9488"/>
                      <stop offset="100%" style="stop-color:#10b981"/>
                    </linearGradient>
                  </defs>
                  <circle cx="90" cy="90" r="80" class="ring-bg"/>
                  <circle cx="90" cy="90" r="80" class="ring-fill" id="heroRing"/>
                </svg>
                <div class="ring-center">
                  <span class="ring-percent" id="heroPercent">75%</span>
                  <span class="ring-label">{{ __('landing.hero_probability') }}</span>
                </div>
              </div>
            </div>
            <div class="score-insights">
              <div class="insight-row">
                <span class="insight-icon">✅</span>
                <span class="insight-text">{{ __('landing.hero_insight1') }}</span>
                <span class="insight-badge badge-green">{{ __('landing.hero_insight1_badge') }}</span>
              </div>
              <div class="insight-row">
                <span class="insight-icon">⚠️</span>
                <span class="insight-text">{{ __('landing.hero_insight2') }}</span>
                <span class="insight-badge badge-amber">{{ __('landing.hero_insight2_badge') }}</span>
              </div>
              <div class="insight-row">
                <span class="insight-icon">✅</span>
                <span class="insight-text">{{ __('landing.hero_insight3') }}</span>
                <span class="insight-badge badge-green">{{ __('landing.hero_insight3_badge') }}</span>
              </div>
            </div>
            <a href="#scoring" class="btn btn-primary score-cta">{{ __('landing.hero_score_cta') }}</a>
          </div>
          <div class="float-card float-card-1">{{ __('landing.hero_float1') }}</div>
          <div class="float-card float-card-2">{{ __('landing.hero_float2') }}</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- AI SCORING -->
<section class="scoring-section" id="scoring">
  <div class="container">
    <div class="scoring-wrapper">
      <div class="scoring-left fade-up">
        <div class="tag">{{ __('landing.scoring_tag') }}</div>
        <h2 style="margin-top:16px;">{!! __('landing.scoring_h2') !!}</h2>
        <p>{{ __('landing.scoring_desc') }}</p>
        <div style="display:flex; flex-direction:column; gap:16px;">
          <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(22,163,74,0.08);display:flex;align-items:center;justify-content:center;font-size:1.2rem;">⚡</div>
            <div>
              <div style="font-family:var(--font-display);font-weight:700;font-size:0.9rem;">{{ __('landing.scoring_feat1_title') }}</div>
              <div style="font-size:0.82rem;color:var(--text-secondary);">{{ __('landing.scoring_feat1_desc') }}</div>
            </div>
          </div>
          <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(16,185,129,0.08);display:flex;align-items:center;justify-content:center;font-size:1.2rem;">🎯</div>
            <div>
              <div style="font-family:var(--font-display);font-weight:700;font-size:0.9rem;">{{ __('landing.scoring_feat2_title') }}</div>
              <div style="font-size:0.82rem;color:var(--text-secondary);">{{ __('landing.scoring_feat2_desc') }}</div>
            </div>
          </div>
          <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(6,182,212,0.08);display:flex;align-items:center;justify-content:center;font-size:1.2rem;">🔒</div>
            <div>
              <div style="font-family:var(--font-display);font-weight:700;font-size:0.9rem;">{{ __('landing.scoring_feat3_title') }}</div>
              <div style="font-size:0.82rem;color:var(--text-secondary);">{{ __('landing.scoring_feat3_desc') }}</div>
            </div>
          </div>
        </div>
      </div>
      <div class="fade-up fade-up-delay-2">
        <div class="questionnaire" id="questionnaire">
          <div class="q-header">
            <span class="q-header-title" id="qTitle">AI Visa Chance Score</span>
            <span class="q-progress-text" id="qProgress">{{ __('landing.q_step', ['current' => 1, 'total' => 6]) }}</span>
          </div>
          <div class="q-progress-bar"><div class="q-progress-fill" id="qProgressFill"></div></div>
          <div class="q-body">
            <!-- Step 1 -->
            <div class="q-step active" id="step1">
              <div class="q-question">{{ __('landing.q1') }}</div>
              <div class="q-options">
                <div class="q-option" onclick="selectOption(this,'visa_yes')"><span class="opt-icon">✅</span> {{ __('landing.q1_o1') }}</div>
                <div class="q-option" onclick="selectOption(this,'visa_sheng')"><span class="opt-icon">🇪🇺</span> {{ __('landing.q1_o2') }}</div>
                <div class="q-option" onclick="selectOption(this,'visa_us')"><span class="opt-icon">🇺🇸</span> {{ __('landing.q1_o3') }}</div>
                <div class="q-option" onclick="selectOption(this,'visa_no')"><span class="opt-icon">🚫</span> {{ __('landing.q1_o4') }}</div>
              </div>
            </div>
            <!-- Step 2 -->
            <div class="q-step" id="step2">
              <div class="q-question">{{ __('landing.q2') }}</div>
              <div class="q-options">
                <div class="q-option" onclick="selectOption(this,'emp_official')"><span class="opt-icon">🏢</span> {{ __('landing.q2_o1') }}</div>
                <div class="q-option" onclick="selectOption(this,'emp_biz')"><span class="opt-icon">💼</span> {{ __('landing.q2_o2') }}</div>
                <div class="q-option" onclick="selectOption(this,'emp_student')"><span class="opt-icon">🎓</span> {{ __('landing.q2_o3') }}</div>
                <div class="q-option" onclick="selectOption(this,'emp_other')"><span class="opt-icon">📋</span> {{ __('landing.q2_o4') }}</div>
              </div>
            </div>
            <!-- Step 3 -->
            <div class="q-step" id="step3">
              <div class="q-question">{{ __('landing.q3') }}</div>
              <div class="q-options">
                <div class="q-option" onclick="selectOption(this,'tie_both')"><span class="opt-icon">🏠</span> {{ __('landing.q3_o1') }}</div>
                <div class="q-option" onclick="selectOption(this,'tie_family')"><span class="opt-icon">👨‍👩‍👧</span> {{ __('landing.q3_o2') }}</div>
                <div class="q-option" onclick="selectOption(this,'tie_prop')"><span class="opt-icon">🏗️</span> {{ __('landing.q3_o3') }}</div>
                <div class="q-option" onclick="selectOption(this,'tie_none')"><span class="opt-icon">—</span> {{ __('landing.q3_o4') }}</div>
              </div>
            </div>
            <!-- Step 4 -->
            <div class="q-step" id="step4">
              <div class="q-question">{{ __('landing.q4') }}</div>
              <div class="q-options">
                <div class="q-option" onclick="selectOption(this,'inc_high')"><span class="opt-icon">💰</span> {{ __('landing.q4_o1') }}</div>
                <div class="q-option" onclick="selectOption(this,'inc_mid')"><span class="opt-icon">💵</span> {{ __('landing.q4_o2') }}</div>
                <div class="q-option" onclick="selectOption(this,'inc_low')"><span class="opt-icon">💴</span> {{ __('landing.q4_o3') }}</div>
                <div class="q-option" onclick="selectOption(this,'inc_var')"><span class="opt-icon">📊</span> {{ __('landing.q4_o4') }}</div>
              </div>
            </div>
            <!-- Step 5 -->
            <div class="q-step" id="step5">
              <div class="q-question">{{ __('landing.q5') }}</div>
              <div class="q-options">
                <div class="q-option" onclick="selectOption(this,'goal_tour')"><span class="opt-icon">✈️</span> {{ __('landing.q5_o1') }}</div>
                <div class="q-option" onclick="selectOption(this,'goal_biz')"><span class="opt-icon">🤝</span> {{ __('landing.q5_o2') }}</div>
                <div class="q-option" onclick="selectOption(this,'goal_study')"><span class="opt-icon">📚</span> {{ __('landing.q5_o3') }}</div>
                <div class="q-option" onclick="selectOption(this,'goal_med')"><span class="opt-icon">🏥</span> {{ __('landing.q5_o4') }}</div>
              </div>
            </div>
            <!-- Step 6 -->
            <div class="q-step" id="step6">
              <div class="q-question">{{ __('landing.q6') }}</div>
              <div class="q-options">
                <div class="q-option" onclick="selectOption(this,'dest_eu')"><span class="opt-icon">🇪🇺</span> {{ __('landing.q6_o1') }}</div>
                <div class="q-option" onclick="selectOption(this,'dest_asia')"><span class="opt-icon">🌏</span> {{ __('landing.q6_o2') }}</div>
                <div class="q-option" onclick="selectOption(this,'dest_us')"><span class="opt-icon">🌎</span> {{ __('landing.q6_o3') }}</div>
                <div class="q-option" onclick="selectOption(this,'dest_any')"><span class="opt-icon">🌍</span> {{ __('landing.q6_o4') }}</div>
              </div>
            </div>
          </div>
          <div class="q-footer" id="qFooter">
            <div class="q-nav">
              <button class="btn btn-sm q-btn-back" id="qBack" onclick="qNav(-1)" style="display:none">{{ __('landing.q_back') }}</button>
            </div>
            <button class="btn btn-sm btn-primary q-btn-next" id="qNext" onclick="qNav(1)">{{ __('landing.q_next') }}</button>
          </div>
        </div>
        <!-- RESULT -->
        <div class="score-result" id="scoreResult">
          <div class="questionnaire" style="border:none;box-shadow:none;">
            <div class="q-header" style="background: linear-gradient(135deg,#059669,#0d9488);">
              <span class="q-header-title">{{ __('landing.result_ready') }}</span>
              <span class="q-progress-text">visabor.uz</span>
            </div>
            <div style="padding:32px 28px; text-align:center;">
              <div class="result-ring">
                <svg width="160" height="160" viewBox="0 0 160 160">
                  <defs>
                    <linearGradient id="resultGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                      <stop offset="0%" style="stop-color:#0d9488"/>
                      <stop offset="100%" style="stop-color:#10b981"/>
                    </linearGradient>
                  </defs>
                  <circle cx="80" cy="80" r="70" fill="none" stroke="#f1f5f9" stroke-width="12"/>
                  <circle cx="80" cy="80" r="70" fill="none" stroke="url(#resultGrad)" stroke-width="12"
                    stroke-linecap="round" stroke-dasharray="440" stroke-dashoffset="110"
                    style="transform:rotate(-90deg);transform-origin:50% 50%;"/>
                </svg>
                <div class="result-ring-center">
                  <div class="result-percent">75%</div>
                  <div class="result-label">{{ __('landing.result_chance') }}</div>
                </div>
              </div>
              <div class="result-title">{{ __('landing.result_good') }}</div>
              <div class="result-desc">{{ __('landing.result_desc') }}</div>
              <div class="result-tags">
                <span class="rtag rtag-green">{{ __('landing.result_tag1') }}</span>
                <span class="rtag rtag-green">{{ __('landing.result_tag2') }}</span>
                <span class="rtag rtag-amber">{{ __('landing.result_tag3') }}</span>
                <span class="rtag rtag-green">{{ __('landing.result_tag4') }}</span>
              </div>
              <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:16px;margin-bottom:20px;text-align:left;">
                <div style="font-family:var(--font-display);font-weight:700;font-size:0.85rem;color:#059669;margin-bottom:8px;">{{ __('landing.result_recommended') }}</div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                  <span style="background:white;border:1px solid #bbf7d0;padding:6px 12px;border-radius:8px;font-size:0.82rem;font-weight:600;">🇩🇪 {{ __('landing.country_de') }} — 78%</span>
                  <span style="background:white;border:1px solid #bbf7d0;padding:6px 12px;border-radius:8px;font-size:0.82rem;font-weight:600;">🇯🇵 {{ __('landing.country_jp') }} — 71%</span>
                  <span style="background:white;border:1px solid #bbf7d0;padding:6px 12px;border-radius:8px;font-size:0.82rem;font-weight:600;">🇰🇷 {{ __('landing.country_kr') }} — 82%</span>
                </div>
              </div>
              <div style="font-family:var(--font-display);font-weight:700;font-size:0.88rem;color:var(--text-primary);margin-bottom:12px;text-align:left;">{{ __('landing.result_save') }}</div>
              <div class="phone-input-wrap">
                <span class="phone-flag">🇺🇿</span>
                <input type="tel" class="phone-input" placeholder="+998 __ ___ __ __">
              </div>
              <button class="btn btn-primary" style="width:100%;margin-bottom:10px;" onclick="showCabinet()">{{ __('landing.result_login') }}</button>
              <button class="btn btn-secondary btn-sm" style="width:100%;" onclick="resetQ()">{{ __('landing.result_retry') }}</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- DESTINATIONS -->
<section class="destinations-section" id="destinations">
  <div class="container">
    <div class="section-header fade-up">
      <div class="tag">{{ __('landing.dest_tag') }}</div>
      <h2>{!! __('landing.dest_h2') !!}</h2>
      <p>{{ __('landing.dest_sub') }}</p>
    </div>
    <div class="dest-tabs fade-up">
      <button class="dest-tab active" onclick="filterDest('all', this)">{{ __('landing.dest_all') }}</button>
      <button class="dest-tab" onclick="filterDest('visa', this)">{{ __('landing.dest_visa') }}</button>
      <button class="dest-tab" onclick="filterDest('free', this)">{{ __('landing.dest_free') }}</button>
      <button class="dest-tab" onclick="filterDest('easy', this)">{{ __('landing.dest_easy') }}</button>
      <button class="dest-tab" onclick="filterDest('popular', this)">{{ __('landing.dest_popular') }}</button>
    </div>
    <div class="dest-grid" id="destGrid">
      <!-- Cards injected by JS -->
    </div>
    <div style="text-align:center; margin-top:48px;" class="fade-up">
      <a href="#scoring" class="btn btn-primary btn-lg">{{ __('landing.dest_best') }}</a>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-section section" id="how">
  <div class="container">
    <div class="section-header fade-up">
      <div class="tag">{{ __('landing.how_tag') }}</div>
      <h2>{{ __('landing.how_h2') }}</h2>
      <p>{{ __('landing.how_sub') }}</p>
    </div>
    <div class="steps-grid">
      <div class="step-item fade-up">
        <div class="step-num"><span class="step-icon">🎯</span></div>
        <div class="step-title">{{ __('landing.how_s1_title') }}</div>
        <div class="step-desc">{{ __('landing.how_s1_desc') }}</div>
      </div>
      <div class="step-item fade-up fade-up-delay-1">
        <div class="step-num"><span class="step-icon">✍️</span></div>
        <div class="step-title">{{ __('landing.how_s2_title') }}</div>
        <div class="step-desc">{{ __('landing.how_s2_desc') }}</div>
      </div>
      <div class="step-item fade-up fade-up-delay-2">
        <div class="step-num"><span class="step-icon">🤖</span></div>
        <div class="step-title">{{ __('landing.how_s3_title') }}</div>
        <div class="step-desc">{{ __('landing.how_s3_desc') }}</div>
      </div>
      <div class="step-item fade-up fade-up-delay-3">
        <div class="step-num"><span class="step-icon">📱</span></div>
        <div class="step-title">{{ __('landing.how_s4_title') }}</div>
        <div class="step-desc">{{ __('landing.how_s4_desc') }}</div>
      </div>
      <div class="step-item fade-up fade-up-delay-4">
        <div class="step-num"><span class="step-icon">🚀</span></div>
        <div class="step-title">{{ __('landing.how_s5_title') }}</div>
        <div class="step-desc">{{ __('landing.how_s5_desc') }}</div>
      </div>
    </div>
  </div>
</section>

<!-- TRUST / STATS / REVIEWS -->
<section class="trust-section" id="trust">
  <div class="container">
    <div class="section-header fade-up">
      <div class="tag cyan">{{ __('landing.trust_tag') }}</div>
      <h2>{{ __('landing.trust_h2') }}</h2>
      <p>{{ __('landing.trust_sub') }}</p>
    </div>
    <div class="stats-row fade-up">
      <div class="stat-card">
        <div class="stat-num">12<span>K+</span></div>
        <div class="stat-desc">{{ __('landing.trust_checks') }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-num">94<span>%</span></div>
        <div class="stat-desc">{{ __('landing.trust_accuracy') }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-num">40<span>+</span></div>
        <div class="stat-desc">{{ __('landing.trust_directions') }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-num">3<span>мин</span></div>
        <div class="stat-desc">{{ __('landing.trust_onboarding') }}</div>
      </div>
    </div>
    <div class="trust-pills fade-up">
      <div class="trust-pill"><span class="pill-icon">🛡️</span> {{ __('landing.trust_pill1') }}</div>
      <div class="trust-pill"><span class="pill-icon">🔍</span> {{ __('landing.trust_pill2') }}</div>
      <div class="trust-pill"><span class="pill-icon">🤖</span> {{ __('landing.trust_pill3') }}</div>
      <div class="trust-pill"><span class="pill-icon">📋</span> {{ __('landing.trust_pill4') }}</div>
      <div class="trust-pill"><span class="pill-icon">🇷🇺</span> {{ __('landing.trust_pill5') }}</div>
      <div class="trust-pill"><span class="pill-icon">🇺🇿</span> {{ __('landing.trust_pill6') }}</div>
      <div class="trust-pill"><span class="pill-icon">📱</span> {{ __('landing.trust_pill7') }}</div>
    </div>
    <div class="reviews-grid fade-up">
      <div class="review-card">
        <div class="review-stars">★★★★★</div>
        <p class="review-text">{{ __('landing.review1_text') }}</p>
        <div class="review-author">
          <div class="review-avatar">АК</div>
          <div>
            <div class="review-name">{{ __('landing.review1_name') }}</div>
            <div class="review-meta">{{ __('landing.review1_meta') }}</div>
          </div>
        </div>
      </div>
      <div class="review-card">
        <div class="review-stars">★★★★★</div>
        <p class="review-text">{{ __('landing.review2_text') }}</p>
        <div class="review-author">
          <div class="review-avatar">НУ</div>
          <div>
            <div class="review-name">{{ __('landing.review2_name') }}</div>
            <div class="review-meta">{{ __('landing.review2_meta') }}</div>
          </div>
        </div>
      </div>
      <div class="review-card">
        <div class="review-stars">★★★★☆</div>
        <p class="review-text">{{ __('landing.review3_text') }}</p>
        <div class="review-author">
          <div class="review-avatar">ДИ</div>
          <div>
            <div class="review-name">{{ __('landing.review3_name') }}</div>
            <div class="review-meta">{{ __('landing.review3_meta') }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- COMPARE -->
<section class="compare-section section" id="compare">
  <div class="container">
    <div class="section-header fade-up">
      <div class="tag">{{ __('landing.compare_tag') }}</div>
      <h2>{!! __('landing.compare_h2') !!}</h2>
    </div>
    <div class="compare-table fade-up">
      <div class="compare-header">
        <div class="compare-col">{{ __('landing.compare_features') }}</div>
        <div class="compare-col">{{ __('landing.compare_regular') }}</div>
        <div class="compare-col highlight">visabor.uz</div>
      </div>
      <div class="compare-row">
        <div class="compare-feature"><span class="feature-icon">🤖</span> {{ __('landing.compare_f1') }}</div>
        <div class="compare-no">✗</div>
        <div class="compare-yes-text">{{ __('landing.compare_f1_yes') }}</div>
      </div>
      <div class="compare-row">
        <div class="compare-feature"><span class="feature-icon">🎯</span> {{ __('landing.compare_f2') }}</div>
        <div class="compare-no">✗</div>
        <div class="compare-yes-text">{{ __('landing.compare_f2_yes') }}</div>
      </div>
      <div class="compare-row">
        <div class="compare-feature"><span class="feature-icon">⚡</span> {{ __('landing.compare_f3') }}</div>
        <div class="compare-no">✗</div>
        <div class="compare-yes-text">{{ __('landing.compare_f3_yes') }}</div>
      </div>
      <div class="compare-row">
        <div class="compare-feature"><span class="feature-icon">📋</span> {{ __('landing.compare_f4') }}</div>
        <div class="compare-no">✗</div>
        <div class="compare-yes-text">{{ __('landing.compare_f4_yes') }}</div>
      </div>
      <div class="compare-row">
        <div class="compare-feature"><span class="feature-icon">🔍</span> {{ __('landing.compare_f5') }}</div>
        <div class="compare-no">✗</div>
        <div class="compare-yes-text">{{ __('landing.compare_f5_yes') }}</div>
      </div>
      <div class="compare-row">
        <div class="compare-feature"><span class="feature-icon">📊</span> {{ __('landing.compare_f6') }}</div>
        <div class="compare-no">✗</div>
        <div class="compare-yes-text">{{ __('landing.compare_f6_yes') }}</div>
      </div>
      <div class="compare-row">
        <div class="compare-feature"><span class="feature-icon">📱</span> {{ __('landing.compare_f7') }}</div>
        <div class="compare-no">✗</div>
        <div class="compare-yes-text">{{ __('landing.compare_f7_yes') }}</div>
      </div>
      <div class="compare-row">
        <div class="compare-feature"><span class="feature-icon">🌐</span> {{ __('landing.compare_f8') }}</div>
        <div class="compare-no">✗</div>
        <div class="compare-yes-text">{{ __('landing.compare_f8_yes') }}</div>
      </div>
    </div>
  </div>
</section>

<!-- AGENCIES -->
<section class="agencies-section section" id="agencies">
  <div class="container">
    <div class="agencies-header fade-up">
      <div class="tag green">{{ __('landing.agencies_tag') }}</div>
      <h2>{{ __('landing.agencies_h2') }}</h2>
      <p>{{ __('landing.agencies_sub') }}</p>
    </div>
    <div class="agencies-grid fade-up" id="agenciesGrid">
      <!-- Injected by JS -->
    </div>
    <div class="agencies-bottom fade-up">
      <p>{{ __('landing.agencies_verified') }}</p>
      <a href="#scoring" class="btn btn-primary">{{ __('landing.agencies_cta') }}</a>
    </div>
  </div>
</section>

<!-- BLOCK 1: MOBILE APP -->
<section class="app-block section" id="app">
  <div class="container">
    <div class="app-block-grid">
      <div class="fade-up">
        <div class="app-block-text">
          <div class="tag">{{ __('landing.app_tag') }}</div>
          <h2>{{ __('landing.app_h2') }}</h2>
          <p>{{ __('landing.app_desc') }}</p>
          <div class="app-features">
            <div class="app-feat">
              <div class="app-feat-icon" style="background:linear-gradient(135deg,var(--blue-bright),#15803d);">📊</div>
              <div>
                <div class="app-feat-title">{{ __('landing.app_f1_title') }}</div>
                <div class="app-feat-desc">{{ __('landing.app_f1_desc') }}</div>
              </div>
            </div>
            <div class="app-feat">
              <div class="app-feat-icon" style="background:linear-gradient(135deg,var(--cyan),var(--blue-bright));">🤖</div>
              <div>
                <div class="app-feat-title">{{ __('landing.app_f2_title') }}</div>
                <div class="app-feat-desc">{{ __('landing.app_f2_desc') }}</div>
              </div>
            </div>
            <div class="app-feat">
              <div class="app-feat-icon" style="background:linear-gradient(135deg,var(--green),var(--teal));">📄</div>
              <div>
                <div class="app-feat-title">{{ __('landing.app_f3_title') }}</div>
                <div class="app-feat-desc">{{ __('landing.app_f3_desc') }}</div>
              </div>
            </div>
          </div>
          <div class="store-badges">
            <a href="#" class="store-badge">
              <div class="store-badge-icon">🍎</div>
              <div class="store-badge-text">
                <div class="store-badge-small">{{ __('landing.app_download_ios') }}</div>
                <div class="store-badge-name">App Store</div>
              </div>
            </a>
            <a href="#" class="store-badge">
              <div class="store-badge-icon">▶</div>
              <div class="store-badge-text">
                <div class="store-badge-small">{{ __('landing.app_download_android') }}</div>
                <div class="store-badge-name">Google Play</div>
              </div>
            </a>
          </div>
        </div>
      </div>
      <div class="fade-up fade-up-delay-2">
        <div class="phone-mockup-wrap">
          <div class="css-phone">
            <div class="css-phone-screen">
              <div class="css-phone-island"></div>
              <div class="css-phone-home"></div>
              <div class="app-screen">
                <div class="phone-header-bar">
                  <div class="phone-logo">visa<span>bor</span></div>
                  <div style="font-size:0.75rem;color:var(--text-muted);">Акмаль</div>
                </div>
                <div class="phone-notif">{{ __('landing.app_phone_notif') }}</div>
                <div style="font-family:var(--font-display);font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted);margin-bottom:12px;">{{ __('landing.app_phone_my_cases') }}</div>
                <div class="phone-card">
                  <div class="phone-card-top">
                    <div class="phone-card-label">{{ __('landing.country_de') }} 🇩🇪</div>
                    <div class="phone-card-status status-ready">{{ __('landing.app_phone_ready') }}</div>
                  </div>
                  <div class="phone-card-country">{{ __('landing.app_phone_schengen') }}</div>
                  <div class="phone-progress"><div class="phone-progress-fill" style="width:100%"></div></div>
                  <div style="font-size:0.68rem;color:var(--green);margin-top:4px;">{{ __('landing.app_phone_approved') }}</div>
                </div>
                <div class="phone-card">
                  <div class="phone-card-top">
                    <div class="phone-card-label">{{ __('landing.country_jp') }} 🇯🇵</div>
                    <div class="phone-card-status status-review">{{ __('landing.app_phone_review') }}</div>
                  </div>
                  <div class="phone-card-country">{{ __('landing.app_phone_tourist') }}</div>
                  <div class="phone-progress"><div class="phone-progress-fill" style="width:65%"></div></div>
                  <div style="font-size:0.68rem;color:var(--text-muted);margin-top:4px;">{{ __('landing.app_phone_waiting') }}</div>
                </div>
                <div style="margin-top:12px;background:linear-gradient(135deg,#059669,#15803d);border-radius:12px;padding:14px;color:white;">
                  <div style="font-family:var(--font-display);font-size:0.75rem;font-weight:700;margin-bottom:4px;">{{ __('landing.app_phone_ai') }}</div>
                  <div style="font-size:0.7rem;opacity:0.8;">{{ __('landing.app_phone_kr') }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- BLOCK 2: TELEGRAM BOT -->
<section class="tg-block section" id="telegram">
  <div class="container">
    <div class="tg-block-grid">
      <div class="fade-up fade-up-delay-2" style="order:1;">
        <div class="phone-mockup-wrap">
          <div class="css-phone">
            <div class="css-phone-screen">
              <div class="css-phone-island"></div>
              <div class="css-phone-home"></div>
              <div class="tg-screen">
                <div class="tg-header">
                  <div class="tg-avatar">V</div>
                  <div class="tg-header-info">
                    <div class="tg-header-name">VisaBor Bot</div>
                    <div class="tg-header-status">{{ __('landing.tg_online') }}</div>
                  </div>
                </div>
                <div class="tg-messages">
                  <div class="tg-msg bot">
                    <span class="tg-msg-icon">📋</span> {!! __('landing.tg_msg1') !!}
                    <div class="tg-msg-time">09:15</div>
                  </div>
                  <div class="tg-msg bot">
                    <span class="tg-msg-icon">📄</span> {{ __('landing.tg_msg2') }}
                    <div class="tg-msg-time">14:30</div>
                  </div>
                  <div class="tg-msg user">
                    {{ __('landing.tg_msg3') }}
                    <div class="tg-msg-time">15:02</div>
                  </div>
                  <div class="tg-msg bot">
                    <span class="tg-msg-icon">📊</span> {!! __('landing.tg_msg4') !!}
                    <div class="tg-msg-time">15:02</div>
                  </div>
                  <div class="tg-msg bot" style="background:linear-gradient(135deg,#d4f0dd,#c8ecdb);">
                    <span class="tg-msg-icon">✅</span> {!! __('landing.tg_msg5') !!}
                    <div class="tg-msg-time">10:45</div>
                  </div>
                </div>
                <div class="tg-input-bar">
                  <input type="text" placeholder="{{ __('landing.tg_input') }}" readonly>
                  <button class="tg-send-btn">➤</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="fade-up" style="order:2;">
        <div class="tg-block-text">
          <div class="tag cyan">{{ __('landing.tg_tag') }}</div>
          <h2>{{ __('landing.tg_h2') }}</h2>
          <p>{{ __('landing.tg_desc') }}</p>
          <div class="tg-features">
            <div class="tg-feat">
              <div class="tg-feat-icon" style="background:linear-gradient(135deg,#059669,#047857);">🔔</div>
              <div>
                <div class="tg-feat-title">{{ __('landing.tg_f1_title') }}</div>
                <div class="tg-feat-desc">{{ __('landing.tg_f1_desc') }}</div>
              </div>
            </div>
            <div class="tg-feat">
              <div class="tg-feat-icon" style="background:linear-gradient(135deg,#059669,#047857);">💬</div>
              <div>
                <div class="tg-feat-title">{{ __('landing.tg_f2_title') }}</div>
                <div class="tg-feat-desc">{{ __('landing.tg_f2_desc') }}</div>
              </div>
            </div>
            <div class="tg-feat">
              <div class="tg-feat-icon" style="background:linear-gradient(135deg,#059669,#047857);">✅</div>
              <div>
                <div class="tg-feat-title">{{ __('landing.tg_f3_title') }}</div>
                <div class="tg-feat-desc">{{ __('landing.tg_f3_desc') }}</div>
              </div>
            </div>
          </div>
          <a href="https://t.me/visaborbot" class="tg-btn">{{ __('landing.tg_cta') }}</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CABINET SECTION -->
<section class="cabinet-section section" id="cabinet">
  <div class="container">
    <div class="cabinet-grid">
      <div class="fade-up">
        <div class="tag cyan">{{ __('landing.cab_tag') }}</div>
        <h2 style="margin-top:16px;">{!! __('landing.cab_h2') !!}</h2>
        <p style="margin:16px 0 8px;">{{ __('landing.cab_sub') }}</p>
        <div class="cabinet-features">
          <div class="cab-feature">
            <div class="cab-feature-icon">📊</div>
            <div class="cab-feature-title">{{ __('landing.cab_f1_title') }}</div>
            <div class="cab-feature-desc">{{ __('landing.cab_f1_desc') }}</div>
          </div>
          <div class="cab-feature">
            <div class="cab-feature-icon">🗺️</div>
            <div class="cab-feature-title">{{ __('landing.cab_f2_title') }}</div>
            <div class="cab-feature-desc">{{ __('landing.cab_f2_desc') }}</div>
          </div>
          <div class="cab-feature">
            <div class="cab-feature-icon">📋</div>
            <div class="cab-feature-title">{{ __('landing.cab_f3_title') }}</div>
            <div class="cab-feature-desc">{{ __('landing.cab_f3_desc') }}</div>
          </div>
          <div class="cab-feature">
            <div class="cab-feature-icon">📁</div>
            <div class="cab-feature-title">{{ __('landing.cab_f4_title') }}</div>
            <div class="cab-feature-desc">{{ __('landing.cab_f4_desc') }}</div>
          </div>
          <div class="cab-feature">
            <div class="cab-feature-icon">💬</div>
            <div class="cab-feature-title">{{ __('landing.cab_f5_title') }}</div>
            <div class="cab-feature-desc">{{ __('landing.cab_f5_desc') }}</div>
          </div>
          <div class="cab-feature">
            <div class="cab-feature-icon">🔔</div>
            <div class="cab-feature-title">{{ __('landing.cab_f6_title') }}</div>
            <div class="cab-feature-desc">{{ __('landing.cab_f6_desc') }}</div>
          </div>
        </div>
        <a href="javascript:void(0)" onclick="openAuthModal()" class="btn btn-primary btn-lg" style="margin-top:32px;">{{ __('landing.cab_cta') }}</a>
      </div>
      <div class="fade-up fade-up-delay-2">
        <div class="dashboard-preview">
          <div class="dash-topbar">
            <div class="dot-row">
              <div class="dot-btn dot-red"></div>
              <div class="dot-btn dot-amber"></div>
              <div class="dot-btn dot-green"></div>
            </div>
            <div style="flex:1; text-align:center; font-family:var(--font-display); font-size:0.78rem; color:rgba(255,255,255,0.3);">{{ __('landing.cab_dash_title') }}</div>
          </div>
          <div class="dash-content">
            <div class="dash-welcome">{{ __('landing.cab_dash_welcome') }}</div>
            <div class="dash-score-row">
              <div class="dash-mini-card">
                <div class="dash-mini-label">{{ __('landing.cab_dash_score') }}</div>
                <div class="dash-mini-val val-cyan">75%</div>
              </div>
              <div class="dash-mini-card">
                <div class="dash-mini-label">{{ __('landing.cab_dash_directions') }}</div>
                <div class="dash-mini-val val-white">5</div>
              </div>
            </div>
            <div style="font-family:var(--font-display);font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:rgba(255,255,255,0.35);margin-bottom:10px;">{{ __('landing.cab_dash_top') }}</div>
            <div class="dash-dest-list">
              <div class="dash-dest-item">
                <div class="dash-dest-flag">🇩🇪</div>
                <div class="dash-dest-name">{{ __('landing.country_de') }}</div>
                <div class="dash-dest-pct val-cyan">78%</div>
              </div>
              <div class="dash-dest-item">
                <div class="dash-dest-flag">🇯🇵</div>
                <div class="dash-dest-name">{{ __('landing.country_jp') }}</div>
                <div class="dash-dest-pct" style="color:var(--green)">71%</div>
              </div>
              <div class="dash-dest-item">
                <div class="dash-dest-flag">🇰🇷</div>
                <div class="dash-dest-name">{{ __('landing.country_kr') }}</div>
                <div class="dash-dest-pct" style="color:var(--green)">82%</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="faq-section" id="faq">
  <div class="container">
    <div class="section-header fade-up">
      <div class="tag">{{ __('landing.faq_tag') }}</div>
      <h2>{{ __('landing.faq_h2') }}</h2>
    </div>
    <div class="faq-grid fade-up">
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">{{ __('landing.faq_q1') }} <span class="faq-arrow">▾</span></div>
        <div class="faq-a">{{ __('landing.faq_a1') }}</div>
      </div>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">{{ __('landing.faq_q2') }} <span class="faq-arrow">▾</span></div>
        <div class="faq-a">{{ __('landing.faq_a2') }}</div>
      </div>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">{{ __('landing.faq_q3') }} <span class="faq-arrow">▾</span></div>
        <div class="faq-a">{{ __('landing.faq_a3') }}</div>
      </div>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">{{ __('landing.faq_q4') }} <span class="faq-arrow">▾</span></div>
        <div class="faq-a">{{ __('landing.faq_a4') }}</div>
      </div>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">{{ __('landing.faq_q5') }} <span class="faq-arrow">▾</span></div>
        <div class="faq-a">{{ __('landing.faq_a5') }}</div>
      </div>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">{{ __('landing.faq_q6') }} <span class="faq-arrow">▾</span></div>
        <div class="faq-a">{{ __('landing.faq_a6') }}</div>
      </div>
    </div>
  </div>
</section>

<!-- CTA BANNER -->
<section class="cta-banner">
  <div class="container">
    <div class="cta-inner fade-up">
      <div class="tag" style="background:rgba(255,255,255,0.15);color:white;border-color:rgba(255,255,255,0.25);margin-bottom:24px;">{{ __('landing.cta_tag') }}</div>
      <h2>{!! __('landing.cta_h2') !!}</h2>
      <p>{{ __('landing.cta_sub') }}</p>
      <div class="cta-actions">
        <a href="#scoring" class="btn btn-white btn-lg">{{ __('landing.cta_btn') }}</a>
        <a href="#destinations" class="btn btn-outline-white btn-lg">{{ __('landing.cta_btn2') }}</a>
      </div>
    </div>
  </div>
</section>

<script>
// ===== TRANSLATIONS FOR JS =====
const T = @json(__('landing'));

// ===== DESTINATIONS DATA =====
const destinations = [
  { flag:'🇩🇪', name:T.country_de, tagline:T.tag_de, type:'visa', typeLabel:T.type_visa, score:78, time:'15-20 дней', cost:'160€', difficulty:T.diff_medium, why:T.why_de },
  { flag:'🇮🇹', name:T.country_it, tagline:T.tag_it, type:'visa', typeLabel:T.type_visa, score:74, time:'10-15 дней', cost:'160€', difficulty:T.diff_medium, why:T.why_it },
  { flag:'🇯🇵', name:T.country_jp, tagline:T.tag_jp, type:'visa', typeLabel:T.type_visa, score:71, time:'5-7 дней', cost:'~$50', difficulty:T.diff_low, why:T.why_jp },
  { flag:'🇰🇷', name:T.country_kr, tagline:T.tag_kr, type:'visa', typeLabel:T.type_visa, score:82, time:'3-5 дней', cost:'~$40', difficulty:T.diff_low, why:T.why_kr },
  { flag:'🇹🇷', name:T.country_tr, tagline:T.tag_tr, type:'free', typeLabel:T.type_free, score:99, time:'—', cost:'—', difficulty:T.diff_none, why:T.why_tr },
  { flag:'🇦🇪', name:T.country_ae, tagline:T.tag_ae, type:'easy', typeLabel:T.type_easy, score:96, time:'Онлайн', cost:'~$100', difficulty:T.diff_min, why:T.why_ae },
  { flag:'🇺🇸', name:T.country_us, tagline:T.tag_us, type:'visa', typeLabel:T.type_visa, score:45, time:'60-90 дней', cost:'$185', difficulty:T.diff_high, why:T.why_us },
  { flag:'🇬🇧', name:T.country_gb, tagline:T.tag_gb, type:'visa', typeLabel:T.type_visa, score:52, time:'15-21 дней', cost:'£115', difficulty:T.diff_high, why:T.why_gb },
  { flag:'🇫🇷', name:T.country_fr, tagline:T.tag_fr, type:'visa', typeLabel:T.type_visa, score:73, time:'15 дней', cost:'160€', difficulty:T.diff_medium, why:T.why_fr },
  { flag:'🇸🇦', name:T.country_sa, tagline:T.tag_sa, type:'easy', typeLabel:T.type_easy, score:88, time:'Online 1-3 дня', cost:'$80', difficulty:T.diff_low, why:T.why_sa },
  { flag:'🇨🇦', name:T.country_ca, tagline:T.tag_ca, type:'visa', typeLabel:T.type_visa, score:48, time:'45-90 дней', cost:'CAD$100', difficulty:T.diff_high, why:T.why_ca },
  { flag:'🇪🇸', name:T.country_es, tagline:T.tag_es, type:'visa', typeLabel:T.type_visa, score:76, time:'10-15 дней', cost:'160€', difficulty:T.diff_medium, why:T.why_es },
];

function renderDest(filter = 'all') {
  const grid = document.getElementById('destGrid');
  const filtered = filter === 'all' ? destinations : destinations.filter(d => d.type === filter);
  grid.innerHTML = filtered.map(d => {
    const fillClass = d.score >= 75 ? 'fill-green' : d.score >= 55 ? 'fill-amber' : 'fill-blue';
    const valClass = d.score >= 75 ? 'val-green' : d.score >= 55 ? 'val-amber' : 'val-blue';
    const typeClass = { visa:'type-visa', free:'type-free', easy:'type-easy', popular:'type-popular' }[d.type] || 'type-visa';
    return `
    <div class="dest-card">
      <div class="dest-card-top">
        <span class="dest-flag">${d.flag}</span>
        <span class="dest-type-badge ${typeClass}">${d.typeLabel}</span>
      </div>
      <div class="dest-card-body">
        <div class="dest-country">${d.name}</div>
        <div class="dest-tagline">${d.tagline}</div>
        <div class="dest-stats">
          <div class="dest-stat">
            <div class="dest-stat-label">${T.dest_time}</div>
            <div class="dest-stat-value">${d.time}</div>
          </div>
          <div class="dest-stat">
            <div class="dest-stat-label">${T.dest_cost}</div>
            <div class="dest-stat-value">${d.cost}</div>
          </div>
        </div>
        <div class="dest-score-bar"><div class="dest-score-fill ${fillClass}" style="width:${d.score}%"></div></div>
        <div class="dest-score-text">
          <span class="dest-score-label">${T.dest_chance}</span>
          <span class="dest-score-val ${valClass}">${d.score}%</span>
        </div>
        <div class="dest-actions">
          <a href="#scoring" class="btn btn-secondary btn-sm dest-btn">${T.dest_more}</a>
          <a href="#scoring" class="btn btn-primary btn-sm dest-btn">${T.dest_check}</a>
        </div>
      </div>
    </div>`;
  }).join('');
}
renderDest();

function filterDest(type, el) {
  document.querySelectorAll('.dest-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  renderDest(type);
}

// ===== QUESTIONNAIRE =====
let currentStep = 1;
const totalSteps = 6;
const answers = {};

function selectOption(el, key) {
  el.closest('.q-options').querySelectorAll('.q-option').forEach(o => o.classList.remove('selected'));
  el.classList.add('selected');
  answers[`step${currentStep}`] = key;
}

function qNav(dir) {
  const next = currentStep + dir;
  if (next < 1 || next > totalSteps + 1) return;
  if (dir > 0 && !answers[`step${currentStep}`]) {
    // Pick first option if none selected
    const firstOpt = document.querySelector(`#step${currentStep} .q-option`);
    if (firstOpt) { firstOpt.classList.add('selected'); answers[`step${currentStep}`] = 'default'; }
  }
  if (next > totalSteps) { showResult(); return; }
  document.getElementById(`step${currentStep}`).classList.remove('active');
  currentStep = next;
  document.getElementById(`step${currentStep}`).classList.add('active');
  document.getElementById('qProgress').textContent = T.q_step.replace(':current', currentStep).replace(':total', totalSteps);
  document.getElementById('qProgressFill').style.width = `${(currentStep / totalSteps) * 100}%`;
  document.getElementById('qBack').style.display = currentStep > 1 ? 'block' : 'none';
  const nextBtn = document.getElementById('qNext');
  nextBtn.textContent = currentStep === totalSteps ? T.q_get_result : T.q_next;
}

function showResult() {
  document.getElementById('questionnaire').style.display = 'none';
  const result = document.getElementById('scoreResult');
  result.classList.add('show');
  // Animate number
  let n = 0; const target = 75;
  const int = setInterval(() => {
    n = Math.min(n + 2, target);
    result.querySelector('.result-percent').textContent = n + '%';
    if (n >= target) clearInterval(int);
  }, 30);
}

function resetQ() {
  currentStep = 1;
  Object.keys(answers).forEach(k => delete answers[k]);
  document.querySelectorAll('.q-option').forEach(o => o.classList.remove('selected'));
  document.querySelectorAll('.q-step').forEach(s => s.classList.remove('active'));
  document.getElementById('step1').classList.add('active');
  document.getElementById('qProgress').textContent = T.q_step.replace(':current', 1).replace(':total', 6);
  document.getElementById('qProgressFill').style.width = '0%';
  document.getElementById('qBack').style.display = 'none';
  document.getElementById('qNext').textContent = T.q_next;
  document.getElementById('questionnaire').style.display = '';
  document.getElementById('scoreResult').classList.remove('show');
}

function showCabinet() { if (localStorage.getItem('public_token')) { window.location.href = '/me/scoring'; } else { openAuthModal(); } }

// ===== FAQ =====
function toggleFaq(el) {
  const item = el.closest('.faq-item');
  const wasOpen = item.classList.contains('open');
  document.querySelectorAll('.faq-item').forEach(f => f.classList.remove('open'));
  if (!wasOpen) item.classList.add('open');
}

// ===== AGENCIES =====
const agencies = [
  {
    name: 'Silk Road Visa',
    initials: 'SR',
    city: 'Ташкент',
    gradient: 'linear-gradient(135deg, #059669, #15803d)',
    rating: 4.9, reviews: 127,
    cases: '800+', experience: T.exp_years.replace(':n', 8),
    countries: ['🇩🇪','🇮🇹','🇫🇷','🇪🇸','🇬🇧','🇺🇸'],
  },
  {
    name: 'Euro Visa Pro',
    initials: 'EV',
    city: 'Самарканд',
    gradient: 'linear-gradient(135deg, #0d9488, #10b981)',
    rating: 4.8, reviews: 89,
    cases: '500+', experience: T.exp_years.replace(':n', 5),
    countries: ['🇩🇪','🇮🇹','🇫🇷','🇪🇸','🇨🇿','🇵🇱'],
  },
  {
    name: 'Asia Passport',
    initials: 'AP',
    city: 'Бухара',
    gradient: 'linear-gradient(135deg, #065f46, #047857)',
    rating: 4.7, reviews: 64,
    cases: '350+', experience: T.exp_years.replace(':n', 6),
    countries: ['🇯🇵','🇰🇷','🇨🇳','🇸🇬','🇲🇾','🇹🇭'],
  },
  {
    name: 'Visa Grand',
    initials: 'VG',
    city: 'Нукус',
    gradient: 'linear-gradient(135deg, #166534, #15803d)',
    rating: 4.6, reviews: 42,
    cases: '200+', experience: T.exp_years_4.replace(':n', 4),
    countries: ['🇺🇸','🇨🇦','🇬🇧','🇦🇺'],
  },
  {
    name: 'Travel Docs UZ',
    initials: 'TD',
    city: 'Фергана',
    gradient: 'linear-gradient(135deg, #0891b2, #34d399)',
    rating: 4.8, reviews: 73,
    cases: '400+', experience: T.exp_years.replace(':n', 7),
    countries: ['🇩🇪','🇫🇷','🇮🇹','🇯🇵','🇰🇷'],
  },
  {
    name: 'Global Visa Center',
    initials: 'GV',
    city: 'Ташкент',
    gradient: 'linear-gradient(135deg, #16a34a, #34d399)',
    rating: 4.9, reviews: 156,
    cases: '1200+', experience: T.exp_years.replace(':n', 10),
    countries: ['🇩🇪','🇮🇹','🇫🇷','🇪🇸','🇺🇸','🇬🇧','🇯🇵','🇰🇷'],
  },
];

function renderAgencies() {
  const grid = document.getElementById('agenciesGrid');
  if (!grid) return;
  grid.innerHTML = agencies.map(a => {
    const stars = '★'.repeat(Math.floor(a.rating)) + (a.rating % 1 >= 0.5 ? '½' : '');
    const flags = a.countries.map(f => `<div class="agency-country-flag">${f}</div>`).join('');
    return `
    <div class="agency-card">
      <div class="agency-card-header">
        <div class="agency-avatar" style="background:${a.gradient}">${a.initials}</div>
        <div>
          <div class="agency-name">${a.name}</div>
          <div class="agency-city">📍 ${a.city}</div>
        </div>
      </div>
      <div class="agency-stats">
        <div class="agency-stat"><span class="agency-stat-val">${a.cases}</span> ${T.agencies_cases}</div>
        <div class="agency-stat"><span class="agency-stat-val">${a.experience}</span> ${T.agencies_exp}</div>
      </div>
      <div class="agency-countries">${flags}</div>
      <div class="agency-rating">
        <div class="agency-rating-stars">${stars}</div>
        <div class="agency-rating-num">${a.rating}</div>
        <div class="agency-rating-count">(${a.reviews} ${T.agencies_reviews})</div>
      </div>
      <div class="agency-cta">
        <a href="#scoring" class="btn btn-secondary btn-sm" style="flex:1;font-size:0.82rem;padding:10px 16px;">${T.agencies_more}</a>
        <a href="#scoring" class="btn btn-primary btn-sm" style="flex:1;font-size:0.82rem;padding:10px 16px;">${T.agencies_apply}</a>
      </div>
    </div>`;
  }).join('');
}
renderAgencies();

// ===== SHIMMER: уникальная скорость и фаза для ::after каждой кнопки =====
(function() {
  const btns = document.querySelectorAll('.btn-primary, .tg-btn, .btn-white');
  const style = document.createElement('style');
  let css = '';
  btns.forEach((btn, i) => {
    const cls = 'shimmer-' + i;
    btn.classList.add(cls);
    const dur = 5 + ((i * 2.3) % 5);
    const delay = (i * 1.7) % 4;
    const glowDur = 5 + ((i * 1.9) % 4);
    const glowDelay = (i * 1.4) % 4;
    css += `.${cls}::after { animation: shimmer-drift ${dur.toFixed(1)}s ease-in-out -${delay.toFixed(1)}s infinite !important; }\n`;
    css += `.${cls} { animation: pulse-glow ${glowDur.toFixed(1)}s ease-in-out -${glowDelay.toFixed(1)}s infinite !important; }\n`;
  });
  style.textContent = css;
  document.head.appendChild(style);
})();

// ===== ANIMATED HERO RING =====
window.addEventListener('load', () => {
  let pct = 0;
  const el = document.getElementById('heroPercent');
  const ring = document.getElementById('heroRing');
  const target = 75;
  const circumference = 502;
  const int = setInterval(() => {
    pct = Math.min(pct + 1.5, target);
    if (el) el.textContent = Math.round(pct) + '%';
    if (ring) ring.style.strokeDashoffset = circumference - (pct / 100) * circumference;
    if (pct >= target) clearInterval(int);
  }, 25);
});
</script>
@endsection
