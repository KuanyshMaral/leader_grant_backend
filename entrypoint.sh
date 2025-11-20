#!/bin/bash
set -e

echo "🧨 HARD RESET DATABASE (DEMO MODE)..."

# 1. Полное удаление всех таблиц (игнорируя ошибки, если базы нет)
php bin/console doctrine:schema:drop --force --full-database --no-interaction

# 2. Создание схемы БД напрямую из Entity (игнорируем файлы миграций!)
# Это самый надежный способ, так как он берет структуру прямо из вашего PHP кода.
echo "🏗️ Creating schema from Entities..."
php bin/console doctrine:schema:create --no-interaction

# 3. Заливка демо-данных (Админ, Банки)
echo "🌱 Loading Fixtures..."
php bin/console doctrine:fixtures:load --no-interaction --append

# 4. Настройка прав для кеша (как мы делали раньше)
echo "🔧 Fixing permissions..."
mkdir -p /tmp/cache /tmp/log
chown -R www-data:www-data /tmp/cache /tmp/log
chmod -R 777 /tmp/cache /tmp/log

echo "🔥 Starting Apache..."
exec apache2-foreground
