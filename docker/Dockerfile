FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_mysql zip

# ← Установи Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

# ← Копируй composer файлы ДО установки зависимостей
COPY composer.json composer.lock ./

# ← Установи зависимости БЕЗ скриптов (--no-scripts)
RUN composer install --no-dev --optimize-autoloader --no-scripts

CMD ["php-fpm"]
