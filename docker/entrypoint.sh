#!/bin/bash
# Entrypoint para el contenedor Laravel (Gestión de Expensas)
# Se ejecuta en cada arranque. Es idempotente.

set -e

echo "================================================================"
echo "🚀 Iniciando contenedor Laravel - Gestión de Expensas"
echo "================================================================"

# -----------------------------------------------------------------------------
# 0a. Asegurar estructura de storage (bind mount puede traerla vacía)
# -----------------------------------------------------------------------------
mkdir -p \
    /var/www/html/storage/app/public \
    /var/www/html/storage/app/private \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# -----------------------------------------------------------------------------
# 0. Asegurar .env (generar desde variables de entorno del docker-compose)
# -----------------------------------------------------------------------------
# Las credenciales NUNCA están en este archivo. Llegan como env vars del host.
if [ ! -f /var/www/html/.env ]; then
    echo "📄 Generando .env desde variables de entorno..."
    cat > /var/www/html/.env <<EOF
APP_NAME="${APP_NAME:-Gestión de Expensas}"
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY:-}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-https://expensas.acmanalytics.online}
APP_LOCALE=${APP_LOCALE:-es}
APP_FALLBACK_LOCALE=${APP_FALLBACK_LOCALE:-en}
APP_FAKER_LOCALE=${APP_FAKER_LOCALE:-es_BO}

LOG_CHANNEL=${LOG_CHANNEL:-stack}
LOG_STACK=${LOG_STACK:-single}
LOG_LEVEL=${LOG_LEVEL:-warning}

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-mysql}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

SESSION_DRIVER=${SESSION_DRIVER:-database}
SESSION_LIFETIME=${SESSION_LIFETIME:-120}
SESSION_ENCRYPT=${SESSION_ENCRYPT:-false}
SESSION_PATH=${SESSION_PATH:-/}
SESSION_DOMAIN=${SESSION_DOMAIN:-expensas.acmanalytics.online}
SESSION_SECURE_COOKIE=${SESSION_SECURE_COOKIE:-true}

CACHE_STORE=${CACHE_STORE:-database}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-database}

MAIL_MAILER=${MAIL_MAILER:-log}
MAIL_FROM_ADDRESS="${MAIL_FROM_ADDRESS:-noreply@expensas.com}"
MAIL_FROM_NAME="${MAIL_FROM_NAME:-\${APP_NAME}}"

VITE_APP_NAME="${VITE_APP_NAME:-\${APP_NAME}}"
ASSET_URL=${ASSET_URL:-${APP_URL:-https://expensas.acmanalytics.online}}
TRUSTED_PROXIES=${TRUSTED_PROXIES:-*}
EOF
    echo "✅ .env generado"
fi

# -----------------------------------------------------------------------------
# 1. Validar que las variables críticas existan (fail-fast)
# -----------------------------------------------------------------------------
if [ -z "${DB_DATABASE}" ] || [ -z "${DB_USERNAME}" ] || [ -z "${DB_PASSWORD}" ]; then
    echo "❌ Faltan variables obligatorias: DB_DATABASE, DB_USERNAME o DB_PASSWORD."
    echo "   Copia .env.example a .env en el host y rellénalas."
    exit 1
fi

# -----------------------------------------------------------------------------
# 2. Esperar a MySQL (usa credenciales inyectadas por docker-compose)
# -----------------------------------------------------------------------------
echo "⏳ Esperando a MySQL en ${DB_HOST:-mysql}:${DB_PORT:-3306} (usuario: ${DB_USERNAME})..."
ATTEMPTS=0
MAX_ATTEMPTS=60
until mysqladmin ping \
        -h"${DB_HOST:-mysql}" \
        -P"${DB_PORT:-3306}" \
        -u"${DB_USERNAME}" \
        -p"${DB_PASSWORD}" \
        --ssl-mode=DISABLED \
        --silent 2>/dev/null; do
    ATTEMPTS=$((ATTEMPTS+1))
    if [ ${ATTEMPTS} -ge ${MAX_ATTEMPTS} ]; then
        echo "❌ Timeout esperando MySQL tras ${MAX_ATTEMPTS} intentos."
        echo "   ¿Está el contenedor 'mysql' levantado y en la misma red?"
        exit 1
    fi
    echo "   MySQL no responde aún (intento ${ATTEMPTS}/${MAX_ATTEMPTS})..."
    sleep 3
done
echo "✅ MySQL disponible"

# -----------------------------------------------------------------------------
# 2. APP_KEY (generar si no existe)
# -----------------------------------------------------------------------------
if ! grep -q "^APP_KEY=base64:" /var/www/html/.env 2>/dev/null; then
    echo "🔑 Generando APP_KEY..."
    php artisan key:generate --force
fi

# -----------------------------------------------------------------------------
# 3. Storage link
# -----------------------------------------------------------------------------
if [ ! -L /var/www/html/public/storage ]; then
    echo "🔗 Creando storage:link..."
    php artisan storage:link || true
fi

# -----------------------------------------------------------------------------
# 4. Migraciones (default: sí en producción)
# -----------------------------------------------------------------------------
RUN_MIGRATIONS="${RUN_MIGRATIONS:-true}"
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "🔄 Ejecutando migraciones..."
    php artisan migrate --force
    echo "✅ Migraciones OK"
else
    echo "⏭️  RUN_MIGRATIONS=false — saltando migraciones"
fi

# -----------------------------------------------------------------------------
# 5. Seeders (SOLO si la tabla users está vacía — evita duplicados en redeploys)
# -----------------------------------------------------------------------------
RUN_SEEDERS="${RUN_SEEDERS:-true}"
if [ "${RUN_SEEDERS}" = "true" ]; then
    USERS_COUNT=$(php artisan tinker --execute="echo App\\Models\\User::count();" 2>/dev/null || echo "-1")
    if [ "${USERS_COUNT}" = "0" ]; then
        echo "🌱 Base de datos vacía — ejecutando seeders..."
        php artisan db:seed --force
        echo "✅ Seeders OK"
    elif [ "${USERS_COUNT}" = "-1" ]; then
        echo "⚠️  No se pudo verificar el estado de la BD — saltando seeders por seguridad"
    else
        echo "⏭️  La BD ya tiene ${USERS_COUNT} usuarios — saltando seeders (idempotente)"
    fi
else
    echo "⏭️  RUN_SEEDERS=false — saltando seeders"
fi

# -----------------------------------------------------------------------------
# 6. Optimización para producción
# -----------------------------------------------------------------------------
if [ "${APP_ENV}" = "production" ]; then
    echo "⚡ Optimizando para producción..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache 2>/dev/null || true
    echo "✅ Cachés generadas"
else
    echo "🧹 Limpiando cachés (modo desarrollo)..."
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
fi

echo "================================================================"
echo "🎉 Contenedor listo — arrancando supervisor (nginx + php-fpm)"
echo "================================================================"

exec "$@"
