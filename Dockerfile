# Imagem de produção do Aurora Workspaces (usada pelo Render).
# Três estágios: dependências PHP → build do front-end → runtime Apache + PHP.

# ── 1) Dependências PHP ─────────────────────────────────────────
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

# ── 2) Front-end (o tema do Filament importa arquivos de vendor/) ─
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY --from=vendor /app /app
RUN npm run build

# ── 3) Runtime ─────────────────────────────────────────────────
FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libzip-dev \
    && docker-php-ext-install intl zip opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# PHP de produção + um pouco mais de memória para o seed
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf "memory_limit=256M\nopcache.validate_timestamps=0\nexpose_php=Off\n" > "$PHP_INI_DIR/conf.d/aurora.ini"

# Apache servindo public/, com .htaccess e na porta que o Render define ($PORT)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    PORT=10000
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && sed -i 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY --from=assets /app /var/www/html
RUN rm -rf node_modules \
    && chown -R www-data:www-data storage bootstrap/cache database

COPY docker/entrypoint.sh /usr/local/bin/aurora-entrypoint
RUN chmod +x /usr/local/bin/aurora-entrypoint

EXPOSE 10000
CMD ["aurora-entrypoint"]
