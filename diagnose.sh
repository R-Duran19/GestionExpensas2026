#!/bin/bash

# Script de diagnóstico para GestionExpensas2026
# Ayuda a identificar problemas de configuración y conexión

echo "=== 🔍 DIAGNÓSTICO DE GESTION EXPENSAS 2026 ==="
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para mostrar checks
check_pass() {
    echo -e "${GREEN}✅ $1${NC}"
}

check_fail() {
    echo -e "${RED}❌ $1${NC}"
}

check_warn() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# 1. Verificar archivos de configuración
echo "📁 ARCHIVOS DE CONFIGURACIÓN"
echo "----------------------------"

if [ -f ".env" ]; then
    check_pass "Archivo .env existe"
else
    check_fail "Archivo .env NO existe - Copia .env.production a .env"
fi

if [ -f "docker-compose.yml" ]; then
    check_pass "Archivo docker-compose.yml existe"
else
    check_fail "Archivo docker-compose.yml NO existe"
fi

echo ""

# 2. Verificar variables de entorno críticas
echo "🔐 VARIABLES DE ENTORNO"
echo "----------------------"

if [ -f ".env" ]; then
    DB_USER=$(grep '^DB_USERNAME=' .env | cut -d '=' -f2)
    DB_PASS=$(grep '^DB_PASSWORD=' .env | cut -d '=' -f2)
    # [REMEDIACIÓN-0.2] Canario de filtración: hash SHA-256 de la contraseña que
    # quedó expuesta en el historial git. Permite detectar que NO se rotó sin
    # volver a almacenar el secreto en texto plano en este repositorio.
    PASSWORD_FILTRADA_HISTORIAL_SHA256="2d9cc37a99899a5f9bdb78834f52e9c75ca8dc375443a38e8a0e9e8ce402d91e"

    if [ -n "$DB_USER" ]; then
        check_pass "DB_USERNAME configurado: $DB_USER"
    else
        check_fail "DB_USERNAME sin configurar en .env"
    fi

    if [ -z "$DB_PASS" ]; then
        check_fail "DB_PASSWORD sin configurar en .env"
    elif [ "$(printf '%s' "$DB_PASS" | sha256sum | cut -d ' ' -f1)" = "$PASSWORD_FILTRADA_HISTORIAL_SHA256" ]; then
        check_fail "DB_PASSWORD usa la contraseña FILTRADA en el historial git. ROTALA ya (ver audit/run-1/REMEDIATION-PLAN.md fase 0.2)"
    else
        check_pass "DB_PASSWORD configurada (no coincide con la filtrada históricamente)"
    fi
else
    check_fail "No se pueden verificar variables - .env no existe"
fi

echo ""

# 3. Verificar Docker
echo "🐳 ESTADO DE DOCKER"
echo "------------------"

if command -v docker &> /dev/null; then
    check_pass "Docker está instalado"
else
    check_fail "Docker NO está instalado"
    exit 1
fi

if command -v docker-compose &> /dev/null || docker compose version &> /dev/null; then
    check_pass "Docker Compose está instalado"
else
    check_fail "Docker Compose NO está instalado"
    exit 1
fi

echo ""

# 4. Verificar contenedores
echo "📦 CONTENEDORES"
echo "--------------"

if docker compose ps &> /dev/null; then
    check_pass "Docker Compose puede leer configuración"

    # Verificar contenedor app
    if docker compose ps --services | grep -q "app"; then
        APP_STATUS=$(docker compose ps app | grep app | awk '{print $7}')
        if [ "$APP_STATUS" = "Up" ]; then
            check_pass "Contenedor 'app' está corriendo"
        else
            check_fail "Contenedor 'app' NO está corriendo (estado: $APP_STATUS)"
        fi
    else
        check_fail "Contenedor 'app' no existe"
    fi

    # Verificar contenedor mysql
    if docker compose ps --services | grep -q "mysql"; then
        MYSQL_STATUS=$(docker compose ps mysql | grep mysql | awk '{print $7}')
        if [ "$MYSQL_STATUS" = "Up" ]; then
            check_pass "Contenedor 'mysql' está corriendo"
        else
            check_fail "Contenedor 'mysql' NO está corriendo (estado: $MYSQL_STATUS)"
        fi
    else
        check_fail "Contenedor 'mysql' no existe"
    fi
else
    check_fail "No se pueden verificar contenedores - docker compose falla"
fi

echo ""

# 5. Verificar redes Docker
echo "🌐 REDES DOCKER"
echo "--------------"

if docker network ls | grep -q "gestion_expensas_network"; then
    check_pass "Red gestion_expensas_network existe"
else
    check_fail "Red gestion_expensas_network NO existe"
fi

if docker network ls | grep -q "geopricer_365soft_app-network"; then
    check_pass "Red externa nginx-proxy-manager existe"
else
    check_warn "Red externa nginx-proxy-manager NO existe (opcional)"
fi

echo ""

# 6. Verificar volúmenes
echo "💾 VOLÚMENES"
echo "-----------"

if docker volume ls | grep -q "gestion_expensas_mysql_data"; then
    check_pass "Volumen MySQL existe"
else
    check_warn "Volumen MySQL NO existe (se creará al iniciar)"
fi

echo ""

# 7. Probar conexión a MySQL
echo "🔗 CONEXIÓN A MYSQL"
echo "-----------------"

# [REMEDIACIÓN-0.2] Credenciales leídas del .env del host (nunca hardcodeadas)
DB_USER=$(grep '^DB_USERNAME=' .env 2>/dev/null | cut -d '=' -f2)
DB_PASS=$(grep '^DB_PASSWORD=' .env 2>/dev/null | cut -d '=' -f2)
DB_ROOT_PASS=$(grep '^DB_ROOT_PASSWORD=' .env 2>/dev/null | cut -d '=' -f2)

if docker compose ps mysql | grep -q "Up"; then
    if [ -n "$DB_USER" ] && docker compose exec -T mysql mysql -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" &> /dev/null; then
        check_pass "Conexión a MySQL exitosa con credenciales de .env ($DB_USER)"
    else
        check_fail "Conexión a MySQL FALLÓ con credenciales de .env"
        echo "   Posible causa: Usuario no configurado correctamente en MySQL"
    fi

    if [ -n "$DB_ROOT_PASS" ] && docker compose exec -T mysql mysql -u root -p"$DB_ROOT_PASS" -e "SELECT 1" &> /dev/null; then
        check_pass "Conexión a MySQL exitosa con credenciales root (de .env)"
    else
        check_fail "Conexión a MySQL FALLÓ con credenciales root (revisa DB_ROOT_PASSWORD en .env)"
    fi
else
    check_warn "No se puede probar conexión - MySQL no está corriendo"
fi

echo ""

# 8. Verificar logs recientes
echo "📋 ÚLTIMOS ERRORES EN LOGS"
echo "------------------------"

echo "Errores recientes en MySQL:"
docker compose logs mysql | grep -i error | tail -5 || echo "   No hay errores recientes"

echo ""
echo "Errores recientes en App:"
docker compose logs app | grep -i error | tail -5 || echo "   No hay errores recientes"

echo ""

# 9. Recomendaciones
echo "💡 RECOMENDACIONES"
echo "----------------"

if [ ! -f ".env" ]; then
    echo "1. Ejecuta: cp .env.production .env"
fi

if ! docker compose ps app | grep -q "Up"; then
    echo "2. Ejecuta: docker compose up -d"
fi

if [ -n "$DB_USER" ] && ! docker compose exec -T mysql mysql -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" &> /dev/null; then
    echo "3. Ejecuta: docker compose down -v && docker compose up -d"
    echo "   Esto reiniciará MySQL con la configuración correcta"
fi

echo ""
echo "=== ✅ DIAGNÓSTICO COMPLETADO ==="
echo ""
echo "Si todos los checks están en verde, ejecuta:"
echo "  docker compose exec app php artisan migrate --force"
echo ""
echo "Para ver logs en tiempo real:"
echo "  docker compose logs -f"
echo ""