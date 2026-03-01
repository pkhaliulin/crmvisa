# Доступы к системе VisaBor

## Суперадмин (Owner Panel)

| URL | Email | Пароль | Роль |
|-----|-------|--------|------|
| visabor.uz/crm | pulat@visabor.uz | VisaBor@2026! | superadmin |

---

## Агентства (CRM Panel — visabor.uz/app)

### Silk Road Visa (Enterprise, Ташкент)
| Роль | Email | Пароль |
|------|-------|--------|
| Owner | owner@silkroad.test | Owner@2026! |
| Manager 1 | m1@silkroad.test | Manager@2026! |
| Manager 2 | m2@silkroad.test | Manager@2026! |
| Manager 3 | m3@silkroad.test | Manager@2026! |
| Manager 4 | m4@silkroad.test | Manager@2026! |
| Manager 5 | m5@silkroad.test | Manager@2026! |

### Euro Visa Pro (Pro, Самарканд)
| Роль | Email | Пароль |
|------|-------|--------|
| Owner | owner@eurovisa.test | Owner@2026! |
| Manager 1 | m1@eurovisa.test | Manager@2026! |
| Manager 2 | m2@eurovisa.test | Manager@2026! |
| Manager 3 | m3@eurovisa.test | Manager@2026! |
| Manager 4 | m4@eurovisa.test | Manager@2026! |
| Manager 5 | m5@eurovisa.test | Manager@2026! |

### Asia Passport (Enterprise, Бухара)
| Роль | Email | Пароль |
|------|-------|--------|
| Owner | owner@asiapass.test | Owner@2026! |
| Manager 1 | m1@asiapass.test | Manager@2026! |
| Manager 2 | m2@asiapass.test | Manager@2026! |
| Manager 3 | m3@asiapass.test | Manager@2026! |
| Manager 4 | m4@asiapass.test | Manager@2026! |
| Manager 5 | m5@asiapass.test | Manager@2026! |

### Visa Grand (Pro, Нукус)
| Роль | Email | Пароль |
|------|-------|--------|
| Owner | owner@visagrand.test | Owner@2026! |
| Manager 1 | m1@visagrand.test | Manager@2026! |
| Manager 2 | m2@visagrand.test | Manager@2026! |
| Manager 3 | m3@visagrand.test | Manager@2026! |
| Manager 4 | m4@visagrand.test | Manager@2026! |
| Manager 5 | m5@visagrand.test | Manager@2026! |

### Travel Docs UZ (Pro, Фергана)
| Роль | Email | Пароль |
|------|-------|--------|
| Owner | owner@traveldocs.test | Owner@2026! |
| Manager 1 | m1@traveldocs.test | Manager@2026! |
| Manager 2 | m2@traveldocs.test | Manager@2026! |
| Manager 3 | m3@traveldocs.test | Manager@2026! |
| Manager 4 | m4@traveldocs.test | Manager@2026! |
| Manager 5 | m5@traveldocs.test | Manager@2026! |

---

## Публичный портал (Скоринг)

| URL | Вход | PIN (заглушка) |
|-----|------|----------------|
| visabor.uz/scoring | Любой номер телефона | 6236 |

---

## Сервер

| Параметр | Значение |
|----------|----------|
| IP | 185.76.15.44 |
| Провайдер | servercore.uz |
| ОС | Ubuntu 22.04 |
| SSH ключ | ~/.ssh/id_ed25519 |
| SSH команда | `ssh -i ~/.ssh/id_ed25519 root@185.76.15.44` |
| Путь к проекту | /var/www/crmvisa |

---

## Роли в системе

| Роль | Доступ |
|------|--------|
| superadmin | /crm — полный доступ ко всей системе |
| owner | /app — управление своим агентством, отчёты, настройки |
| manager | /app — работа с кейсами (видит свои или все, зависит от настроек агентства) |
