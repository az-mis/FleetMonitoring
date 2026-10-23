# syntax=docker/dockerfile:1
FROM php:8.4-apache-bookworm AS php-base
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev libonig-dev libpq-dev libzip-dev libgmp-dev unzip \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" bcmath gmp intl mbstring pcntl pdo_mysql pdo_pgsql zip opcache gd exif \
    && rm -rf /var/lib/apt/lists/* \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
WORKDIR /var/www/html

FROM php-base AS dependencies
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts \
    && composer check-platform-reqs --no-dev

FROM node:24.19.0-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm install --global npm@11.17.0 && npm ci --ignore-scripts
COPY resources ./resources
COPY Modules ./Modules
COPY app ./app
COPY .gitignore ./
COPY vite.config.js ./
COPY --from=dependencies /var/www/html/vendor ./vendor
RUN npm run build

FROM php-base AS production
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr LOG_LEVEL=info \
    SESSION_SECURE_COOKIE=true AUTH_REGISTRATION_ENABLED=false
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && printf '\nServerName localhost\nServerTokens Prod\nServerSignature Off\n' >> /etc/apache2/apache2.conf \
    && mkdir -p /var/run/apache2 /var/lock/apache2 \
    && chown -R www-data:www-data /var/run/apache2 /var/lock/apache2
COPY --from=dependencies /var/www/html /var/www/html
COPY --from=assets /app/public/build ./public/build
RUN mkdir -p storage/app/public storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && ln -s /var/www/html/storage/app/public public/storage
USER www-data
EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD php -r '$r = @file_get_contents("http://127.0.0.1:8080/up"); exit($r === false ? 1 : 0);'
ENTRYPOINT ["app-entrypoint"]
CMD ["apache2-foreground"]
