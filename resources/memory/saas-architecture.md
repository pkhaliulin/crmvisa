# SaaS Architecture — VisaBor CRM

## Бизнес-стратегия

- Multi-tenant SaaS CRM для визовых агентств
- Цель: быстрый запуск, генерация дохода, масштабирование
- Расширение: маркетплейс + Telegram Mini App
- **Exit через 3-5 лет** — код должен быть exit-ready
- Приоритет: Fast MVP > monetization > clean architecture > exit-readiness
- Избегать перфекционизма, задерживающего MVP
- Думать как SaaS-фаундер, готовящийся к продаже

## Стек (строго)

- **Backend:** Laravel (latest stable), API-first, Modular Monolith
- **Frontend:** Vue 3 (Composition API), TypeScript preferred
- **DB:** PostgreSQL
- **Cache/Queues:** Redis
- **Storage:** S3-compatible (документы)
- **Auth:** JWT, token-based only, stateless API

## Архитектурные принципы

- Modular Monolith — четкое разделение доменов
- **Service layer** для бизнес-логики
- **Controllers** — только HTTP (никакой бизнес-логики!)
- **Form Requests** для валидации
- **Policies/Gates** для авторизации
- Минимум логики в моделях
- Чистый, масштабируемый, refactor-friendly код
- Без overengineering

### Структура модулей
```
Modules/
  Agency/ Users/ Clients/ Cases/ Documents/
  Payments/ Workflow/ Notifications/ Marketplace/
```

## Мультитенантность (КРИТИЧНО)

- **Single database** — одна БД для всех
- **tenant_id (agency_id)** во ВСЕХ бизнес-таблицах
- **Индексировать tenant_id** везде
- **Автоматический scoping** по tenant_id (HasTenant trait, global scope)
- **НИКАКОГО кросс-тенантного доступа**
- Тенант определяется из аутентифицированного пользователя
- **Безопасность — высший приоритет.** Всегда проверять tenant isolation.

## API-First (обязательно)

- REST API only
- Версионированные эндпоинты: `/api/v1/`
- NO session-based auth
- Token-based auth only
- JSON responses only
- Ready for Swagger/OpenAPI
- Backend поддерживает: Web, Telegram Mini App, Mobile (future), External integrations, Marketplace
- **Никогда не связывать frontend и backend жестко**

## Telegram & Mobile Readiness

- Telegram Bot (webhooks)
- Telegram Mini App (WebView frontend)
- External API access
- Future mobile app (Flutter/React Native)
- Auth и API responses должны поддерживать все платформы

## Core CRM

- Agencies (tenants), Managers (roles & permissions), Clients
- Visa cases, Case statuses, Workflow states
- Document uploads, SLA tracking
- Internal notes, Activity logs
- Payments, Marketplace module

## Стандарты БД

- **UUID** primary keys (BaseModel с HasUuid)
- **Индексировать ВСЕ foreign keys**
- **Индексировать tenant_id** (agency_id)
- **Soft deletes** везде через BaseModel
- **Timestamps** везде
- Proper constraints (FK с cascadeOnDelete/nullOnDelete)
- Composite indexes на часто фильтруемые комбинации
- Дизайн на **20,000+ активных заявок**

## Справочники (reference_items)

- **Единый источник истины** для всех enum-полей
- 9 категорий: lead_source, employment_type, marital_status, income_type, travel_purpose, education_level, document_category, payment_method, position_level
- Бэкенд: `new ReferenceExists('category')` — НИКОГДА не `in:value1,value2`
- CRM фронтенд: `useReferences()` composable
- Публичный фронтенд: `usePublicReferences()` composable
- Кэширование 60 мин с инвалидацией при изменениях
- Типы виз: `portal_visa_types` (отдельная таблица, slug PK)

## Безопасность

- **JWT** (HS256, 1h TTL, 2w refresh, blacklist enabled)
- **CORS** — только visabor.uz + localhost (config/cors.php)
- **Security Headers** — X-Frame-Options, HSTS, XSS-Protection, nosniff, Referrer-Policy
- **Rate Limiting** — 120/min auth, 60/min guest, 5/min heavy, 10/min auth endpoints
- **Audit Trail** — spatie/laravel-activitylog, автоматически на всех моделях

## Производительность

- **Database queues** для тяжёлых операций (будущее: Redis)
- **Jobs** для фоновой обработки (скоринг, уведомления, экспорт)
- **Никогда не блокировать HTTP** тяжёлой логикой
- **Всегда пагинация** результатов
- **Cache::remember()** для справочников, стран, агрегатов
- **Rate limiting** на тяжёлые эндпоинты (reports, recalculate)

## Обработка ошибок

- **ApiResponse** — единый формат {success, message, data/errors}
- **bootstrap/app.php** — централизованная обработка: Auth(401), Validation(422), NotFound(404), Throttle(429)
- **Никогда не возвращать** stack traces в production
- Будущее: Sentry/Bugsnag для мониторинга

## Правила генерации кода

- Полные файлы-примеры (не generic)
- Включать: migrations, models, relationships, validation
- Enum → ReferenceExists, не hardcode
- Новая модель → BaseModel (автоматический audit trail)
- Новый тяжёлый endpoint → throttle:heavy
- i18n → ru.json + uz.json
- **Production-ready** структура
- Подробно: `memory/architecture-rules.md`
