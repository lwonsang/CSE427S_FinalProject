FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

RUN a2enmod rewrite

COPY src/ /var/www/html

RUN chown -R www-data:www-data /var/www/html/ && \
    chmod -R 755 /var/www/html/

# Install dependencies for AWS SDK and Composer
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    git \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/custom-perms.conf && \
    a2enconf custom-perms
    
WORKDIR /var/www/html

# Copy your PHP files and vendor directory
COPY . /var/www/html

# Run Composer install if vendor is not included
RUN composer install --no-dev --optimize-autoloader

EXPOSE 80