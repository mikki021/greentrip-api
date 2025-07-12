FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    supervisor \
    cron \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

RUN apt-get clean && rm -rf /var/lib/apt/lists/*
RUN pecl install redis && docker-php-ext-enable redis
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
COPY . /var/www
COPY --chown=www-data:www-data . /var/www
USER www-data
EXPOSE 9000
CMD ["php-fpm"]