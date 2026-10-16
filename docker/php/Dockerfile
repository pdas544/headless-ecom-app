FROM php:8.3-fpm-alpine

# Week-1 minimal extensions only (fast build ~30s).
# Heavy extensions (intl, zip, redis) deferred to Week-4 when needed.
# Predis client used in Week-1 works over TCP without php-redis ext.
RUN apk add --no-cache \
      postgresql-libs \
    && apk add --no-cache --virtual .build-deps \
      postgresql-dev \
      $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) pdo_pgsql pgsql opcache \
    && apk del .build-deps \
    && rm -rf /tmp/* /var/cache/apk/*

# OPcache + production defaults (overridable via env)
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

# Composer binary for dev-installs inside container
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Run as non-root (www-data already exists in php image)
USER www-data

EXPOSE 9000
CMD ["php-fpm"]
