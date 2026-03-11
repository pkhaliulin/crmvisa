#!/bin/bash
# Настройка очереди задач на продакшн сервере
# Запускать: ssh root@185.76.15.44 'bash -s' < deploy/setup-queue.sh

set -e

echo "=== Настройка Redis Queue + Horizon ==="

# 1. Проверить Redis
redis-cli ping || { echo "Redis не запущен!"; exit 1; }

# 2. Обновить .env
cd /var/www/crmvisa
sed -i 's/QUEUE_CONNECTION=database/QUEUE_CONNECTION=redis/' .env
echo "QUEUE_CONNECTION=redis установлен"

# 3. Копировать Supervisor конфиг
cp deploy/supervisor/horizon.conf /etc/supervisor/conf.d/horizon.conf
echo "Supervisor конфиг скопирован"

# 4. Перечитать конфиг и запустить
supervisorctl reread
supervisorctl update
supervisorctl start horizon
echo "Horizon запущен"

# 5. Проверить crontab
(crontab -l 2>/dev/null | grep -q "schedule:run") || {
    (crontab -l 2>/dev/null; echo "* * * * * cd /var/www/crmvisa && php artisan schedule:run >> /dev/null 2>&1") | crontab -
    echo "Crontab добавлен"
}

# 6. Кэш конфигов
php artisan config:cache
php artisan route:cache

echo "=== Готово! Horizon работает ==="
supervisorctl status horizon
