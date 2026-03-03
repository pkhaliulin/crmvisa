# Правила разработки visa-crm — ВСЕГДА соблюдать

## 1. Справочники (reference_items) — ЕДИНЫЙ ИСТОЧНИК ИСТИНЫ

**Таблица:** `reference_items` (category + code, unique pair)
**9 категорий:** lead_source, employment_type, marital_status, income_type, travel_purpose, education_level, document_category, payment_method, position_level

### При разработке ВСЕГДА:
- **Бэкенд валидация** — использовать `new ReferenceExists('category')` вместо `in:value1,value2`
- **CRM фронтенд** — использовать `useReferences()` из `@/composables/useReferences.js` (singleton, один API вызов)
- **Публичный фронтенд** — использовать `usePublicReferences()` из `@/composables/usePublicReferences.js`
- **Никогда не хардкодить** значения enum в контроллерах или Vue-компонентах
- **Новое enum-поле** → добавить категорию в reference_items, валидировать через ReferenceExists

### Эндпоинты:
- `GET /api/v1/references/all` — для CRM (JWT auth)
- `GET /api/v1/public/references` — для публичного портала (без auth)
- `GET /api/v1/owner/references/{category}` — CRUD для superadmin

### Кэширование:
- `Cache::remember('references:all', 3600, ...)` — 60 мин
- При store/update/destroy → `Cache::forget('references:all')`

### Типы виз — отдельная таблица:
- `portal_visa_types` (slug PK) — единый справочник типов виз
- Валидация: `exists:portal_visa_types,slug`
- CRM страница: `/crm/visa-types`

## 2. Audit Trail (spatie/laravel-activitylog)

- **BaseModel** автоматически логирует ВСЕ изменения через `LogsActivity` trait
- **User** логирует: name, email, phone, role, is_active
- Таблица: `activity_log` — кто, что, когда, старые/новые значения
- **При создании новой модели** — наследовать от BaseModel (автоматически подключается)
- **Для моделей без BaseModel** (User и др.) — добавлять `LogsActivity` вручную + `getActivitylogOptions()`

## 3. Security Headers (SecurityHeaders middleware)

Глобальный middleware, применяется ко ВСЕМ ответам:
- `X-Frame-Options: DENY` — защита от clickjacking
- `X-Content-Type-Options: nosniff` — защита от MIME sniffing
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- `Strict-Transport-Security: max-age=31536000` (только HTTPS)

## 4. CORS (config/cors.php)

**Разрешённые домены (СТРОГО):**
- `https://visabor.uz`
- `https://www.visabor.uz`
- `http://localhost:5173` (dev)
- `http://localhost:3000` (dev)

**Разрешённые заголовки:** Content-Type, Authorization, X-Requested-With, X-Locale, Accept

**При добавлении нового домена** — обновить `config/cors.php`

## 5. Rate Limiting (AppServiceProvider)

- **api** — 120 req/min для авторизованных, 60 для гостей (глобально на все API)
- **auth** — 10 req/min (login, register, OTP)
- **heavy** — 5 req/min (отчёты, пересчёт скоринга, экспорт)

**При создании тяжёлых эндпоинтов** → добавлять `->middleware('throttle:heavy')`
**При создании auth эндпоинтов** → добавлять `->middleware('throttle:auth')` или `throttle:10,1`

## 6. Exception Handler (bootstrap/app.php)

Унифицированная обработка через ApiResponse:
- `AuthenticationException` → 401
- `ValidationException` → 422
- `ModelNotFoundException` / `NotFoundHttpException` → 404
- `ThrottleRequestsException` → 429

**При создании кастомных ошибок** — использовать `ApiResponse::error()` с правильным HTTP кодом

## 7. RBAC (роли и доступ)

**Текущие роли:** owner, manager, client, superadmin
**Middleware:** `role:owner,manager,superadmin`
**Проверка:** `CheckRole` middleware в routes/api.php

**Разделение доступа в маршрутах:**
- `role:owner,manager,superadmin` + `plan.active` — основные CRM операции
- `role:owner,superadmin` + `plan.active` — управление (users, settings, packages)
- `role:superadmin` — owner admin panel
- `role:client` — клиентский портал
- `auth.public` — публичный портал (phone auth)

**При создании нового эндпоинта** → обязательно указать middleware с ролями

## 8. Кэширование — когда использовать

**Кэшировать:**
- Справочники (references) — 60 мин
- Страны (portal_countries) — при первом запросе
- Типы виз (portal_visa_types) — при первом запросе
- Dashboard aggregates — 5-15 мин

**НЕ кэшировать:**
- Данные конкретного кейса/клиента (часто меняются)
- Результаты поиска
- Данные с пагинацией

**Инвалидация** — `Cache::forget('key')` при CUD операциях

## 9. Очереди и Jobs

**Driver:** database (PostgreSQL)
**Таблицы:** jobs, job_batches, failed_jobs

**Когда создавать Job:**
- Пересчёт скоринга (CalculateClientScoreJob — уже есть)
- Отправка email/Telegram уведомлений
- Генерация отчётов/экспорт
- Обработка документов (OCR, конвертация)
- Любая операция > 500ms

**Шаблон:**
```php
class SomeJob implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 60;
    // ...
}
```

## 10. Чек-лист при создании нового функционала

1. Enum-поля → `ReferenceExists` + справочник (НЕ hardcode)
2. Модель → наследовать от BaseModel (audit trail автоматически)
3. i18n → `$t()` + ключи в ru.json и uz.json
4. Маршрут → middleware с ролями + rate limiting если тяжёлый
5. Валидация → в контроллере через `$request->validate()`
6. Тяжёлая операция → Queue Job, не блокировать HTTP
7. Кэшируемые данные → `Cache::remember()` + инвалидация
8. Новый домен/origin → обновить `config/cors.php`
