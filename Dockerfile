FROM wordpress:php8.2-apache

RUN cp -r /usr/src/wordpress/. /var/www/html/

RUN rm -f /var/www/html/wp-config.php

COPY app/public/wp-content/plugins/thetruth-settings /var/www/html/wp-content/plugins/thetruth-settings
COPY app/public/wp-content/themes/twentytwentyfive /var/www/html/wp-content/themes/twentytwentyfive

RUN a2enmod rewrite \
    && echo "upload_max_filesize = 64M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

RUN mkdir -p /var/www/html/wp-content/uploads \
    && chown -R www-data:www-data /var/www/html/wp-content
