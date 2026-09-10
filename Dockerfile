FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libicu-dev libonig-dev libpq-dev \
        libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev default-libmysqlclient-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" bcmath exif gd intl opcache pdo_mysql pdo_pgsql zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_MEMORY_LIMIT=-1
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN printf "memory_limit=512M\nupload_max_filesize=64M\npost_max_size=64M\nmax_execution_time=600\n" > /usr/local/etc/php/conf.d/zz-assan.ini

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader \
    && php artisan vendor:publish --tag=config --tag=public --force \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache public/aimeos \
    && chown -R www-data:www-data storage bootstrap/cache public/aimeos

# The admin bundle's CKEditor plugin list references an undeclared "Markdown"
# identifier, which aborts the whole global registration (map/chart widgets
# never load). The feature is disabled (markdown:false) anyway, so drop it.
RUN f=$(find vendor/aimeos/ai-admin-jqadm -name vendor.js -type f | head -1) \
    && test -n "$f" \
    && sed -i 's/TH,Markdown,LH/TH,LH/' "$f" \
    && ! grep -q 'TH,Markdown,LH' "$f" \
    && echo "Admin vendor.js patched: $f"

EXPOSE 80

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]