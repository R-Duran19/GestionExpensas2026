# =============================================================================
# Dockerfile — Gestión de Expensas (Laravel 12 + PHP 8.4 + Nginx + Supervisor)
# =============================================================================
# MULTI-STAGE: la etapa "builder" compila (Node, extensiones PHP, Vite) y la
# etapa final SOLO copia el resultado → imagen ~2.1GB → ~700MB, sin Node ni
# compiladores en producción (menos disco, menos superficie de ataque).
# =============================================================================

# -----------------------------------------------------------------------------
# ETAPA 1 — builder: dependencias, extensiones PHP y assets del front
# -----------------------------------------------------------------------------
FROM php:8.4-fpm AS builder

# Dependencias de compilación (dev): SOLO viven en esta etapa
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    && rm -rf /var/lib/apt/lists/*

# Node.js 22 (build de assets con Vite) — descartado en la etapa final
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP requeridas por Laravel (compiladas acá, copiadas al final)
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

# ImageMagick para procesamiento de imágenes más rápido (Intervention Image v4)
# GD es 3-10× más lento que Imagick encodeando WebP
RUN apt-get update && apt-get install -y --no-install-recommends \
        libmagickwand-dev \
        libmagickcore-dev \
        libwebp-dev \
        imagemagick \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install \
        --no-interaction \
        --no-dev \
        --optimize-autoloader \
        --no-progress

RUN npm install --no-audit --no-fund \
    && npm run build \
    && npm cache clean --force \
    # node_modules ya cumplió: si no se borra, viajaría a la etapa final
    && rm -rf node_modules

# -----------------------------------------------------------------------------
# ETAPA 2 — production: solo runtime (sin Node, sin -dev, sin compiladores)
# -----------------------------------------------------------------------------
FROM php:8.4-fpm AS production

# Runtime del sistema: nginx + supervisor (procesos), mysql-client (el
# entrypoint usa mysqladmin para esperar a la BD) y las librerías NO-dev que
# enlazan las extensiones compiladas en builder.
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        curl \
        ca-certificates \
        default-mysql-client \
        imagemagick \
        libmagickwand-7.q16-10 \
        libpng16-16t64 \
        libjpeg62-turbo \
        libfreetype6 \
        libwebp7 \
        libonig5 \
        libxml2 \
        libzip5 \
    && rm -rf /var/lib/apt/lists/*

# Extensiones ya compiladas en builder: los .so + sus ini (docker-php-ext-*)
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/etc/php/conf.d/docker-php-ext-*.ini /usr/local/etc/php/conf.d/

# Código + vendor + assets compilados (node_modules se eliminó en builder)
WORKDIR /var/www/html
COPY --from=builder /var/www/html /var/www/html

# -----------------------------------------------------------------------------
# Permisos y directorios necesarios
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
# Configuración de nginx, supervisor y entrypoint
# -----------------------------------------------------------------------------
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
# Límites PHP para uploads (imágenes/PDFs). Se carga automáticamente por php-fpm.
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/zz-uploads.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
# Pool PHP-FPM dimensionado para VPS 4GB (Plan 12 F0.3). Se carga después de
# www.conf y zz-docker.conf (orden alfabético) — estos valores son los que mandan.
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-expensas.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# -----------------------------------------------------------------------------
# Puertos y arranque
# -----------------------------------------------------------------------------
EXPOSE 80 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
