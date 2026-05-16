# Multi-stage build for Laravel
FROM php:8.1-fpm as builder

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    git \
    curl \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
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
RUN composer install --no-dev --no-interaction --prefer-dist

# Copy application files
COPY . .

# Generate APP_KEY
RUN php artisan key:generate || true

# Production stage
FROM php:8.1-fpm

# Install runtime dependencies
RUN apt-get update && apt-get install -y \
    libpng6 \
    libjpeg62-turbo \
    libfreetype6 \
    libonig5 \
    libxml2 \
    nginx \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (runtime only)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
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

# Copy nginx configuration
COPY nginx.conf /etc/nginx/sites-available/default

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
