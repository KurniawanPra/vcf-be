# Multi-stage build for Laravel
FROM php:8.1-fpm-alpine as builder

# Install build dependencies
RUN apk add --no-cache \
    build-base \
    autoconf \
    pkgconfig \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    git \
    curl \
    unzip

# Install PHP extensions
RUN docker-php-ext-configure gd \
        --with-freetype=/usr/include \
        --with-jpeg=/usr/include \
    && docker-php-ext-install -j$(nproc) \
        gd \
        bcmath \
        ctype \
        fileinfo \
        json \
        mbstring \
        openssl \
        pdo_mysql \
        tokenizer \
        xml

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Copy application files
COPY . .

# Generate APP_KEY
RUN php artisan key:generate || true

# Production stage
FROM php:8.1-fpm-alpine

# Install runtime dependencies
RUN apk add --no-cache \
    libpng \
    libjpeg-turbo \
    freetype \
    oniguruma \
    libxml2 \
    nginx \
    curl \
    netcat-openbsd \
    bash \
    dcron

# Install runtime PHP extensions
RUN apk add --no-cache --virtual .php-deps \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libxml2-dev \
        oniguruma-dev \
    && docker-php-ext-configure gd \
        --with-freetype=/usr/include \
        --with-jpeg=/usr/include \
    && docker-php-ext-install -j$(nproc) \
        gd \
        bcmath \
        ctype \
        fileinfo \
        json \
        mbstring \
        openssl \
        pdo_mysql \
        tokenizer \
        xml \
    && apk del .php-deps

# Set working directory
WORKDIR /app

# Copy application from builder
COPY --from=builder /app /app

# Create necessary directories
RUN mkdir -p /app/storage/logs \
    && mkdir -p /app/storage/framework/sessions \
    && mkdir -p /app/storage/framework/views \
    && mkdir -p /app/storage/framework/cache \
    && mkdir -p /app/bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Create nginx config directory if not exists
RUN mkdir -p /etc/nginx/http.d

# Copy nginx configuration
COPY nginx.conf /etc/nginx/http.d/default.conf

# Copy startup script
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Expose port
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/api/health || exit 1

# Start services
CMD ["/start.sh"]
