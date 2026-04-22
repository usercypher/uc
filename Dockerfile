FROM php:8.3-fpm-alpine

RUN set -e && \
    apk add --no-cache --virtual .build-deps \
    lighttpd sqlite sqlite-dev pkgconf gcc libc-dev make && \

    # --- START: PHP EXTENSIONS ---
    docker-php-ext-install pdo pdo_sqlite && \
    # --- END:   PHP EXTENSIONS ---

    apk del .build-deps

WORKDIR /var/www

COPY ./ .

RUN mkdir -p /var/www/var && \
    chown -R www-data:www-data /var/www && \
    chmod -R 755 /var/www && \
    chmod -R 775 /var/www/var && \
    chmod -R 775 /var/www/web && \
    chmod +x boot.sh

EXPOSE 8080

USER www-data

CMD ["./boot.sh"]