#!/bin/bash
# setup.sh - Автоматическая настройка окружения Leader Grant Backend

set -e # Остановить скрипт при ошибке

echo -e "\033[0;36m🚀 Starting Leader Grant Backend Setup...\033[0m"

# 1. Проверка .env файла
if [ ! -f ".env" ]; then
    echo -e "\033[0;33m📝 Creating .env file...\033[0m"
    if [ -f ".env.example" ]; then
        cp .env.example .env
    else
        cat <<EOF > .env
APP_ENV=dev
APP_SECRET=auto_generated_secret_$(openssl rand -hex 16)
DATABASE_URL="postgresql://app:!ChangeMe!@db:5432/app?serverVersion=15&charset=utf8"
MESSENGER_TRANSPORT_DSN=doctrine://default
MAILER_DSN=null://null
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=leader_grant_secret
POSTGRES_DB=app
POSTGRES_USER=app
POSTGRES_PASSWORD=!ChangeMe!
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
EOF
    fi
    echo -e "\033[0;32m✅ .env created.\033[0m"
else
    echo -e "\033[0;32m✅ .env already exists.\033[0m"
fi

# 2. Запуск Docker контейнеров
echo -e "\033[0;36m🐳 Building and starting Docker containers...\033[0m"
docker-compose up -d --build --remove-orphans

echo -e "\033[0;36m⏳ Waiting for database to initialize (10 seconds)...\033[0m"
sleep 10

# 3. Установка зависимостей Composer
echo -e "\033[0;36m📦 Installing PHP dependencies...\033[0m"
docker-compose exec -T php composer install --no-interaction --optimize-autoloader

# 4. Генерация JWT ключей
echo -e "\033[0;36m🔑 Generating JWT keys...\033[0m"
docker-compose exec -T php bin/console lexik:jwt:generate-keypair --skip-if-exists

# 5. Настройка Базы Данных
echo -e "\033[0;36m🗄️ Setting up Database (Schema & Fixtures)...\033[0m"
docker-compose exec -T php bin/console doctrine:schema:drop --force --full-database --no-interaction
docker-compose exec -T php bin/console doctrine:schema:create --no-interaction
docker-compose exec -T php bin/console doctrine:fixtures:load --no-interaction --append

# 6. Права на папки
echo -e "\033[0;36m🔧 Fixing permissions...\033[0m"
docker-compose exec -T php chmod -R 777 var/cache var/log var/storage public/uploads

echo -e "\033[0;32m✅ Setup Complete!\033[0m"
echo -e "\033[0;32m🌍 API available at: http://localhost:8080\033[0m"
echo -e "\033[0;32m📄 Documentation: http://localhost:8080/api/doc\033[0m"
