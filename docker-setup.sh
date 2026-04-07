#!/bin/bash

# Script de configuración inicial para el contenedor
# Ejecutar esto DENTRO del contenedor después del primer up

echo "🚀 Configurando aplicación Laravel..."

# Instalar dependencias de PHP
echo "📦 Instalando dependencias de PHP..."
composer install --no-interaction --optimize-autoloader --no-dev

# Instalar dependencias de Node y construir assets
echo "📦 Instalando dependencias de Node..."
npm install
npm run build

# Crear storage link
echo "🔗 Creando storage link..."
php artisan storage:link

# Limpiar caché
echo "🧹 Limpiando caché..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimizar para producción
echo "⚡ Optimizando para producción..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Configuración completada!"
echo "🎉 La aplicación está lista!"