#!/bin/bash

# Script de Deployment para GestionExpensas2026
# Uso: ./deploy.sh [comando]

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funciones de ayuda
print_header() {
    echo -e "${BLUE}================================================${NC}"
    echo -e "${BLUE} $1${NC}"
    echo -e "${BLUE}================================================${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Verificar que estamos en el directorio correcto
check_directory() {
    if [ ! -f "artisan" ]; then
        print_error "Este script debe ejecutarse desde el directorio raíz del proyecto Laravel"
        exit 1
    fi
}

# Verificar que Docker está instalado
check_docker() {
    if ! command -v docker &> /dev/null; then
        print_error "Docker no está instalado"
        exit 1
    fi

    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose no está instalado"
        exit 1
    fi
}

# Verificar que la red existe
check_network() {
    print_info "Verificando red Docker..."
    if docker network inspect geopricer_365soft_app-network &> /dev/null; then
        print_success "Red 'geopricer_365soft_app-network' encontrada"
    else
        print_error "La red 'geopricer_365soft_app-network' no existe"
        print_info "Creando red..."
        docker network create geopricer_365soft_app-network
        print_success "Red creada exitosamente"
    fi
}

# Crear archivo .env.docker si no existe
setup_env() {
    if [ ! -f ".env.docker" ]; then
        print_warning "Archivo .env.docker no encontrado"
        print_info "Creando desde .env.docker.example..."
        cp .env.docker.example .env.docker
        print_warning "⚠️  Por favor edita .env.docker con tus configuraciones"
        print_warning "Especialmente las contraseñas de base de datos"
        read -p "Presiona ENTER para continuar cuando hayas terminado de editar .env.docker"
    else
        print_success "Archivo .env.docker encontrado"
    fi
}

# Construir imágenes
build() {
    print_header "Construyendo imágenes Docker"
    docker-compose build
    print_success "Imágenes construidas exitosamente"
}

# Levantar contenedores
up() {
    print_header "Levantando contenedores"
    docker-compose up -d
    print_success "Contenedores levantados"

    print_info "Esperando que los servicios estén listos..."
    sleep 10

    print_info "Estado de los contenedores:"
    docker-compose ps
}

# Detener contenedores
down() {
    print_header "Deteniendo contenedores"
    docker-compose down
    print_success "Contenedores detenidos"
}

# Ver logs
logs() {
    print_header "Mostrando logs (Ctrl+C para salir)"
    docker-compose logs -f
}

# Ejecutar migraciones
migrate() {
    print_header "Ejecutando migraciones"
    docker-compose exec app php artisan migrate --force
    print_success "Migraciones ejecutadas"
}

# Ejecutar seeders
seed() {
    print_header "Ejecutando seeders"
    docker-compose exec app php artisan db:seed --force
    print_success "Seeders ejecutados"
}

# Limpiar caché
clear_cache() {
    print_header "Limpiando caché de Laravel"
    docker-compose exec app php artisan config:clear
    docker-compose exec app php artisan route:clear
    docker-compose exec app php artisan view:clear
    docker-compose exec app php artisan cache:clear
    print_success "Caché limpiada"
}

# Optimizar para producción
optimize() {
    print_header "Optimizando para producción"
    docker-compose exec app php artisan config:cache
    docker-compose exec app php artisan route:cache
    docker-compose exec app php artisan view:cache
    print_success "Aplicación optimizada"
}

# Backup de base de datos
backup() {
    print_header "Creando backup de base de datos"
    BACKUP_FILE="backup_expensas_$(date +%Y%m%d_%H%M%S).sql"
    docker-compose exec mysql mysqldump -u expensas_user -p expensas_db > "$BACKUP_FILE"
    print_success "Backup creado: $BACKUP_FILE"
}

# Deployment completo
deploy() {
    print_header "🚀 Deployment Completo"

    check_directory
    check_docker
    check_network
    setup_env

    print_info "¿Deseas ejecutar migraciones? (y/n)"
    read -r RUN_MIGRATIONS

    print_info "¿Deseas optimizar para producción? (y/n)"
    read -r RUN_OPTIMIZE

    build
    up

    if [ "$RUN_MIGRATIONS" = "y" ]; then
        sleep 5  # Esperar a que MySQL esté listo
        migrate
    fi

    clear_cache

    if [ "$RUN_OPTIMIZE" = "y" ]; then
        optimize
    fi

    print_header "✅ Deployment Completado"
    print_info "No olvides configurar Nginx Proxy Manager:"
    print_info "  - Forward Hostname/IP: gestion_expensas_app"
    print_info "  - Forward Port: 80"
}

# Actualizar aplicación
update() {
    print_header "🔄 Actualizando aplicación"

    print_info "Obteniendo últimos cambios..."
    git pull origin main

    print_info "Reconstruyendo contenedores..."
    docker-compose build

    print_info "Reiniciando contenedores..."
    docker-compose down
    docker-compose up -d

    print_info "Ejecutando migraciones..."
    sleep 5
    docker-compose exec app php artisan migrate --force

    clear_cache
    optimize

    print_success "Aplicación actualizada exitosamente"
}

# Menú de ayuda
show_help() {
    cat << EOF
${BLUE}Script de Deployment - GestionExpensas2026${NC}

${YELLOW}Comandos disponibles:${NC}

  ${GREEN}deploy${NC}          - Deployment completo (build, up, migrate, optimize)
  ${GREEN}update${NC}          - Actualizar aplicación (git pull, rebuild, migrate)
  ${GREEN}build${NC}           - Construir imágenes Docker
  ${GREEN}up${NC}              - Levantar contenedores
  ${GREEN}down${NC}            - Detener contenedores
  ${GREEN}restart${NC}         - Reiniciar contenedores
  ${GREEN}logs${NC}            - Ver logs de contenedores
  ${GREEN}migrate${NC}         - Ejecutar migraciones
  ${GREEN}seed${NC}            - Ejecutar seeders
  ${GREEN}clear-cache${NC}     - Limpiar caché de Laravel
  ${GREEN}optimize${NC}        - Optimizar para producción
  ${GREEN}backup${NC}          - Crear backup de base de datos
  ${GREEN}shell${NC}           - Acceder a la consola del contenedor app
  ${GREEN}status${NC}          - Ver estado de contenedores
  ${GREEN}help${NC}            - Mostrar esta ayuda

${YELLOW}Ejemplos:${NC}
  ./deploy.sh deploy
  ./deploy.sh logs
  ./deploy.sh migrate
  ./deploy.sh backup

EOF
}

# Comando shell
shell() {
    print_info "Accediendo a la consola del contenedor..."
    docker-compose exec app bash
}

# Ver estado
status() {
    print_header "Estado de Contenedores"
    docker-compose ps

    echo ""
    print_info "Uso de recursos:"
    docker stats --no-stream gestion_expensas_app gestion_expensas_mysql
}

# Reiniciar contenedores
restart() {
    print_header "Reiniciando contenedores"
    docker-compose restart
    print_success "Contenedores reiniciados"
}

# Procesar comandos
case "$1" in
    deploy)
        deploy
        ;;
    update)
        update
        ;;
    build)
        check_docker
        build
        ;;
    up)
        check_docker
        check_network
        setup_env
        up
        ;;
    down)
        down
        ;;
    restart)
        restart
        ;;
    logs)
        logs
        ;;
    migrate)
        migrate
        ;;
    seed)
        seed
        ;;
    clear-cache)
        clear_cache
        ;;
    optimize)
        optimize
        ;;
    backup)
        backup
        ;;
    shell)
        shell
        ;;
    status)
        status
        ;;
    help|--help|-h|"")
        show_help
        ;;
    *)
        print_error "Comando desconocido: $1"
        echo ""
        show_help
        exit 1
        ;;
esac