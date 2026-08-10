# =============================================================================
# Dockerfile — Gestión de Expensas (Laravel 12 + PHP 8.4 + Nginx + Supervisor)
# =============================================================================
FROM php:8.4-fpm

# -----------------------------------------------------------------------------
# 1. Dependencias del sistema + extensiones PHP + Node.js + cliente MySQL
# -----------------------------------------------------------------------------
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Node.js 22 (para build de assets con Vite)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP requeridas por Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# -----------------------------------------------------------------------------
# 2. Código de la aplicación
# -----------------------------------------------------------------------------
WORKDIR /var/www/html
COPY . .

# -----------------------------------------------------------------------------
# 3. Dependencias PHP y build de assets front (Vite)
# -----------------------------------------------------------------------------
RUN composer install \
        --no-interaction \
        --no-dev \
        --optimize-autoloader \
        --no-progress

RUN npm install --no-audit --no-fund \
    && npm run build \
    && npm cache clean --force

# -----------------------------------------------------------------------------
# 4. Permisos y directorios necesarios
# -----------------------------------------------------------------------------
RUN mkdir -p \
        bootstrap/cache \
        storage/framework/cache \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        storage/app/public \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# -----------------------------------------------------------------------------
# 5. Configuración de nginx, supervisor y entrypoint
# -----------------------------------------------------------------------------
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# -----------------------------------------------------------------------------
# 6. Puertos y arranque
# -----------------------------------------------------------------------------
EXPOSE 80 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
