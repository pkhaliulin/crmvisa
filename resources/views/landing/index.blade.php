@extends('landing.layout')

@section('meta')
<title>visabor.uz — AI Visa Platform для граждан Узбекистана</title>
<meta name="description" content="visabor.uz — AI-платформа для граждан Узбекистана. Проверьте вероятность получения визы, сравните направления и получите персональные рекомендации до подачи заявки.">
<meta name="keywords" content="виза Узбекистан, AI visa, шансы на визу, visabor, визовый помощник, Шенген виза">
<meta property="og:title" content="visabor.uz — Умный способ выбрать страну и проверить шансы">
<meta property="og:description" content="AI-скоринг вероятности визы для граждан Узбекистана. Сравните направления, пройдите анкету и войдите в личный кабинет.">
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
  "description": "AI-платформа для граждан Узбекистана — проверка вероятности получения визы, сравнение направлений, умная анкета и личный кабинет",
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
      "name": "Насколько точен AI-скоринг?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "Точность нашей модели составляет около 94% на основе исторических данных по одобрениям и отказам. Скоринг анализирует более 12 факторов, включая визовую историю, занятость, финансовую привязку и цель поездки."
      }
    },
    {
      "@@type": "Question",
      "name": "Нужно ли платить за проверку шансов?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "Базовая проверка AI Visa Chance Score полностью бесплатна. Вы можете бесплатно пройти анкету, получить оценку и войти в личный кабинет без каких-либо платежей."
      }
    },
    {
      "@@type": "Question",
      "name": "Как сохранить результат?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "После прохождения анкеты введите ваш номер телефона — результат автоматически привяжется к нему. При следующем входе вы сразу попадёте в свой личный кабинет со всеми данными."
      }
    },
    {
      "@@type": "Question",
      "name": "Мои данные в безопасности?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "Да. Мы не продаём и не передаём ваши данные третьим лицам. Платформа соответствует стандартам защиты данных. Все соединения шифруются по протоколу HTTPS."
      }
    },
    {
      "@@type": "Question",
      "name": "Работаете ли вы с визовыми агентствами?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "Да. visabor.uz объединяет проверенные визовые агентства Узбекистана на одной платформе. После анализа профиля вы можете напрямую продолжить оформление с партнёрским агентством."
      }
    },
    {
      "@@type": "Question",
      "name": "На каких языках работает платформа?",
      "acceptedAnswer": {
        "@@type": "Answer",
        "text": "Платформа полностью поддерживает русский и узбекский языки. Поддержка также доступна на обоих языках через Telegram и WhatsApp."
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
          Создано для граждан Узбекистана
        </div>
        <h1>Узнайте шансы<br><em>до подачи</em> визы</h1>
        <p class="hero-sub">
          AI-платформа нового поколения — сравните направления, получите персональный скоринг и выберите страну, куда вам проще всего поехать.
        </p>
        <div class="hero-actions">
          <a href="#scoring" class="btn btn-primary btn-lg">
            ✦ Проверить шансы
          </a>
          <a href="#destinations" class="btn btn-secondary btn-lg">
            Сравнить направления
          </a>
        </div>
        <div class="hero-trust">
          <div class="trust-item">
            <span class="trust-icon">🛡️</span>
            <span class="trust-text"><strong>AI-скоринг</strong> по 12+ факторам</span>
          </div>
          <div class="trust-item">
            <span class="trust-icon">⚡</span>
            <span class="trust-text"><strong>За 3 минуты</strong> без бюрократии</span>
          </div>
          <div class="trust-item">
            <span class="trust-icon">🇺🇿</span>
            <span class="trust-text"><strong>Для граждан УЗ</strong> на русском</span>
          </div>
        </div>
      </div>
      <div class="hero-visual fade-up fade-up-delay-2">
        <div style="position:relative;">
          <div class="score-card">
            <div class="score-card-header">
              <span class="score-card-title">AI Visa Chance Score</span>
              <span class="score-live"><span class="live-dot"></span>Расчёт в реальном времени</span>
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
                  <span class="ring-label">Вероятность</span>
                </div>
              </div>
            </div>
            <div class="score-insights">
              <div class="insight-row">
                <span class="insight-icon">✅</span>
                <span class="insight-text">Официальная занятость</span>
                <span class="insight-badge badge-green">+сильно</span>
              </div>
              <div class="insight-row">
                <span class="insight-icon">⚠️</span>
                <span class="insight-text">Финансовая привязка</span>
                <span class="insight-badge badge-amber">умеренно</span>
              </div>
              <div class="insight-row">
                <span class="insight-icon">✅</span>
                <span class="insight-text">История поездок</span>
                <span class="insight-badge badge-green">+сильно</span>
              </div>
            </div>
            <a href="#scoring" class="btn btn-primary score-cta">Проверить свой профиль →</a>
          </div>
          <div class="float-card float-card-1">🇩🇪 Германия — 82% одобрений</div>
          <div class="float-card float-card-2">🇯🇵 Япония — быстрый путь</div>
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
        <div class="tag">✦ AI Visa Chance Score</div>
        <h2 style="margin-top:16px;">Ваш профиль —<br>ваш шанс</h2>
        <p>Ответьте на несколько коротких вопросов. AI-система проанализирует ваш профиль по 12+ факторам и покажет реальную вероятность получения визы по лучшим направлениям.</p>
        <div style="display:flex; flex-direction:column; gap:16px;">
          <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(22,163,74,0.08);display:flex;align-items:center;justify-content:center;font-size:1.2rem;">⚡</div>
            <div>
              <div style="font-family:var(--font-display);font-weight:700;font-size:0.9rem;">Занимает 3 минуты</div>
              <div style="font-size:0.82rem;color:var(--text-secondary);">Короткая умная анкета без лишних полей</div>
            </div>
          </div>
          <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(16,185,129,0.08);display:flex;align-items:center;justify-content:center;font-size:1.2rem;">🎯</div>
            <div>
              <div style="font-family:var(--font-display);font-weight:700;font-size:0.9rem;">Персональный результат</div>
              <div style="font-size:0.82rem;color:var(--text-secondary);">Подходящие именно вам направления</div>
            </div>
          </div>
          <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(6,182,212,0.08);display:flex;align-items:center;justify-content:center;font-size:1.2rem;">🔒</div>
            <div>
              <div style="font-family:var(--font-display);font-weight:700;font-size:0.9rem;">Безопасно и конфиденциально</div>
              <div style="font-size:0.82rem;color:var(--text-secondary);">Ваши данные защищены и сохраняются по номеру телефона</div>
            </div>
          </div>
        </div>
      </div>
      <div class="fade-up fade-up-delay-2">
        <div class="questionnaire" id="questionnaire">
          <div class="q-header">
            <span class="q-header-title" id="qTitle">AI Visa Chance Score</span>
            <span class="q-progress-text" id="qProgress">Шаг 1 из 6</span>
          </div>
          <div class="q-progress-bar"><div class="q-progress-fill" id="qProgressFill"></div></div>
          <div class="q-body">
            <!-- Step 1 -->
            <div class="q-step active" id="step1">
              <div class="q-question">Есть ли у вас опыт получения виз?</div>
              <div class="q-options">
                <div class="q-option" onclick="selectOption(this,'visa_yes')"><span class="opt-icon">✅</span> Да, есть</div>
                <div class="q-option" onclick="selectOption(this,'visa_sheng')"><span class="opt-icon">🇪🇺</span> Есть Шенген</div>
                <div class="q-option" onclick="selectOption(this,'visa_us')"><span class="opt-icon">🇺🇸</span> США / UK</div>
                <div class="q-option" onclick="selectOption(this,'visa_no')"><span class="opt-icon">🚫</span> Нет опыта</div>
              </div>
            </div>
            <!-- Step 2 -->
            <div class="q-step" id="step2">
              <div class="q-question">Какова ваша занятость?</div>
              <div class="q-options">
                <div class="q-option" onclick="selectOption(this,'emp_official')"><span class="opt-icon">🏢</span> Официально трудоустроен</div>
                <div class="q-option" onclick="selectOption(this,'emp_biz')"><span class="opt-icon">💼</span> Свой бизнес / ИП</div>
                <div class="q-option" onclick="selectOption(this,'emp_student')"><span class="opt-icon">🎓</span> Студент</div>
                <div class="q-option" onclick="selectOption(this,'emp_other')"><span class="opt-icon">📋</span> Другое</div>
              </div>
            </div>
            <!-- Step 3 -->
            <div class="q-step" id="step3">
              <div class="q-question">Есть ли у вас недвижимость или семья?</div>
              <div class="q-options">
                <div class="q-option" onclick="selectOption(this,'tie_both')"><span class="opt-icon">🏠</span> Недвижимость + семья</div>
                <div class="q-option" onclick="selectOption(this,'tie_family')"><span class="opt-icon">👨‍👩‍👧</span> Только семья</div>
                <div class="q-option" onclick="selectOption(this,'tie_prop')"><span class="opt-icon">🏗️</span> Только недвижимость</div>
                <div class="q-option" onclick="selectOption(this,'tie_none')"><span class="opt-icon">—</span> Нет</div>
              </div>
            </div>
            <!-- Step 4 -->
            <div class="q-step" id="step4">
              <div class="q-question">Каков ваш ежемесячный доход?</div>
              <div class="q-options">
                <div class="q-option" onclick="selectOption(this,'inc_high')"><span class="opt-icon">💰</span> Выше 5 000 USD</div>
                <div class="q-option" onclick="selectOption(this,'inc_mid')"><span class="opt-icon">💵</span> 1 500 – 5 000 USD</div>
                <div class="q-option" onclick="selectOption(this,'inc_low')"><span class="opt-icon">💴</span> До 1 500 USD</div>
                <div class="q-option" onclick="selectOption(this,'inc_var')"><span class="opt-icon">📊</span> Непостоянный</div>
              </div>
            </div>
            <!-- Step 5 -->
            <div class="q-step" id="step5">
              <div class="q-question">Цель поездки?</div>
              <div class="q-options">
                <div class="q-option" onclick="selectOption(this,'goal_tour')"><span class="opt-icon">✈️</span> Туризм</div>
                <div class="q-option" onclick="selectOption(this,'goal_biz')"><span class="opt-icon">🤝</span> Бизнес</div>
                <div class="q-option" onclick="selectOption(this,'goal_study')"><span class="opt-icon">📚</span> Учёба</div>
                <div class="q-option" onclick="selectOption(this,'goal_med')"><span class="opt-icon">🏥</span> Медицина</div>
              </div>
            </div>
            <!-- Step 6 -->
            <div class="q-step" id="step6">
              <div class="q-question">Куда хотели бы поехать?</div>
              <div class="q-options">
                <div class="q-option" onclick="selectOption(this,'dest_eu')"><span class="opt-icon">🇪🇺</span> Европа / Шенген</div>
                <div class="q-option" onclick="selectOption(this,'dest_asia')"><span class="opt-icon">🌏</span> Азия (Япония, Корея)</div>
                <div class="q-option" onclick="selectOption(this,'dest_us')"><span class="opt-icon">🌎</span> США / Канада / UK</div>
                <div class="q-option" onclick="selectOption(this,'dest_any')"><span class="opt-icon">🌍</span> Любое оптимальное</div>
              </div>
            </div>
          </div>
          <div class="q-footer" id="qFooter">
            <div class="q-nav">
              <button class="btn btn-sm q-btn-back" id="qBack" onclick="qNav(-1)" style="display:none">← Назад</button>
            </div>
            <button class="btn btn-sm btn-primary q-btn-next" id="qNext" onclick="qNav(1)">Далее →</button>
          </div>
        </div>
        <!-- RESULT -->
        <div class="score-result" id="scoreResult">
          <div class="questionnaire" style="border:none;box-shadow:none;">
            <div class="q-header" style="background: linear-gradient(135deg,#059669,#0d9488);">
              <span class="q-header-title">Ваш AI-результат готов</span>
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
                  <div class="result-label">шанс</div>
                </div>
              </div>
              <div class="result-title">Хороший профиль!</div>
              <div class="result-desc">Ваш профиль соответствует требованиям по нескольким направлениям. Укрепите финансовую привязку для повышения шанса.</div>
              <div class="result-tags">
                <span class="rtag rtag-green">✓ Официальная занятость</span>
                <span class="rtag rtag-green">✓ Визовая история</span>
                <span class="rtag rtag-amber">⚠ Доход: средний</span>
                <span class="rtag rtag-green">✓ Семья в УЗ</span>
              </div>
              <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:16px;margin-bottom:20px;text-align:left;">
                <div style="font-family:var(--font-display);font-weight:700;font-size:0.85rem;color:#059669;margin-bottom:8px;">🎯 Рекомендуемые направления:</div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                  <span style="background:white;border:1px solid #bbf7d0;padding:6px 12px;border-radius:8px;font-size:0.82rem;font-weight:600;">🇩🇪 Германия — 78%</span>
                  <span style="background:white;border:1px solid #bbf7d0;padding:6px 12px;border-radius:8px;font-size:0.82rem;font-weight:600;">🇯🇵 Япония — 71%</span>
                  <span style="background:white;border:1px solid #bbf7d0;padding:6px 12px;border-radius:8px;font-size:0.82rem;font-weight:600;">🇰🇷 Корея — 82%</span>
                </div>
              </div>
              <div style="font-family:var(--font-display);font-weight:700;font-size:0.88rem;color:var(--text-primary);margin-bottom:12px;text-align:left;">Сохраните результат по номеру телефона:</div>
              <div class="phone-input-wrap">
                <span class="phone-flag">🇺🇿</span>
                <input type="tel" class="phone-input" placeholder="+998 __ ___ __ __">
              </div>
              <button class="btn btn-primary" style="width:100%;margin-bottom:10px;" onclick="showCabinet()">Войти в личный кабинет →</button>
              <button class="btn btn-secondary btn-sm" style="width:100%;" onclick="resetQ()">Пройти заново</button>
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
      <div class="tag">🌍 Направления</div>
      <h2>Популярные направления<br>для граждан Узбекистана</h2>
      <p>Актуальные данные по визовым режимам, срокам и вероятности одобрения.</p>
    </div>
    <div class="dest-tabs fade-up">
      <button class="dest-tab active" onclick="filterDest('all', this)">Все направления</button>
      <button class="dest-tab" onclick="filterDest('visa', this)">Визовые</button>
      <button class="dest-tab" onclick="filterDest('free', this)">Безвизовые</button>
      <button class="dest-tab" onclick="filterDest('easy', this)">Упрощённые</button>
      <button class="dest-tab" onclick="filterDest('popular', this)">Популярные</button>
    </div>
    <div class="dest-grid" id="destGrid">
      <!-- Cards injected by JS -->
    </div>
    <div style="text-align:center; margin-top:48px;" class="fade-up">
      <a href="#scoring" class="btn btn-primary btn-lg">Подобрать лучший вариант для меня →</a>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-section section" id="how">
  <div class="container">
    <div class="section-header fade-up">
      <div class="tag">📋 Процесс</div>
      <h2>Как это работает</h2>
      <p>Весь путь — от первого вопроса до личного кабинета — занимает менее 5 минут.</p>
    </div>
    <div class="steps-grid">
      <div class="step-item fade-up">
        <div class="step-num"><span class="step-icon">🎯</span></div>
        <div class="step-title">Выберите направление</div>
        <div class="step-desc">Или укажите цель поездки — платформа предложит лучшие варианты</div>
      </div>
      <div class="step-item fade-up fade-up-delay-1">
        <div class="step-num"><span class="step-icon">✍️</span></div>
        <div class="step-title">Ответьте на вопросы</div>
        <div class="step-desc">Короткая умная анкета вместо длинных бюрократических форм</div>
      </div>
      <div class="step-item fade-up fade-up-delay-2">
        <div class="step-num"><span class="step-icon">🤖</span></div>
        <div class="step-title">Получите AI-оценку</div>
        <div class="step-desc">Динамический скоринг с объяснением сильных и слабых сторон профиля</div>
      </div>
      <div class="step-item fade-up fade-up-delay-3">
        <div class="step-num"><span class="step-icon">📱</span></div>
        <div class="step-title">Введите номер телефона</div>
        <div class="step-desc">Результат сохраняется автоматически — никаких паролей и регистраций</div>
      </div>
      <div class="step-item fade-up fade-up-delay-4">
        <div class="step-num"><span class="step-icon">🚀</span></div>
        <div class="step-title">Войдите в кабинет</div>
        <div class="step-desc">Продолжите оформление, следите за статусами и используйте все возможности платформы</div>
      </div>
    </div>
  </div>
</section>

<!-- TRUST / STATS / REVIEWS -->
<section class="trust-section" id="trust">
  <div class="container">
    <div class="section-header fade-up">
      <div class="tag cyan">⭐ Доверие</div>
      <h2>Нам доверяют</h2>
      <p>Тысячи граждан Узбекистана уже приняли более умные визовые решения с visabor.uz</p>
    </div>
    <div class="stats-row fade-up">
      <div class="stat-card">
        <div class="stat-num">12<span>K+</span></div>
        <div class="stat-desc">Проверок шансов</div>
      </div>
      <div class="stat-card">
        <div class="stat-num">94<span>%</span></div>
        <div class="stat-desc">Точность прогнозов AI</div>
      </div>
      <div class="stat-card">
        <div class="stat-num">40<span>+</span></div>
        <div class="stat-desc">Направлений в базе</div>
      </div>
      <div class="stat-card">
        <div class="stat-num">3<span>мин</span></div>
        <div class="stat-desc">Средний онбординг</div>
      </div>
    </div>
    <div class="trust-pills fade-up">
      <div class="trust-pill"><span class="pill-icon">🛡️</span> Проверенные агентства</div>
      <div class="trust-pill"><span class="pill-icon">🔍</span> Прозрачный процесс</div>
      <div class="trust-pill"><span class="pill-icon">🤖</span> AI-оценка до подачи</div>
      <div class="trust-pill"><span class="pill-icon">📋</span> Понятные статусы</div>
      <div class="trust-pill"><span class="pill-icon">🇷🇺</span> Поддержка на русском</div>
      <div class="trust-pill"><span class="pill-icon">🇺🇿</span> и узбекском</div>
      <div class="trust-pill"><span class="pill-icon">📱</span> Всё в одном месте</div>
    </div>
    <div class="reviews-grid fade-up">
      <div class="review-card">
        <div class="review-stars">★★★★★</div>
        <p class="review-text">"Наконец-то сервис, который объясняет шансы честно. Я проверил профиль перед подачей в Германию — AI точно указал на слабое место (финансы), я подготовился лучше и получил визу."</p>
        <div class="review-author">
          <div class="review-avatar">АК</div>
          <div>
            <div class="review-name">Азиз Каримов</div>
            <div class="review-meta">Ташкент • Шенген виза • Германия</div>
          </div>
        </div>
      </div>
      <div class="review-card">
        <div class="review-stars">★★★★★</div>
        <p class="review-text">"Сравнила несколько стран — visabor показал, что у меня выше всего шанс в Японии, а не в Европе, как я думала. Оказалось правдой! Японская виза с первого раза."</p>
        <div class="review-author">
          <div class="review-avatar">НУ</div>
          <div>
            <div class="review-name">Нилуфар Усманова</div>
            <div class="review-meta">Самарканд • Туристическая • Япония</div>
          </div>
        </div>
      </div>
      <div class="review-card">
        <div class="review-stars">★★★★☆</div>
        <p class="review-text">"Очень удобно, что результат сохраняется по номеру телефона. Вошёл, увидел рекомендации — всё на месте. Личный кабинет простой и понятный, видно каждый шаг."</p>
        <div class="review-author">
          <div class="review-avatar">ДИ</div>
          <div>
            <div class="review-name">Дильшод Исмоилов</div>
            <div class="review-meta">Фергана • Бизнес-виза • Южная Корея</div>
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
      <div class="tag">⚖️ Сравнение</div>
      <h2>Почему visabor.uz<br>лучше обычного визового сайта</h2>
    </div>
    <div class="compare-table fade-up">
      <div class="compare-header">
        <div class="compare-col">Возможности</div>
        <div class="compare-col">Обычный сайт</div>
        <div class="compare-col highlight">visabor.uz</div>
      </div>
      <div class="compare-row">
        <div class="compare-feature"><span class="feature-icon">🤖</span> AI-скоринг вероятности визы</div>
        <div class="compare-no">✗</div>
        <div class="compare-yes-text">✓ Динамический</div>
      </div>
      <div class="compare-row">
        <div class="compare-feature"><span class="feature-icon">🎯</span> Персональный подбор направлений</div>
        <div class="compare-no">✗</div>
        <div class="compare-yes-text">✓ По профилю</div>
      </div>
      <div class="compare-row">
        <div class="compare-feature"><span class="feature-icon">⚡</span> Умная анкета без бюрократии</div>
        <div class="compare-no">✗</div>
        <div class="compare-yes-text">✓ 3 минуты</div>
      </div>
      <div class="compare-row">
        <div class="compare-feature"><span class="feature-icon">📋</span> Единый личный кабинет</div>
        <div class="compare-no">✗</div>
        <div class="compare-yes-text">✓ Полный цикл</div>
      </div>
      <div class="compare-row">
        <div class="compare-feature"><span class="feature-icon">🔍</span> Объяснение слабых сторон профиля</div>
        <div class="compare-no">✗</div>
        <div class="compare-yes-text">✓ AI-инсайты</div>
      </div>
      <div class="compare-row">
        <div class="compare-feature"><span class="feature-icon">📊</span> Сравнение направлений</div>
        <div class="compare-no">✗</div>
        <div class="compare-yes-text">✓ Интерактивно</div>
      </div>
      <div class="compare-row">
        <div class="compare-feature"><span class="feature-icon">📱</span> Сохранение по номеру телефона</div>
        <div class="compare-no">✗</div>
        <div class="compare-yes-text">✓ Без паролей</div>
      </div>
      <div class="compare-row">
        <div class="compare-feature"><span class="feature-icon">🌐</span> Поддержка русский / узбекский</div>
        <div class="compare-no">✗</div>
        <div class="compare-yes-text">✓ Оба языка</div>
      </div>
    </div>
  </div>
</section>

<!-- AGENCIES -->
<section class="agencies-section section" id="agencies">
  <div class="container">
    <div class="agencies-header fade-up">
      <div class="tag green">🏢 Проверенные партнёры</div>
      <h2>Агентства Узбекистана на одной платформе</h2>
      <p>visabor.uz объединяет лучшие визовые агентства страны. Выберите проверенного партнёра для оформления визы.</p>
    </div>
    <div class="agencies-grid fade-up" id="agenciesGrid">
      <!-- Injected by JS -->
    </div>
    <div class="agencies-bottom fade-up">
      <p>Все агентства проходят проверку перед подключением к платформе</p>
      <a href="#scoring" class="btn btn-primary">Проверить шансы и найти агентство →</a>
    </div>
  </div>
</section>

<!-- BLOCK 1: MOBILE APP -->
<section class="app-block section" id="app">
  <div class="container">
    <div class="app-block-grid">
      <div class="fade-up">
        <div class="app-block-text">
          <div class="tag">📱 Мобильное приложение</div>
          <h2>Все заявки и статусы — в кармане</h2>
          <p>Приложение VisaBor даёт полный контроль над визовым процессом. Статусы заявок, AI-рекомендации, загрузка документов — всё доступно с телефона.</p>
          <div class="app-features">
            <div class="app-feat">
              <div class="app-feat-icon" style="background:linear-gradient(135deg,var(--blue-bright),#15803d);">📊</div>
              <div>
                <div class="app-feat-title">Статусы в реальном времени</div>
                <div class="app-feat-desc">Видите каждый шаг — от подачи документов до получения визы</div>
              </div>
            </div>
            <div class="app-feat">
              <div class="app-feat-icon" style="background:linear-gradient(135deg,var(--cyan),var(--blue-bright));">🤖</div>
              <div>
                <div class="app-feat-title">AI-рекомендации</div>
                <div class="app-feat-desc">Персональные предложения стран с наибольшим шансом одобрения</div>
              </div>
            </div>
            <div class="app-feat">
              <div class="app-feat-icon" style="background:linear-gradient(135deg,var(--green),var(--teal));">📄</div>
              <div>
                <div class="app-feat-title">Документы в 2 касания</div>
                <div class="app-feat-desc">Сфотографируйте и загрузите прямо из приложения</div>
              </div>
            </div>
          </div>
          <div class="store-badges">
            <a href="#" class="store-badge">
              <div class="store-badge-icon">🍎</div>
              <div class="store-badge-text">
                <div class="store-badge-small">Загрузите в</div>
                <div class="store-badge-name">App Store</div>
              </div>
            </a>
            <a href="#" class="store-badge">
              <div class="store-badge-icon">▶</div>
              <div class="store-badge-text">
                <div class="store-badge-small">Доступно в</div>
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
                <div class="phone-notif">✅ Виза в Германию одобрена!</div>
                <div style="font-family:var(--font-display);font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted);margin-bottom:12px;">МОИ ЗАЯВКИ</div>
                <div class="phone-card">
                  <div class="phone-card-top">
                    <div class="phone-card-label">Германия 🇩🇪</div>
                    <div class="phone-card-status status-ready">Готова</div>
                  </div>
                  <div class="phone-card-country">Шенгенская виза</div>
                  <div class="phone-progress"><div class="phone-progress-fill" style="width:100%"></div></div>
                  <div style="font-size:0.68rem;color:var(--green);margin-top:4px;">Виза одобрена!</div>
                </div>
                <div class="phone-card">
                  <div class="phone-card-top">
                    <div class="phone-card-label">Япония 🇯🇵</div>
                    <div class="phone-card-status status-review">На проверке</div>
                  </div>
                  <div class="phone-card-country">Туристическая виза</div>
                  <div class="phone-progress"><div class="phone-progress-fill" style="width:65%"></div></div>
                  <div style="font-size:0.68rem;color:var(--text-muted);margin-top:4px;">65% — ожидание решения</div>
                </div>
                <div style="margin-top:12px;background:linear-gradient(135deg,#059669,#15803d);border-radius:12px;padding:14px;color:white;">
                  <div style="font-family:var(--font-display);font-size:0.75rem;font-weight:700;margin-bottom:4px;">AI-рекомендация</div>
                  <div style="font-size:0.7rem;opacity:0.8;">Южная Корея — шанс 82%</div>
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
                    <div class="tg-header-status">онлайн</div>
                  </div>
                </div>
                <div class="tg-messages">
                  <div class="tg-msg bot">
                    <span class="tg-msg-icon">📋</span> Заявка #1247 на визу в <strong>Германию</strong> принята в обработку.
                    <div class="tg-msg-time">09:15</div>
                  </div>
                  <div class="tg-msg bot">
                    <span class="tg-msg-icon">📄</span> Документы по заявке прошли проверку. Все в порядке!
                    <div class="tg-msg-time">14:30</div>
                  </div>
                  <div class="tg-msg user">
                    Какой статус моей заявки?
                    <div class="tg-msg-time">15:02</div>
                  </div>
                  <div class="tg-msg bot">
                    <span class="tg-msg-icon">📊</span> Заявка #1247 — <strong>Подана в посольство</strong>. Ожидаемый срок: 5-7 рабочих дней.
                    <div class="tg-msg-time">15:02</div>
                  </div>
                  <div class="tg-msg bot" style="background:linear-gradient(135deg,#d4f0dd,#c8ecdb);">
                    <span class="tg-msg-icon">✅</span> <strong>Поздравляем!</strong> Виза в Германию одобрена! Заберите паспорт в офисе агентства.
                    <div class="tg-msg-time">10:45</div>
                  </div>
                </div>
                <div class="tg-input-bar">
                  <input type="text" placeholder="Написать..." readonly>
                  <button class="tg-send-btn">➤</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="fade-up" style="order:2;">
        <div class="tg-block-text">
          <div class="tag cyan">💬 Telegram-бот</div>
          <h2>Уведомления прямо в Telegram</h2>
          <p>Не нужно проверять почту или заходить на сайт. Наш бот отправит обновление в секунду, когда что-то изменится по вашей заявке.</p>
          <div class="tg-features">
            <div class="tg-feat">
              <div class="tg-feat-icon" style="background:linear-gradient(135deg,#059669,#047857);">🔔</div>
              <div>
                <div class="tg-feat-title">Мгновенные уведомления</div>
                <div class="tg-feat-desc">Приём документов, проверка, подача в посольство, одобрение — всё в чате</div>
              </div>
            </div>
            <div class="tg-feat">
              <div class="tg-feat-icon" style="background:linear-gradient(135deg,#059669,#047857);">💬</div>
              <div>
                <div class="tg-feat-title">Диалог с ботом</div>
                <div class="tg-feat-desc">Спросите статус заявки — бот ответит мгновенно, без ожидания менеджера</div>
              </div>
            </div>
            <div class="tg-feat">
              <div class="tg-feat-icon" style="background:linear-gradient(135deg,#059669,#047857);">✅</div>
              <div>
                <div class="tg-feat-title">Виза одобрена? Узнаете первым</div>
                <div class="tg-feat-desc">Уведомление с инструкциями приходит сразу после решения посольства</div>
              </div>
            </div>
          </div>
          <a href="https://t.me/visaborbot" class="tg-btn">💬 Открыть Telegram-бот</a>
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
        <div class="tag cyan">🗂️ Личный кабинет</div>
        <h2 style="margin-top:16px;">Ваш цифровой<br>помощник на пути к визе</h2>
        <p style="margin:16px 0 8px;">Личный кабинет — это не просто страница. Это центр прозрачности, куда хочется возвращаться.</p>
        <div class="cabinet-features">
          <div class="cab-feature">
            <div class="cab-feature-icon">📊</div>
            <div class="cab-feature-title">Ваши шансы</div>
            <div class="cab-feature-desc">Актуальный AI-скоринг и история оценок</div>
          </div>
          <div class="cab-feature">
            <div class="cab-feature-icon">🗺️</div>
            <div class="cab-feature-title">Направления</div>
            <div class="cab-feature-desc">Персонально подобранные страны и сравнение</div>
          </div>
          <div class="cab-feature">
            <div class="cab-feature-icon">📋</div>
            <div class="cab-feature-title">Статусы заявок</div>
            <div class="cab-feature-desc">Прозрачный прогресс по каждому шагу</div>
          </div>
          <div class="cab-feature">
            <div class="cab-feature-icon">📁</div>
            <div class="cab-feature-title">Документы</div>
            <div class="cab-feature-desc">Загрузка и управление файлами заявки</div>
          </div>
          <div class="cab-feature">
            <div class="cab-feature-icon">💬</div>
            <div class="cab-feature-title">Связь с агентом</div>
            <div class="cab-feature-desc">Прямой контакт с проверенным агентством</div>
          </div>
          <div class="cab-feature">
            <div class="cab-feature-icon">🔔</div>
            <div class="cab-feature-title">Уведомления</div>
            <div class="cab-feature-desc">Важные напоминания и обновления статусов</div>
          </div>
        </div>
        <a href="/me/cases" class="btn btn-primary btn-lg" style="margin-top:32px;">Войти в личный кабинет →</a>
      </div>
      <div class="fade-up fade-up-delay-2">
        <div class="dashboard-preview">
          <div class="dash-topbar">
            <div class="dot-row">
              <div class="dot-btn dot-red"></div>
              <div class="dot-btn dot-amber"></div>
              <div class="dot-btn dot-green"></div>
            </div>
            <div style="flex:1; text-align:center; font-family:var(--font-display); font-size:0.78rem; color:rgba(255,255,255,0.3);">visabor.uz — Личный кабинет</div>
          </div>
          <div class="dash-content">
            <div class="dash-welcome">👋 Добро пожаловать, Акмаль</div>
            <div class="dash-score-row">
              <div class="dash-mini-card">
                <div class="dash-mini-label">AI Шанс</div>
                <div class="dash-mini-val val-cyan">75%</div>
              </div>
              <div class="dash-mini-card">
                <div class="dash-mini-label">Направлений</div>
                <div class="dash-mini-val val-white">5</div>
              </div>
            </div>
            <div style="font-family:var(--font-display);font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:rgba(255,255,255,0.35);margin-bottom:10px;">Топ направлений для вас</div>
            <div class="dash-dest-list">
              <div class="dash-dest-item">
                <div class="dash-dest-flag">🇩🇪</div>
                <div class="dash-dest-name">Германия</div>
                <div class="dash-dest-pct val-cyan">78%</div>
              </div>
              <div class="dash-dest-item">
                <div class="dash-dest-flag">🇯🇵</div>
                <div class="dash-dest-name">Япония</div>
                <div class="dash-dest-pct" style="color:var(--green)">71%</div>
              </div>
              <div class="dash-dest-item">
                <div class="dash-dest-flag">🇰🇷</div>
                <div class="dash-dest-name">Южная Корея</div>
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
      <div class="tag">❓ Вопросы</div>
      <h2>Частые вопросы</h2>
    </div>
    <div class="faq-grid fade-up">
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">Насколько точен AI-скоринг? <span class="faq-arrow">▾</span></div>
        <div class="faq-a">Точность нашей модели составляет около 94% на основе исторических данных по одобрениям и отказам. Скоринг анализирует более 12 факторов, включая визовую историю, занятость, финансовую привязку и цель поездки.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">Нужно ли платить за проверку шансов? <span class="faq-arrow">▾</span></div>
        <div class="faq-a">Базовая проверка AI Visa Chance Score полностью бесплатна. Вы можете бесплатно пройти анкету, получить оценку и войти в личный кабинет без каких-либо платежей.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">Как сохранить результат? <span class="faq-arrow">▾</span></div>
        <div class="faq-a">После прохождения анкеты введите ваш номер телефона — результат автоматически привяжется к нему. При следующем входе вы сразу попадёте в свой личный кабинет со всеми данными.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">Мои данные в безопасности? <span class="faq-arrow">▾</span></div>
        <div class="faq-a">Да. Мы не продаём и не передаём ваши данные третьим лицам. Платформа соответствует стандартам защиты данных. Все соединения шифруются по протоколу HTTPS.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">Работаете ли вы с визовыми агентствами? <span class="faq-arrow">▾</span></div>
        <div class="faq-a">Да. visabor.uz объединяет проверенные визовые агентства Узбекистана на одной платформе. После анализа профиля вы можете напрямую продолжить оформление с партнёрским агентством.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">На каких языках работает платформа? <span class="faq-arrow">▾</span></div>
        <div class="faq-a">Платформа полностью поддерживает русский и узбекский языки. Поддержка также доступна на обоих языках через Telegram и WhatsApp.</div>
      </div>
    </div>
  </div>
</section>

<!-- CTA BANNER -->
<section class="cta-banner">
  <div class="container">
    <div class="cta-inner fade-up">
      <div class="tag" style="background:rgba(255,255,255,0.15);color:white;border-color:rgba(255,255,255,0.25);margin-bottom:24px;">✦ Начните прямо сейчас</div>
      <h2>Узнайте свои шансы<br>за 3 минуты</h2>
      <p>Тысячи граждан Узбекистана уже приняли более умные решения. Ваша очередь.</p>
      <div class="cta-actions">
        <a href="#scoring" class="btn btn-white btn-lg">Проверить шансы →</a>
        <a href="#destinations" class="btn btn-outline-white btn-lg">Сравнить направления</a>
      </div>
    </div>
  </div>
</section>

<script>
// ===== DESTINATIONS DATA =====
const destinations = [
  { flag:'🇩🇪', name:'Германия', tagline:'Шенген — стабильный и надёжный', type:'visa', typeLabel:'Визовое', score:78, time:'15-20 дней', cost:'160€', difficulty:'Средняя', why:'Стабильная шенгенская зона, чёткие требования' },
  { flag:'🇮🇹', name:'Италия', tagline:'Шенген с итальянским акцентом', type:'visa', typeLabel:'Визовое', score:74, time:'10-15 дней', cost:'160€', difficulty:'Средняя', why:'Популярный туристический Шенген' },
  { flag:'🇯🇵', name:'Япония', tagline:'Высокий одобрительный уровень для УЗ', type:'visa', typeLabel:'Визовое', score:71, time:'5-7 дней', cost:'~$50', difficulty:'Низкая', why:'Лояльная политика к гражданам Узбекистана' },
  { flag:'🇰🇷', name:'Южная Корея', tagline:'Быстро, доступно, высокий шанс', type:'visa', typeLabel:'Визовое', score:82, time:'3-5 дней', cost:'~$40', difficulty:'Низкая', why:'Один из лучших шансов для граждан УЗ' },
  { flag:'🇹🇷', name:'Турция', tagline:'Безвизовый доступ — популярное направление', type:'free', typeLabel:'Безвизовое', score:99, time:'—', cost:'—', difficulty:'Нет', why:'Безвизовый режим для граждан Узбекистана' },
  { flag:'🇦🇪', name:'ОАЭ', tagline:'Виза по прилёту — удобный формат', type:'easy', typeLabel:'Упрощённое', score:96, time:'Онлайн', cost:'~$100', difficulty:'Минимальная', why:'Visa on arrival / e-visa без консульства' },
  { flag:'🇺🇸', name:'США', tagline:'Высокие требования — готовьтесь тщательно', type:'visa', typeLabel:'Визовое', score:45, time:'60-90 дней', cost:'$185', difficulty:'Высокая', why:'Требует сильной финансовой привязки' },
  { flag:'🇬🇧', name:'Великобритания', tagline:'Стандарты после Brexit', type:'visa', typeLabel:'Визовое', score:52, time:'15-21 дней', cost:'£115', difficulty:'Высокая', why:'Строгие критерии финансовой состоятельности' },
  { flag:'🇫🇷', name:'Франция', tagline:'Шенген — Париж ждёт', type:'visa', typeLabel:'Визовое', score:73, time:'15 дней', cost:'160€', difficulty:'Средняя', why:'Шенгенская виза через французское посольство' },
  { flag:'🇸🇦', name:'Саудовская Аравия', tagline:'e-visa для туристов — быстро', type:'easy', typeLabel:'Упрощённое', score:88, time:'Online 1-3 дня', cost:'$80', difficulty:'Низкая', why:'Электронная туристическая виза без консульства' },
  { flag:'🇨🇦', name:'Канада', tagline:'Требовательная, но возможная', type:'visa', typeLabel:'Визовое', score:48, time:'45-90 дней', cost:'CAD$100', difficulty:'Высокая', why:'Высокие финансовые и документальные требования' },
  { flag:'🇪🇸', name:'Испания', tagline:'Шенген — отличный туристический выбор', type:'visa', typeLabel:'Визовое', score:76, time:'10-15 дней', cost:'160€', difficulty:'Средняя', why:'Один из самых быстрых Шенгенов' },
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
            <div class="dest-stat-label">Срок</div>
            <div class="dest-stat-value">${d.time}</div>
          </div>
          <div class="dest-stat">
            <div class="dest-stat-label">Стоимость</div>
            <div class="dest-stat-value">${d.cost}</div>
          </div>
        </div>
        <div class="dest-score-bar"><div class="dest-score-fill ${fillClass}" style="width:${d.score}%"></div></div>
        <div class="dest-score-text">
          <span class="dest-score-label">AI-шанс одобрения</span>
          <span class="dest-score-val ${valClass}">${d.score}%</span>
        </div>
        <div class="dest-actions">
          <a href="#scoring" class="btn btn-secondary btn-sm dest-btn">Подробнее</a>
          <a href="#scoring" class="btn btn-primary btn-sm dest-btn">Проверить шанс</a>
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
  document.getElementById('qProgress').textContent = `Шаг ${currentStep} из ${totalSteps}`;
  document.getElementById('qProgressFill').style.width = `${(currentStep / totalSteps) * 100}%`;
  document.getElementById('qBack').style.display = currentStep > 1 ? 'block' : 'none';
  const nextBtn = document.getElementById('qNext');
  nextBtn.textContent = currentStep === totalSteps ? 'Получить результат →' : 'Далее →';
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
  document.getElementById('qProgress').textContent = 'Шаг 1 из 6';
  document.getElementById('qProgressFill').style.width = '0%';
  document.getElementById('qBack').style.display = 'none';
  document.getElementById('qNext').textContent = 'Далее →';
  document.getElementById('questionnaire').style.display = '';
  document.getElementById('scoreResult').classList.remove('show');
}

function showCabinet() { window.location.href = '/me/scoring'; }

// ===== FAQ =====
function toggleFaq(el) {
  const item = el.closest('.faq-item');
  const wasOpen = item.classList.contains('open');
  document.querySelectorAll('.faq-item').forEach(f => f.classList.remove('open'));
  if (!wasOpen) item.classList.add('open');
}

// ===== SCROLL ANIMATIONS =====
const io = new IntersectionObserver((entries) => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.12 });
document.querySelectorAll('.fade-up').forEach(el => io.observe(el));

// ===== NAVBAR SCROLL =====
window.addEventListener('scroll', () => {
  const navbar = document.getElementById('navbar');
  if (navbar) navbar.style.boxShadow = window.scrollY > 50 ? '0 4px 20px rgba(0,0,0,0.08)' : '';
});

// ===== AGENCIES =====
const agencies = [
  {
    name: 'Silk Road Visa',
    initials: 'SR',
    city: 'Ташкент',
    gradient: 'linear-gradient(135deg, #059669, #15803d)',
    rating: 4.9, reviews: 127,
    cases: '800+', experience: '8 лет',
    countries: ['🇩🇪','🇮🇹','🇫🇷','🇪🇸','🇬🇧','🇺🇸'],
  },
  {
    name: 'Euro Visa Pro',
    initials: 'EV',
    city: 'Самарканд',
    gradient: 'linear-gradient(135deg, #0d9488, #10b981)',
    rating: 4.8, reviews: 89,
    cases: '500+', experience: '5 лет',
    countries: ['🇩🇪','🇮🇹','🇫🇷','🇪🇸','🇨🇿','🇵🇱'],
  },
  {
    name: 'Asia Passport',
    initials: 'AP',
    city: 'Бухара',
    gradient: 'linear-gradient(135deg, #065f46, #047857)',
    rating: 4.7, reviews: 64,
    cases: '350+', experience: '6 лет',
    countries: ['🇯🇵','🇰🇷','🇨🇳','🇸🇬','🇲🇾','🇹🇭'],
  },
  {
    name: 'Visa Grand',
    initials: 'VG',
    city: 'Нукус',
    gradient: 'linear-gradient(135deg, #166534, #15803d)',
    rating: 4.6, reviews: 42,
    cases: '200+', experience: '4 года',
    countries: ['🇺🇸','🇨🇦','🇬🇧','🇦🇺'],
  },
  {
    name: 'Travel Docs UZ',
    initials: 'TD',
    city: 'Фергана',
    gradient: 'linear-gradient(135deg, #0891b2, #34d399)',
    rating: 4.8, reviews: 73,
    cases: '400+', experience: '7 лет',
    countries: ['🇩🇪','🇫🇷','🇮🇹','🇯🇵','🇰🇷'],
  },
  {
    name: 'Global Visa Center',
    initials: 'GV',
    city: 'Ташкент',
    gradient: 'linear-gradient(135deg, #16a34a, #34d399)',
    rating: 4.9, reviews: 156,
    cases: '1200+', experience: '10 лет',
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
        <div class="agency-stat"><span class="agency-stat-val">${a.cases}</span> заявок</div>
        <div class="agency-stat"><span class="agency-stat-val">${a.experience}</span> опыта</div>
      </div>
      <div class="agency-countries">${flags}</div>
      <div class="agency-rating">
        <div class="agency-rating-stars">${stars}</div>
        <div class="agency-rating-num">${a.rating}</div>
        <div class="agency-rating-count">(${a.reviews} отзывов)</div>
      </div>
      <div class="agency-cta">
        <a href="#scoring" class="btn btn-secondary btn-sm" style="flex:1;font-size:0.82rem;padding:10px 16px;">Подробнее</a>
        <a href="#scoring" class="btn btn-primary btn-sm" style="flex:1;font-size:0.82rem;padding:10px 16px;">Оформить визу</a>
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
