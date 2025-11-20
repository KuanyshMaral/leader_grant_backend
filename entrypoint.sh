#!/bin/bash
set -e

# 1. Накатываем миграции (всегда при запуске)
echo "🚀 Запуск миграций..."
php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing

# 2. Загружаем фикстуры (ТОЛЬКО если мы попросили это через переменную)
if [ "$LOAD_FIXTURES" = "true" ]; then
    echo "🌱 Загрузка демо-данных (Fixtures)..."
    # --append добавит данные к существующим, не удаляя базу (безопаснее на проде)
    php bin/console doctrine:fixtures:load --no-interaction --append
fi

# 3. Запускаем Apache (как обычно)
echo "🔥 Запуск Apache..."
exec apache2-foreground
