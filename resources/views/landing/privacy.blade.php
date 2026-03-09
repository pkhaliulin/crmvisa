@extends('landing.layout')

@section('meta')
<title>Политика конфиденциальности -- visabor.uz</title>
<meta name="description" content="Политика конфиденциальности платформы visabor.uz. Как мы собираем, используем и защищаем ваши персональные данные.">
<meta property="og:title" content="Политика конфиденциальности -- visabor.uz">
<meta property="og:type" content="website">
<link rel="canonical" href="{{ url('/privacy') }}">
<meta name="robots" content="noindex, follow">
@endsection

@section('content')
<section class="section" style="padding-top: 120px;">
    <div class="container" style="max-width: 800px;">
        <div class="section-header">
            <h1 style="font-size: clamp(1.6rem, 3vw, 2.2rem);">Политика конфиденциальности</h1>
            <p>Последнее обновление: 1 марта 2026 г.</p>
        </div>

        <div style="font-size: 0.95rem; line-height: 1.8; color: var(--text-secondary);">
            <h2 style="font-size: 1.2rem; margin: 32px 0 12px; color: var(--text-primary);">1. Общие положения</h2>
            <p>Настоящая Политика конфиденциальности определяет порядок обработки и защиты персональных данных пользователей платформы visabor.uz (далее -- «Платформа»), принадлежащей VisaBor (далее -- «Оператор»).</p>

            <h2 style="font-size: 1.2rem; margin: 32px 0 12px; color: var(--text-primary);">2. Какие данные мы собираем</h2>
            <p>При использовании Платформы мы можем собирать следующие данные:</p>
            <ul style="padding-left: 24px; margin: 12px 0;">
                <li>Номер телефона (для авторизации и связи)</li>
                <li>Имя, фамилия, дата рождения</li>
                <li>Гражданство и паспортные данные</li>
                <li>Информация о занятости и доходах</li>
                <li>Семейное положение</li>
                <li>Визовая история</li>
                <li>Адрес электронной почты</li>
                <li>Данные о посещении (IP-адрес, тип браузера, время визита)</li>
            </ul>

            <h2 style="font-size: 1.2rem; margin: 32px 0 12px; color: var(--text-primary);">3. Цели обработки данных</h2>
            <p>Ваши данные используются для:</p>
            <ul style="padding-left: 24px; margin: 12px 0;">
                <li>Предоставления AI-скоринга вероятности получения визы</li>
                <li>Персонализации рекомендаций и подбора направлений</li>
                <li>Оформления визовых заявок через партнёрские агентства</li>
                <li>Уведомлений о статусе заявок</li>
                <li>Улучшения качества сервиса</li>
                <li>Связи с вами по техническим вопросам</li>
            </ul>

            <h2 style="font-size: 1.2rem; margin: 32px 0 12px; color: var(--text-primary);">4. Защита данных</h2>
            <p>Мы принимаем необходимые технические и организационные меры для защиты ваших данных:</p>
            <ul style="padding-left: 24px; margin: 12px 0;">
                <li>Все соединения защищены протоколом HTTPS</li>
                <li>Персональные данные хранятся в зашифрованном виде</li>
                <li>Доступ к данным ограничен авторизованными сотрудниками</li>
                <li>Регулярное резервное копирование</li>
            </ul>

            <h2 style="font-size: 1.2rem; margin: 32px 0 12px; color: var(--text-primary);">5. Передача данных третьим лицам</h2>
            <p>Мы не продаём ваши данные. Данные могут быть переданы партнёрским визовым агентствам только с вашего согласия и исключительно для целей оформления визы. Агентства обязаны соблюдать конфиденциальность данных.</p>

            <h2 style="font-size: 1.2rem; margin: 32px 0 12px; color: var(--text-primary);">6. Хранение данных</h2>
            <p>Данные хранятся на серверах, расположенных в защищённых дата-центрах. Срок хранения -- до момента удаления аккаунта пользователем или по запросу.</p>

            <h2 style="font-size: 1.2rem; margin: 32px 0 12px; color: var(--text-primary);">7. Файлы cookie</h2>
            <p>Платформа использует файлы cookie для обеспечения корректной работы, сохранения языковых предпочтений и авторизации. Вы можете отключить cookie в настройках браузера.</p>

            <h2 style="font-size: 1.2rem; margin: 32px 0 12px; color: var(--text-primary);">8. Ваши права</h2>
            <p>Вы имеете право:</p>
            <ul style="padding-left: 24px; margin: 12px 0;">
                <li>Запросить доступ к своим данным</li>
                <li>Исправить неточные данные</li>
                <li>Удалить свой аккаунт и все связанные данные</li>
                <li>Отказаться от рассылок и уведомлений</li>
            </ul>

            <h2 style="font-size: 1.2rem; margin: 32px 0 12px; color: var(--text-primary);">9. Контакты</h2>
            <p>По вопросам обработки персональных данных обращайтесь: <a href="mailto:privacy@visabor.uz" style="color: var(--blue-bright);">privacy@visabor.uz</a></p>
        </div>
    </div>
</section>
@endsection
