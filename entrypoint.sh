#!/bin/bash
set -e

echo "🧨 СБРОС БАЗЫ (Hard Reset)..."
# Удаляем вообще всё
php bin/console doctrine:schema:drop --force --full-database

echo "🏗️ СОЗДАНИЕ СХЕМЫ ИЗ КОДА..."
# Создаем таблицы напрямую из PHP-классов (игнорируя папку migrations)
php bin/console doctrine:schema:create

echo "🌱 ЗАГРУЗКА ДАННЫХ (Fixtures)..."
# Заливаем админа и банки
php bin/console doctrine:fixtures:load --no-interaction --append

echo "🔥 ЗАПУСК APACHE..."
exec apache2-foreground
