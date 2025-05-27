# Используем базовый образ PHP 8.4 с поддержкой FPM
FROM php:8.4-fpm

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libxml2-dev \
    libonig-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install \
    pdo_pgsql \
    opcache \
    mbstring \
    zip \
    xml

# Устанавливаем Composer
COPY --from=composer:2.8.4 /usr/bin/composer /usr/bin/composer

# Устанавливаем рабочую директорию
WORKDIR /var/www/html

# Добавляем пользователя с локальным UID и GID
ARG UID=1000
ARG GID=1000
RUN groupadd -g $GID symfony && \
    useradd -u $UID -g $GID -m symfony && \
    chown -R symfony:symfony /var/www/html

# Переключаемся на нового пользователя
USER symfony
