# One container: nginx, PHP-FPM, the queue worker and the scheduler, under
# supervisord. Postgres and Redis run beside it (compose.yaml).
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx supervisor curl unzip libpq-dev libzip-dev libicu-dev \
    && docker-php-ext-install pdo_pgsql pgsql bcmath pcntl zip intl \
    && pecl install redis && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Node only to build the front end: `vite build` shells out to
# `php artisan wayfinder:generate`, so it builds here and is thrown away below.
# Corepack provides the Yarn version pinned by `packageManager`.
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && npm install -g corepack \
    && corepack enable \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY package.json yarn.lock .yarnrc.yml ./
RUN yarn install --immutable

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative \
    && php artisan package:discover \
    && yarn build \
    && rm -rf node_modules .yarn

COPY deploy/nginx.conf /etc/nginx/sites-available/default
COPY deploy/supervisord.conf /etc/supervisor/conf.d/fleche.conf
COPY deploy/entrypoint.sh /usr/local/bin/entrypoint

RUN chmod +x /usr/local/bin/entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["entrypoint"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
