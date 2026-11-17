FROM php:8.2-apache

# PHP extensions used by MediCore: mysqli/PDO (database), gd (report images),
# curl + mbstring ship with the base image.
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libfreetype6-dev ca-certificates \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install -j"$(nproc)" mysqli pdo_mysql gd \
 && a2enmod rewrite headers \
 && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
 && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html \
 && chmod +x /var/www/html/deploy/entrypoint.sh

ENV PORT=10000
EXPOSE 10000
CMD ["/var/www/html/deploy/entrypoint.sh"]
