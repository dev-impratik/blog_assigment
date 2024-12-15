FROM php:8.3.0-apache
WORKDIR /var/www/html

# Enable Apache mod_rewrite
RUN a2enmod rewrite

RUN apt-get update -y && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libmcrypt-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libzip-dev \
    nodejs \
    npm \
    mariadb-client \
    libcurl4-openssl-dev

# Install Composer    
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        gettext \
        intl \
        pdo_mysql \
        gd \
        bcmath \
        exif \
        pcntl \
        zip \
        curl

# Copy project files
COPY . /var/www/html

# Update Apache configuration
# RUN sed -i 's|/var/www/html|/var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Run Composer install to set up dependencies
# RUN composer install --no-dev --prefer-dist

# Copy Apache configuration
COPY ./docker/apache/laravel.conf /etc/apache2/sites-available/000-default.conf

# Permissions for Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Generate JWT secret and set up Swagger
# RUN php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider" \
#     && php artisan jwt:secret \
#     && php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" \
#     && php artisan l5-swagger:generate \
#     && php artisan key:generate \
#     && php artisan config:cache \
#     && php artisan route:cache \
#     && php artisan view:cache 


# Permissions for Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache



# Add entrypoint script
# COPY ./docker-entrypoint.sh /usr/local/bin/
# RUN chmod +x /usr/local/bin/docker-entrypoint.sh
# ENTRYPOINT ["docker-entrypoint.sh"]


# Expose port 80
EXPOSE 80

