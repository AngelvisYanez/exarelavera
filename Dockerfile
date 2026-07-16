FROM php:8.1-apache

# Install dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure PHP settings
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Set custom error reporting from environment variables
RUN echo "error_reporting = \${PHP_ERROR_REPORTING}" >> $PHP_INI_DIR/conf.d/custom-php.ini \
    && echo "display_errors = \${PHP_DISPLAY_ERRORS}" >> $PHP_INI_DIR/conf.d/custom-php.ini
