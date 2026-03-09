@extends('landing.layout')

@section('meta')
<title>О платформе visabor.uz -- AI Visa Platform для граждан Узбекистана</title>
<meta name="description" content="visabor.uz -- AI-платформа нового поколения для граждан Узбекистана. Проверка шансов на визу, сравнение направлений, персональный скоринг и подбор агентства.">
<meta property="og:title" content="О платформе visabor.uz">
<meta property="og:description" content="AI-платформа для граждан Узбекистана. Проверка шансов на визу по 12+ факторам.">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url('/about') }}">
<link rel="canonical" href="{{ url('/about') }}">
@endsection

@section('structured_data')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "VisaBor",
    "url": "https://visabor.uz",
    "logo": "https://visabor.uz/logo.png",
    "description": "AI-платформа для граждан Узбекистана. Проверка шансов на визу, сравнение направлений и подбор агентства.",
    "address": {
        "@type": "PostalAddress",
        "addressLocality": "Ташкент",
        "addressCountry": "UZ"
    },
    "contactPoint": {
        "@type": "ContactPoint",
        "contactType": "customer support",
        "email": "info@visabor.uz",
        "availableLanguage": ["Russian", "Uzbek"]
    },
    "sameAs": [
        "https://t.me/visaborbot"
    ]
}
</script>
@endsection

@section('content')
<section class="section" style="padding-top: 120px;">
    <div class="container" style="max-width: 800px;">
        <div class="section-header">
            <div class="tag">О нас</div>
            <h1>О платформе visabor.uz</h1>
        </div>

        <div style="font-size: 1.05rem; line-height: 1.8; color: var(--text-secondary);">
            <h2 style="font-size: 1.4rem; margin-bottom: 16px; color: var(--text-primary);">Что такое visabor.uz?</h2>
            <p style="margin-bottom: 24px;">
                <strong>visabor.uz</strong> -- это AI-платформа нового поколения, созданная специально для граждан Узбекистана. Мы помогаем оценить ваши шансы на получение визы ещё до подачи документов, сравнить направления и выбрать оптимальный маршрут.
            </p>

            <h2 style="font-size: 1.4rem; margin-bottom: 16px; color: var(--text-primary);">Как работает AI-скоринг?</h2>
            <p style="margin-bottom: 24px;">
                Наша система анализирует более <strong>12 факторов</strong> вашего профиля: визовую историю, занятость, финансовое положение, семейное положение, цель поездки и другие параметры. На основе этих данных AI-алгоритм рассчитывает персональную вероятность одобрения визы для каждого направления.
            </p>

            <h2 style="font-size: 1.4rem; margin-bottom: 16px; color: var(--text-primary);">Точность прогнозов</h2>
            <p style="margin-bottom: 24px;">
                Точность нашей модели составляет около <strong>94%</strong> на основе исторических данных по одобрениям и отказам. Модель постоянно обучается на новых данных, повышая точность прогнозов.
            </p>

            <h2 style="font-size: 1.4rem; margin-bottom: 16px; color: var(--text-primary);">Проверенные партнёры</h2>
            <p style="margin-bottom: 24px;">
                visabor.uz объединяет лучшие визовые агентства Узбекистана на одной платформе. Каждое агентство проходит проверку перед подключением. После анализа профиля вы можете продолжить оформление визы напрямую с выбранным агентством.
            </p>

            <h2 style="font-size: 1.4rem; margin-bottom: 16px; color: var(--text-primary);">Безопасность данных</h2>
            <p style="margin-bottom: 24px;">
                Мы серьёзно относимся к защите ваших данных. Все соединения шифруются по протоколу HTTPS. Персональные данные хранятся в зашифрованном виде. Мы не передаём и не продаём ваши данные третьим лицам.
            </p>

            <h2 style="font-size: 1.4rem; margin-bottom: 16px; color: var(--text-primary);">Поддерживаемые направления</h2>
            <p style="margin-bottom: 24px;">
                На данный момент в базе платформы более <strong>40 направлений</strong>, включая страны Шенгенской зоны, Великобританию, США, Канаду, Японию, Южную Корею, ОАЭ, Турцию и другие. Список постоянно расширяется.
            </p>

            <h2 style="font-size: 1.4rem; margin-bottom: 16px; color: var(--text-primary);">Языки</h2>
            <p style="margin-bottom: 24px;">
                Платформа полностью работает на <strong>русском</strong> и <strong>узбекском</strong> языках. Поддержка также доступна на обоих языках через Telegram-бот.
            </p>

            <h2 style="font-size: 1.4rem; margin-bottom: 16px; color: var(--text-primary);">Контакты</h2>
            <p style="margin-bottom: 8px;">Email: <a href="mailto:info@visabor.uz" style="color: var(--blue-bright);">info@visabor.uz</a></p>
            <p style="margin-bottom: 8px;">Telegram: <a href="https://t.me/visaborbot" style="color: var(--blue-bright);">@visaborbot</a></p>
            <p style="margin-bottom: 8px;">Адрес: Ташкент, Узбекистан</p>
        </div>

        <div style="text-align: center; margin-top: 48px;">
            <a href="/#scoring" class="btn btn-primary btn-lg">Проверить шансы на визу</a>
        </div>
    </div>
</section>
@endsection
