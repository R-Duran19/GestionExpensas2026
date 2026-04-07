#!/bin/bash

# Script de entrada para el contenedor Laravel
# Este script se ejecuta cada vez que se inicia el contenedor

set -e

echo "🚀 Iniciando contenedor Laravel..."

# Esperar a que MySQL esté disponible
echo "⏳ Esperando a que MySQL esté disponible..."
until mysql -hmysql -uexpensas_user -psecret_password -e "SELECT 1" > /dev/null 2>&1; do
    echo "MySQL no está disponible aún - esperando..."
    sleep 3
done

echo "✅ MySQL está disponible!"

# Ejecutar migraciones si están configuradas
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "🔄 Ejecutando migraciones..."
    php artisan migrate --force
    echo "✅ Migraciones completadas"
fi

# Ejecutar seeders si están configurados
if [ "$RUN_SEEDERS" = "true" ]; then
    echo "🔄 Ejecutando seeders..."
    php artisan db:seed --force
    echo "✅ Seeders completados"
fi

# Crear storage link si no existe
if [ ! -L "public/storage" ]; then
    echo "🔗 Creando storage link..."
    php artisan storage:link
    echo "✅ Storage link creado"
fi

# Limpiar caché
echo "🧹 Limpiando caché..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimizar para producción
if [ "$APP_ENV" = "production" ]; then
    echo "⚡ Optimizando para producción..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    echo "✅ Optimización completada"
fi

echo "🎉 Contenedor listo para recibir peticiones!"

# Ejecutar el comando principal
exec "$@"