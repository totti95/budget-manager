# ============================================
# Stage 1: Builder - Compile PHP extensions
# ============================================
FROM php:8.3-fpm-alpine AS builder

# Install build dependencies
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    libxml2-dev

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        dom \
        xml \
        opcache

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# ============================================
# Stage 2: Runtime - Production image
# ============================================
FROM php:8.3-fpm-alpine AS runtime

# Install only runtime dependencies (no build tools)
RUN apk add --no-cache \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    libxml2 \
    curl

# Copy compiled extensions from builder
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

# Install Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Configure PHP-FPM to run worker processes as www-data (already configured by default)
# The master process runs as root, but workers run as www-data for security

EXPOSE 9000

CMD ["php-fpm"]
