FROM dunglas/frankenphp:php8.3

WORKDIR /app

RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    xml \
    dom \
    gd \
    intl \
    zip \
    curl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

COPY package.json package-lock.json ./

RUN apt-get update \
    && apt-get install -y --no-install-recommends nodejs npm \
    && rm -rf /var/lib/apt/lists/*

RUN npm ci

COPY . .

COPY docker/start.sh /usr/local/bin/start.sh

RUN chmod +x /usr/local/bin/start.sh

RUN composer dump-autoload --optimize

RUN php artisan package:discover --ansi

RUN npm run build

COPY docker/Caddyfile /etc/caddy/Caddyfile

RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/start.sh"]

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]