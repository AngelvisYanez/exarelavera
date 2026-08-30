FROM php:8.2-apache

# Install dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli pdo pdo_mysql opcache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Use production PHP config (NOT development)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Production PHP settings
RUN echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT" >> $PHP_INI_DIR/conf.d/custom-php.ini \
    && echo "display_errors = Off" >> $PHP_INI_DIR/conf.d/custom-php.ini \
    && echo "display_startup_errors = Off" >> $PHP_INI_DIR/conf.d/custom-php.ini \
    && echo "log_errors = On" >> $PHP_INI_DIR/conf.d/custom-php.ini \
    && echo "error_log = /var/log/php_errors.log" >> $PHP_INI_DIR/conf.d/custom-php.ini \
    && echo "session.cookie_httponly = 1" >> $PHP_INI_DIR/conf.d/custom-php.ini \
    && echo "session.cookie_samesite = Lax" >> $PHP_INI_DIR/conf.d/custom-php.ini \
    && echo "session.use_strict_mode = 1" >> $PHP_INI_DIR/conf.d/custom-php.ini \
    && echo "expose_php = Off" >> $PHP_INI_DIR/conf.d/custom-php.ini \
    && echo "allow_url_fopen = On" >> $PHP_INI_DIR/conf.d/custom-php.ini \
    && echo "upload_max_filesize = 20M" >> $PHP_INI_DIR/conf.d/custom-php.ini \
    && echo "post_max_size = 25M" >> $PHP_INI_DIR/conf.d/custom-php.ini \
    && echo "memory_limit = 256M" >> $PHP_INI_DIR/conf.d/custom-php.ini \
    && echo "max_execution_time = 60" >> $PHP_INI_DIR/conf.d/custom-php.ini

# OPcache for production performance
RUN echo "opcache.enable=1" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> $PHP_INI_DIR/conf.d/opcache.ini

WORKDIR /var/www/html
