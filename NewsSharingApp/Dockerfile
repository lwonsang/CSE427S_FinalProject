FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

RUN a2enmod rewrite

COPY src/ /var/www/html

RUN chown -R www-data:www-data /var/www/html/ && \
    chmod -R 755 /var/www/html/

RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/custom-perms.conf && \
    a2enconf custom-perms
    
WORKDIR /var/www/html

EXPOSE 80