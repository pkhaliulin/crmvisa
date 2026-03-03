# SaaS Scaling & Enterprise Rules — VisaBor CRM

## Архитектурные пробелы и план их закрытия

### 1. DDD Light — границы доменов
**Текущее:** Modular Monolith с Service-слоем, нет разделения Domain/Application/Infrastructure.
**Целевое:**
```
app/Modules/Case/
    Domain/        — Entity, Value Objects, Domain Events
    Application/   — Use Cases, Application Services
    Infrastructure/ — Repository implementations, External APIs
```
**Domain Events для внедрения:**
- CaseCreated, CaseStatusChanged, PaymentReceived, DocumentUploaded
- ClientRegistered, ScoringCalculated, VisaApproved, VisaDenied
**Правило:** При создании нового модуля — создавать Domain Events для значимых бизнес-действий.

### 2. Мультитенантность — RLS (Row Level Security)
**Риск:** Один баг в запросе = утечка данных ВСЕХ агентств. RED FLAG для инвестора.
**План:**
- PostgreSQL RLS policies на все tenant-таблицы
- SET app.current_tenant_id = 'uuid' в начале каждого запроса
- CREATE POLICY tenant_isolation ON clients FOR ALL USING (agency_id = current_setting('app.current_tenant_id')::uuid)
- Integration tests на кросс-тенантный доступ
- Unique constraints с agency_id (email + agency_id)
**Приоритет:** КРИТИЧЕСКИЙ — делать ДО масштабирования

### 3. Observability
**Текущее:** LogApiRequest middleware, request_logs, MonitoringService. Нет structured logging.
**Целевое:**
- Correlation ID middleware (X-Request-ID в каждый запрос)
- Structured JSON logging (Monolog JSON formatter)
- Log context: tenant_id, user_id, request_id в каждой записи
- Sentry для error tracking (или Flare для Laravel)
- APM: p50/p95/p99 response times
- Centralized logging (ELK или Loki+Grafana)
**Правило:** При добавлении логирования — ВСЕГДА включать correlation_id и tenant_id.

### 4. Disaster Recovery
**Текущее:** Один VPS, нет backup стратегии.
**План:**
- Daily DB dump: `pg_dump` → сжатие → offsite (S3/Backblaze)
- Weekly full snapshot сервера
- RPO: 24 часа (максимальная потеря данных)
- RTO: 4 часа (время восстановления)
- Retention: 30 daily + 12 weekly + 6 monthly
- Restore test раз в месяц
- Скрипт: `/usr/local/bin/backup-crmvisa`
**Приоритет:** КРИТИЧЕСКИЙ — настроить НЕМЕДЛЕННО

### 5. API Versioning
**Текущее:** `/api/v1/...`, нет стратегии развития.
**Правила:**
- v1 — stable, backward compatible, deprecation warnings минимум 3 месяца
- v2 создавать только при breaking changes
- Deprecated endpoints → HTTP header `Deprecation: true` + `Sunset: date`
- Не ломать контракты для существующих клиентов API
- Changelog для каждой версии

### 6. Billing & Monetization
**Текущее:** Нет.
**Архитектура:**
```
plan_limits table:
  plan_slug, feature_key, limit_value
  (basic, max_cases, 50)
  (pro, max_cases, 500)
  (enterprise, max_cases, -1)

feature_flags table:
  plan_slug, feature_key, enabled
  (basic, scoring, false)
  (pro, scoring, true)
```
- Middleware: CheckPlanLimits — проверка лимитов перед операциями
- Usage metering: подсчет использования по тенанту
- Stripe/Payme/Click интеграция
- Webhook для subscription events
**Приоритет:** ВЫСОКИЙ — revenue-first стратегия

### 7. Security — PII & Compliance
**Текущее:** JWT, CORS, rate limiting, security headers.
**Пробелы:**
- Шифрование PII: passport_number, phone, birth_date → `Crypt::encryptString()`
- Masking в логах: никогда не логировать паспортные данные
- Password policy: min 8 chars, complexity
- 2FA для owner/superadmin (TOTP)
- Rate limiting per tenant (не только глобальный)
- PDPL (Personal Data Protection Law Uzbekistan) compliance
- GDPR-ready для международного рынка
**Правило:** НИКОГДА не логировать PII. При работе с паспортными данными — ВСЕГДА шифровать.

### 8. Data Retention
**Визовые данные = чувствительные.**
**Политика:**
- Активные кейсы: хранить без ограничений
- Закрытые кейсы: 3 года, потом анонимизация
- Логи запросов (request_logs): 30 дней (уже есть prune)
- Activity log: 1 год
- Hard delete по запросу клиента (GDPR Art. 17)
- Автоматическая анонимизация: замена PII на хеши через scheduled job
**Правило:** При создании новых таблиц с PII — сразу планировать retention policy.

### 9. Performance — Queue & Workers
**Текущее:** Database queue driver. MVP-оптимально, не масштабируется.
**План перехода:**
- Фаза 1 (сейчас): database driver — OK для <100 jobs/hour
- Фаза 2 (>50 агентств): Redis queue + Laravel Horizon
- Фаза 3 (>200 агентств): отдельный worker-сервер
**Правило:** Мониторить queue latency. Если avg > 5 секунд — пора на Redis.

### 10. CI/CD Quality Gate
**Текущее:** npm run build, ручная проверка.
**Целевое:**
- PHPStan / Larastan level 5+ (статический анализ)
- ESLint + Prettier (фронтенд)
- Pre-commit hooks (Husky + lint-staged)
- PHPUnit: минимум smoke tests на все API endpoints
- Coverage threshold: 40% минимум для бизнес-логики
- GitHub Actions: lint → test → build → deploy
**Правило:** Не деплоить без прохождения всех checks.

### 11. Масштабирование инфраструктуры
**Текущее:** Один VPS, всё на одной машине. Single Point of Failure.
**Roadmap:**
- Фаза 1 (1-50 агентств): один VPS — текущее
- Фаза 2 (50-200): отдельный DB сервер, Redis, CDN для static
- Фаза 3 (200-1000): Load Balancer + 2 app servers, managed DB
- Фаза 4 (1000+): Kubernetes/Docker Swarm, auto-scaling
**Zero-downtime deploy:**
- Blue-green или rolling deployment
- Database migrations: backward-compatible only
- `php artisan down --retry=60` только в крайнем случае

### 12. Интеграции — Adapter Pattern
**CRM для виз = много интеграций:**
- SMS: Eskiz.uz / PlayMobile
- Telegram: Bot API (webhook)
- Email: SMTP / SendGrid
- Payments: Payme, Click, Stripe
- OCR: Google Vision / Tesseract
- Government APIs: визовые системы
**Архитектура:**
```
app/Modules/Integration/
    Contracts/
        SmsProviderInterface.php
        PaymentGatewayInterface.php
    Adapters/
        EskizSmsAdapter.php
        PaymeSmsAdapter.php
    Services/
        IntegrationService.php
```
**Правила:**
- Adapter pattern для всех внешних сервисов
- Retry policy: 3 попытки с exponential backoff
- Circuit breaker: если 5 ошибок подряд — отключить на 5 минут
- Timeout: максимум 10 секунд на внешний вызов

### 13. Analytics & Data Warehouse
**Для SaaS метрики критичны:**
- LTV (Lifetime Value) по агентству
- Churn rate
- Conversion funnel (лид → клиент → кейс → виза)
- Revenue per agency
- Feature usage
**Архитектура:**
- Event tracking (значимые бизнес-события)
- Отдельная analytics schema или таблицы
- Агрегация через scheduled jobs
- Dashboard для superadmin

### 14. Feature Flags
**Безопасный выпуск новых фич:**
```
feature_flags table:
  key, enabled, rollout_percent, plans[]
```
- Проверка: `FeatureFlag::isEnabled('scoring_v2', $agency)`
- Постепенный rollout: 10% → 50% → 100%
- Per-plan features: basic/pro/enterprise
- Kill switch: мгновенное отключение проблемной фичи

### 15. AI Strategy
**Текущее:** Scoring модуль, OpenAI API.
**Правила:**
- Token budgeting: max tokens per request, per tenant per day
- Cost tracking: логировать стоимость каждого AI-вызова
- Model selection: GPT-4 для скоринга, GPT-3.5 для простых задач
- Async: все AI-вызовы через Queue (никогда синхронно в HTTP)
- Fallback: если AI недоступен — graceful degradation, не блокировать
- Rate limit per tenant на AI-операции
- Caching AI-ответов для одинаковых inputs (TTL 24h)

## Top 5 архитектурных рисков

1. **Single server** — SPOF, нет redundancy
2. **Database queue driver** — не масштабируется
3. **Нет RLS** — риск кросс-тенантной утечки
4. **Нет backup strategy** — потеря данных = смерть бизнеса
5. **Нет billing engine** — нет монетизации = нет бизнеса

## Scalability Roadmap

| Этап | Агентств | Инфраструктура | Приоритеты |
|------|----------|----------------|------------|
| MVP | 1-50 | 1 VPS | Функциональность, UX |
| Growth | 50-200 | VPS + managed DB + Redis | RLS, Billing, Backup |
| Scale | 200-1000 | LB + 2 app + DB cluster | Observability, CI/CD, Workers |
| Enterprise | 1000+ | K8s, auto-scale, multi-region | HA, DR, Compliance |
