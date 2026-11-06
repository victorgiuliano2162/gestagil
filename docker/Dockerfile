# Stage 1: Build frontend assets
FROM node:22-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: Vendor dependencies
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev

# Stage 3: Runtime environment
FROM dunglas/frankenphp:1-php8.4-alpine AS runtime

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    sqlite \
    sqlite-libs \
    gzip \
    bash \
    curl \
    icu-data-full

RUN install-php-extensions \
    pdo_sqlite \
    intl \
    gd \
    zip \
    opcache \
    pcntl

# Setup directories and permissions
WORKDIR /var/www/html

RUN mkdir -p \
    /data/db \
    /backups \
    storage/app/private/logos \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Copy project files and build artifacts
COPY . /var/www/html
COPY --from=vendor /app/vendor /var/www/html/vendor
COPY --from=assets /app/public/build /var/www/html/public/build
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/php/php.ini /usr/local/etc/php/conf.d/gestagil.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh && \
    chown -R www-data:www-data /var/www/html /data/db /backups

USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
