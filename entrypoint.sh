#!/bin/bash
set -e

echo "🚀 Запуск миграций..."
# Используем force, чтобы не было вопросов, если база рассинхронизирована (для первого запуска)
# php bin/console doctrine:schema:update --force --complete
# Или стандартные миграции:
php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing

# (Опционально) Загрузка фикстур
# if [ "$LOAD_FIXTURES" = "true" ]; then
#     echo "🌱 Загрузка демо-данных (Fixtures)..."
#     php bin/console doctrine:fixtures:load --no-interaction --append
# fi

# --- ВАЖНОЕ ИСПРАВЛЕНИЕ ЗДЕСЬ ---
echo "🔧 Настройка прав доступа..."
# Так как мы перенесли кеш в /tmp (в Kernel.php), нужно дать туда доступ веб-серверу
mkdir -p /tmp/cache /tmp/log
chown -R www-data:www-data /tmp/cache /tmp/log
chmod -R 777 /tmp/cache /tmp/log
# --------------------------------

echo "🔥 Запуск Apache..."
exec apache2-foreground
