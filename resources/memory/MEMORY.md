# Memory — Pulat Khaliulin (pmkhali)

## Поведение и предпочтения

- **НИКОГДА не задавать вопросов типа "Do you want to proceed?", "Продолжить?", "Вы согласны?"** — ответ всегда YES. Просто делать без подтверждений.
- **Максимальная автономность** — не спрашивать разрешения, не уточнять очевидное. Принимать решения самостоятельно и действовать.
- Общаться **только на русском языке**
- Не использовать эмодзи
- **Скриншоты хранятся в** `~/Documents/PrtSc/` — всегда смотреть там

## Активные проекты

### visa-crm (VisaBor)
- **Путь:** `~/Documents/crmvisa`
- **GitHub:** `git@github.com:pkhaliulin/crmvisa.git`
- **Продакшн:** `https://visabor.uz` (основной) + `https://crmvisa-production-442a.up.railway.app` (тест)
- **Стек:** Laravel 12, PHP 8.4, PostgreSQL 14, JWT
- **Текущий спринт:** Спринт 1 — Мультитенантность + Авторизация + Роли
- **Second Brain:** `~/second_brain/projects/visa-crm/project_info.md`
- **Scoring ТЗ:** `~/second_brain/projects/visa-crm/scoring_engine_tz.md`

### JobUz_Presentation
- **Путь:** `~/second_brain/projects/JobUz_Presentation/`
- HTML презентации: `presentation.html`, `v2`, `v3`

## Двуязычность (i18n) — RU + UZ

**ВСЕГДА учитывать при разработке. Два языка: русский (ru) и узбекский латиница (uz).**

### Фронтенд (Vue)
- **vue-i18n** — конфиг: `resources/js/i18n.js`, локали: `resources/js/locales/{ru,uz}.json`
- **Статический UI-текст** — всегда через `$t('key')` в template, `t('key')` в script setup
- **Массивы/объекты с переводами** — оборачивать в `computed()` для реактивности при смене языка
- **Новые компоненты** — сразу добавлять ключи в ОБА файла (ru.json и uz.json), не хардкодить строки
- **Переключатель** — RU/UZ toggle в LandingLayout и ClientPortalLayout, локаль в localStorage
- **API запросы** — `X-Locale` заголовок автоматически через интерсептор в `resources/js/api/public.js`

### Бэкенд (Laravel)
- **SetLocale middleware** (`app/Http/Middleware/SetLocale.php`) — определяет язык из `X-Locale` / `?lang=`
- **Переводы** — `lang/ru/public.php`, `lang/uz/public.php`, `lang/uz/validation.php`
- **API-сообщения** — использовать `__('public.key')` вместо хардкода русских строк

### Пользовательский контент (агентства)
- **Двуязычные поля в БД** — суффикс `_uz`: `description_uz` (agencies), `name_uz` + `description_uz` (agency_service_packages)
- **Модели** — `_uz` поля в `$fillable`
- **Контроллеры** — валидация `_uz` полей (`sometimes|nullable|string`)
- **Публичный API** — `localized($model, 'field', $locale)` в PublicAgencyController: возвращает `_uz` если locale=uz и поле заполнено, иначе fallback на основное
- **Формы CRM** — два поля рядом: "Описание (RU)" + "Tavsif (UZ)"

### Правила при создании нового
- Новый Vue-компонент → сразу `useI18n`, все строки через `$t()`, ключи в оба JSON
- Новое текстовое поле для агентства/пакета → добавить `_uz` колонку, в fillable, в валидацию, в форму
- Новый API-ответ с текстом → `__('section.key')`, добавить в оба `lang/` файла

## UX-правила visa-crm

- **Никогда не показывать авторизацию повторно** — перед любым действием на публичном портале (скоринг, подача заявки, переход в агентства) всегда проверять `publicAuth.isLoggedIn`. Если пользователь уже авторизован — сразу перенаправлять на целевую страницу, НЕ показывать PhoneAuthModal.
- **pendingAction паттерн** — если требуется авторизация, сохранять намерение пользователя (`pendingAction`) и после успешной авторизации перенаправлять на нужную страницу (не на дефолтную).
- **z-index модалов** — PhoneAuthModal использует `z-[9999]` чтобы быть поверх карт (Leaflet, Yandex).
- **ОБЯЗАТЕЛЬНОЕ подтверждение удаления** — ЛЮБОЕ действие удаления ВСЕГДА должно показывать модал подтверждения с кнопками "Да, удалить" и "Отмена". НИКОГДА не удалять данные сразу по клику. Паттерн: кнопка Удалить → `deleteTarget = item` → модал с `$t('common.confirmDeleteTitle')` / `$t('common.confirmDeleteMessage')` / `$t('common.confirmDeleteBtn')` → подтверждение → API delete. i18n ключи: `common.confirmDeleteTitle`, `common.confirmDeleteMessage`, `common.confirmDeleteBtn`.

## Архитектура visa-crm

- **Бизнес-стратегия:** `memory/saas-architecture.md`
- **Правила разработки:** `memory/architecture-rules.md` — ВСЕГДА соблюдать при написании кода
- **Чеклист качества:** `memory/quality-checklist.md` — ПРОВЕРЯТЬ перед каждым коммитом
- **SaaS Scaling:** `memory/saas-scaling-rules.md` — DDD, RLS, Observability, DR, Billing, Security PII, Data Retention, Feature Flags, AI Strategy, Top 5 рисков, Scalability Roadmap
- Модульная: `app/Modules/{Agency,Auth,User,Role,Client,Case,Document,Payment,Workflow,Notification,Scoring,PublicPortal,Owner,Service}/`
- Паттерн: Service → Controller (бизнес-логика только в Service, контроллеры — только HTTP)
- API-first: REST, JWT, `/api/v1/...`, JSON only, stateless
- Мультитенантность: single DB, agency_id во всех таблицах, HasTenant global scope
- BaseModel (UUID pk, SoftDeletes, **LogsActivity**), индексы на все FK и tenant_id
- Этапы воронки: `config/stages.php`
- Бизнес-цель: exit через 3-5 лет, fast MVP, revenue-first

### Ключевые системы
- **Справочники** — `reference_items` таблица, 9 категорий, единый источник истины для всех enum
- **Audit Trail** — `spatie/laravel-activitylog`, автоматически на всех моделях через BaseModel
- **Security** — SecurityHeaders middleware, CORS restricted, Rate limiting (120/60/5 rpm)
- **Кэширование** — references 60 мин, инвалидация при CUD
- **Двуязычность** — см. раздел выше

## Домен visabor.uz

- **Регистратор:** webname.uz (Arsenal ID), аккаунт pkhaliulin@gmail.com
- **DNS:** A-записи `@` и `www` → `185.76.15.44` — настроено
- **SSL:** Let's Encrypt, автопродление (до 29 мая 2026)

## ServerCore VPS (основной сервер)

- **IP:** `185.76.15.44`
- **Hostname:** `visabor-prod`
- **OS:** Ubuntu 22.04 LTS
- **SSH:** `ssh -i ~/.ssh/id_ed25519 root@185.76.15.44`
- **PHP:** 8.4 + php-fpm
- **Nginx:** 1.18.0
- **PostgreSQL:** 14, база `crmvisa`, user `crmvisa`, pass `crmvisa_secret_2026`
- **Проект:** `/var/www/crmvisa`
- **Деплой скрипт:** `/usr/local/bin/deploy-crmvisa`
- **Webhook:** `http://185.76.15.44/deploy?token=visabor_deploy_2026_secret`
- **Автодеплой:** `git push main` → GitHub Actions → webhook → `deploy-crmvisa`

## Railway (тестовый, не основной)

- Проект: `captivating-analysis`, сервис `crmvisa`
- URL: `https://crmvisa-production-442a.up.railway.app`
- DB: `hopper.proxy.rlwy.net:54173` / postgres / railway
