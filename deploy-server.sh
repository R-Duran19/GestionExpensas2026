#!/bin/bash

# Script de despliegue limpio para GestionExpensas2026
# Este script asegura que todas las configuraciones estén correctas

set -e  # Detener en caso de error

echo "=== Iniciando despliegue de GestionExpensas2026 ==="

# 1. Limpiar contenedores y volúmenes existentes
echo "🧹 Limpiando contenedores y volúmenes anteriores..."
docker compose down -v
docker volume rm gestion_expensas_mysql_data 2>/dev/null || true

# 2. Crear el archivo .env con credenciales seguras
echo "📝 Creando archivo .env..."
cat > .env << 'EOF'
# Configuración de la aplicación
APP_NAME="Gestión de Expensas - Las Palmeras"
APP_ENV=production
APP_KEY=base64:uzDf3IFmH2POVCN5SY3rc+dM8FD9HjJ6bT2JFhvkx04=
APP_DEBUG=false
APP_URL=https://expensas.acmanalytics.online

# Configuración de idioma
APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=es_BO

# Configuración de base de datos para Docker
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=expensas_db
DB_USERNAME=AdminUserExpe2026
DB_PASSWORD=AdminExpe8119

# Configuración adicional de MySQL (para docker-compose)
DB_ROOT_PASSWORD=AdminExpe8119

# Configuración de sesión
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# Configuración de caché y colas
CACHE_STORE=database
QUEUE_CONNECTION=database

# Configuración de logs
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

# Configuración de correo
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="noreply@expensas.com"
MAIL_FROM_NAME="${APP_NAME}"

# Configuración de Vite
VITE_APP_NAME="${APP_NAME}"
EOF

# 3. Levantar los contenedores
echo "🚀 Levantando contenedores Docker..."
docker compose up -d

# 4. Esperar a que MySQL esté listo
echo "⏳ Esperando a que MySQL esté listo..."
sleep 15

# 5. Verificar que los contenedores estén corriendo
echo "✅ Verificando contenedores..."
docker compose ps

# 6. Limpiar configuración de Laravel
echo "🧹 Limpiando caché de configuración..."
docker compose exec -T app php artisan config:clear
docker compose exec -T app php artisan cache:clear
docker compose exec -T app php artisan route:clear
docker compose exec -T app php artisan view:clear

# 7. Ejecutar migraciones
echo "🗄️ Ejecutando migraciones..."
docker compose exec -T app php artisan migrate --force

# 8. Verificar la conexión a la base de datos
echo "🔍 Verificando conexión a la base de datos..."
docker compose exec -T app php artisan db:show

# 9. Optimizar la aplicación para producción
echo "⚡ Optimizando aplicación..."
docker compose exec -T app composer install --no-dev --optimize-autoloader
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

echo ""
echo "=== ✅ Despliegue completado exitosamente ==="
echo ""
echo "🌐 La aplicación debería estar accesible en: https://expensas.acmanalytics.online"
echo ""
echo "📊 Comandos útiles:"
echo "  Ver logs: docker compose logs -f"
echo "  Ver logs de MySQL: docker compose logs -f mysql"
echo "  Ver logs de la app: docker compose logs -f app"
echo "  Reiniciar: docker compose restart"
echo "  Detener: docker compose down"
echo ""
