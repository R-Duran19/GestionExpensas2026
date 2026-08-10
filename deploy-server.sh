#!/bin/bash
# =============================================================================
# Script de despliegue — Gestión de Expensas (VPS Contabo)
# Uso:  ./deploy-server.sh
# =============================================================================
set -e

echo "================================================================"
echo "🚀 Despliegue de GestionExpensas2026"
echo "================================================================"

# 1. Verificar red externa del Nginx Proxy Manager
if ! docker network inspect proxy_network >/dev/null 2>&1; then
    echo "❌ La red 'proxy_network' NO existe en el host."
    echo "   Créala con:  docker network create proxy_network"
    exit 1
fi

# 2. Build + up (sin borrar volúmenes — preserva la BD)
echo "🔨 Construyendo imagen y levantando contenedores..."
docker compose up -d --build

# 3. Esperar a que el contenedor app termine su entrypoint
echo "⏳ Esperando a que la app esté lista (migraciones + seeders)..."
timeout 180 bash -c 'until docker compose logs app 2>&1 | grep -q "Contenedor listo"; do sleep 3; done' \
    || echo "⚠️  Timeout esperando a la app. Revisa: docker compose logs app"

# 4. Estado
echo ""
echo "📊 Estado de contenedores:"
docker compose ps

echo ""
echo "================================================================"
echo "✅ Deploy completado"
echo "================================================================"
echo "🌐 URL: https://expensas.acmanalytics.online"
echo ""
echo "Comandos útiles:"
echo "  Logs en vivo:    docker compose logs -f app"
echo "  Reiniciar:       docker compose restart app"
echo "  Detener:         docker compose down"
echo "  Re-deploy:       git pull && docker compose up -d --build"
echo ""
