# Container image for platform hosting (Render, Railway, Fly, Koyeb and the like).
#
# Shared cPanel hosting does not use this file at all — follow README sections 28
# to 31 for that. This exists so the site can be put on a free platform host in a
# few minutes, with no server administration, to show people before the real
# hosting and domain are bought.
#
# FrankenPHP is one process serving public/ directly, which suits a container
# platform better than nginx plus php-fpm plus a supervisor.
FROM dunglas/frankenphp:php8.4

# pdo_sqlite ships enabled; pdo_mysql is here so the same image works if you
# point it at a managed MySQL later.
RUN install-php-extensions pdo_mysql intl zip gd opcache pcntl

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Dependencies first, so a code change does not re-resolve the whole tree.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# The built front end assets are committed to the repository, so there is no
# Node build step here.
COPY . .

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && chmod +x docker/entrypoint.sh \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
               storage/logs storage/app/public storage/app/private bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/app/database/database.sqlite \
    SESSION_DRIVER=file \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync

EXPOSE 8080

ENTRYPOINT ["/app/docker/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
