FROM php:8.2-fpm-alpine

# Set working directory
WORKDIR /app

# Install system dependencies
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    npm \
    nodejs \
    supervisor

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    bcmath \
    ctype \
    fileinfo \
    json \
    tokenizer \
    xml \
    curl \
    gd

# Install Redis PHP extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js dependencies and build frontend
COPY package*.json ./
RUN npm ci && npm run build

# Copy application code
COPY . .

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Create necessary directories
RUN mkdir -p storage/logs \
    && mkdir -p storage/app/public/qr_codes \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data /app

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Copy entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expose port
EXPOSE 9000

# Health check
HEALTHCHECK --interval=10s --timeout=5s --retries=5 \
    CMD curl -f http://localhost:9000/health || exit 1

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
