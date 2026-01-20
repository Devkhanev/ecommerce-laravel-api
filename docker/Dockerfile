FROM php:8.4-fpm

# 1. Добавляем libicu-dev в список системных библиотек
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    zip \
    git \
    curl \
    # 2. Настраиваем и устанавливаем расширение intl
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_mysql zip intl

# ← Установи Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

# ← Копируй composer файлы ДО установки зависимостей
COPY composer.json composer.lock ./

# ← Установи зависимости БЕЗ скриптов (--no-scripts)
RUN composer install --no-dev --optimize-autoloader --no-scripts

CMD ["php-fpm"]
