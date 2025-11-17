# C:\Users\Maral\PhpstormProjects\leader_grant_backend\docker\php\Dockerfile

# Берем официальный образ PHP 8.2-FPM (FPM нужен для Nginx)
FROM php:8.4-fpm-alpine

# Устанавливаем расширения, необходимые для Symfony и Postgres
RUN apk add --no-cache \
    postgresql-dev \
    icu-dev \
    libzip-dev

# Включаем сами PHP-расширения
RUN docker-php-ext-install pdo pdo_pgsql pgsql intl zip opcache

# Устанавливаем Composer (менеджер пакетов PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Устанавливаем рабочую директорию
WORKDIR /var/www/project

# Меняем пользователя на www-data (стандарт для FPM)
USER www-data
