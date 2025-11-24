# setup.ps1 - Clean Version

Write-Host "Starting Leader Grant Backend Setup..." -ForegroundColor Cyan

# 1. Проверка .env файла
if (-not (Test-Path ".env")) {
    Write-Host "Creating .env file from .env.example..." -ForegroundColor Yellow
    
    if (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
    }
    else {
        # Внимание: "@ должен быть в самом начале строки (без пробелов слева)
        $envContent = @"
APP_ENV=dev
APP_SECRET=auto_generated_secret_$(Get-Random)
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
"@
        Set-Content -Path ".env" -Value $envContent
    }
    Write-Host ".env created." -ForegroundColor Green
}
else {
    Write-Host ".env already exists." -ForegroundColor Green
}

# 2. Запуск Docker контейнеров
Write-Host "Building and starting Docker containers..." -ForegroundColor Cyan
docker-compose up -d --build --remove-orphans

if ($LASTEXITCODE -ne 0) {
    Write-Host "Docker Error. Please check Docker Desktop is running." -ForegroundColor Red
    exit 1
}

Write-Host "Waiting for database to initialize (10 seconds)..." -ForegroundColor Cyan
Start-Sleep -Seconds 10

# 3. Установка зависимостей Composer
Write-Host "Installing PHP dependencies..." -ForegroundColor Cyan
docker-compose exec -T php composer install --no-interaction --optimize-autoloader

# 4. Генерация JWT ключей
Write-Host "Generating JWT keys..." -ForegroundColor Cyan
docker-compose exec -T php bin/console lexik:jwt:generate-keypair --skip-if-exists

# 5. Настройка Базы Данных (Schema + Fixtures)
Write-Host "Setting up Database (Schema and Fixtures)..." -ForegroundColor Cyan
docker-compose exec -T php bin/console doctrine:schema:drop --force --full-database --no-interaction
docker-compose exec -T php bin/console doctrine:schema:create --no-interaction
docker-compose exec -T php bin/console doctrine:fixtures:load --no-interaction --append

# 6. Права на папки
Write-Host "Fixing permissions..." -ForegroundColor Cyan
docker-compose exec -T php chmod -R 777 var/cache var/log var/storage public/uploads

Write-Host "Setup Complete!" -ForegroundColor Green
Write-Host "API available at: http://localhost:8080" -ForegroundColor Green
Write-Host "Documentation: http://localhost:8080/api/doc" -ForegroundColor Green