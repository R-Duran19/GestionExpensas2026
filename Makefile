.PHONY: help build up down restart logs migrate seed clear-cache optimize backup shell status deploy update

# Colores para output
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[1;33m
RED := \033[0;31m
NC := \033[0m

help: ## Mostrar esta ayuda
	@echo "$(BLUE)Comandos disponibles para GestionExpensas2026$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-15s$(NC) %s\n", $$1, $$2}'
	@echo ""

build: ## Construir imágenes Docker
	@echo "$(BLUE)Construyendo imágenes Docker...$(NC)"
	docker-compose build
	@echo "$(GREEN)✅ Imágenes construidas$(NC)"

up: ## Levantar contenedores
	@echo "$(BLUE)Levantando contenedores...$(NC)"
	docker-compose up -d
	@echo "$(GREEN)✅ Contenedores levantados$(NC)"
	@docker-compose ps

down: ## Detener contenedores
	@echo "$(BLUE)Deteniendo contenedores...$(NC)"
	docker-compose down
	@echo "$(GREEN)✅ Contenedores detenidos$(NC)"

restart: ## Reiniciar contenedores
	@echo "$(BLUE)Reiniciando contenedores...$(NC)"
	docker-compose restart
	@echo "$(GREEN)✅ Contenedores reiniciados$(NC)"

logs: ## Ver logs de contenedores (Ctrl+C para salir)
	docker-compose logs -f

logs-app: ## Ver logs solo del contenedor app
	docker-compose logs -f app

logs-mysql: ## Ver logs solo del contenedor mysql
	docker-compose logs -f mysql

migrate: ## Ejecutar migraciones de base de datos
	@echo "$(BLUE)Ejecutando migraciones...$(NC)"
	docker-compose exec app php artisan migrate --force
	@echo "$(GREEN)✅ Migraciones completadas$(NC)"

migrate-fresh: ## Ejecutar migraciones frescas (borra todo)
	@echo "$(YELLOW)⚠️  Esto borrará todos los datos de la base de datos$(NC)"
	@read -p "¿Estás seguro? (yes/no): " confirm; \
	if [ "$$confirm" = "yes" ]; then \
		docker-compose exec app php artisan migrate:fresh --force; \
		echo "$(GREEN)✅ Migraciones frescas completadas$(NC)"; \
	else \
		echo "$(RED)❌ Cancelado$(NC)"; \
	fi

seed: ## Ejecutar seeders de base de datos
	@echo "$(BLUE)Ejecutando seeders...$(NC)"
	docker-compose exec app php artisan db:seed --force
	@echo "$(GREEN)✅ Seeders completados$(NC)"

migrate-seed: ## Ejecutar migraciones y seeders
	@echo "$(BLUE)Ejecutando migraciones y seeders...$(NC)"
	docker-compose exec app php artisan migrate --force --seed
	@echo "$(GREEN)✅ Migraciones y seeders completados$(NC)"

clear-cache: ## Limpiar caché de Laravel
	@echo "$(BLUE)Limpiando caché...$(NC)"
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear
	docker-compose exec app php artisan cache:clear
	@echo "$(GREEN)✅ Caché limpiada$(NC)"

optimize: ## Optimizar para producción
	@echo "$(BLUE)Optimizando para producción...$(NC)"
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache
	@echo "$(GREEN)✅ Optimización completada$(NC)"

storage-link: ## Crear storage link
	@echo "$(BLUE)Creando storage link...$(NC)"
	docker-compose exec app php artisan storage:link
	@echo "$(GREEN)✅ Storage link creado$(NC)"

backup: ## Crear backup de base de datos
	@echo "$(BLUE)Creando backup...$(NC)"
	@mkdir -p backups
	docker-compose exec mysql mysqldump -u expensas_user -p expensas_db > backups/backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)✅ Backup creado$(NC)"

shell: ## Acceder a la consola del contenedor app
	docker-compose exec app bash

mysql: ## Acceder a la consola de MySQL
	docker-compose exec mysql mysql -u expensas_user -p expensas_db

artisan: ## Ejecutar comando de Artisan (ej: make artisan cmd=route:list)
	docker-compose exec app php artisan $(cmd)

status: ## Ver estado de contenedores
	@echo "$(BLUE)Estado de contenedores:$(NC)"
	@docker-compose ps
	@echo ""
	@echo "$(BLUE)Uso de recursos:$(NC)"
	@docker stats --no-stream gestion_expensas_app gestion_expensas_mysql

ps: ## Ver lista de contenedores
	docker-compose ps

networks: ## Ver redes Docker disponibles
	@echo "$(BLUE)Redes Docker disponibles:$(NC)"
	@docker network ls | grep -E "NETWORK ID|geopricer|nginx-proxy|expensas"

deploy: ## Deployment completo (build + up + migrate + optimize)
	@echo "$(BLUE)🚀 Iniciando deployment...$(NC)"
	@make build
	@make up
	@sleep 5
	@make migrate
	@make clear-cache
	@make optimize
	@echo "$(GREEN)✅ Deployment completado$(NC)"
	@echo "$(BLUE)⚠️  No olvides configurar Nginx Proxy Manager:$(NC)"
	@echo "   Forward Hostname/IP: gestion_expensas_app"
	@echo "   Forward Port: 80"

update: ## Actualizar aplicación (git pull + rebuild)
	@echo "$(BLUE)🔄 Actualizando aplicación...$(NC)"
	@git pull origin main
	@make build
	@make down
	@make up
	@sleep 5
	@make migrate
	@make clear-cache
	@make optimize
	@echo "$(GREEN)✅ Actualización completada$(NC)"

install: ## Instalación inicial (setup + deploy)
	@echo "$(BLUE)🔧 Configuración inicial...$(NC)"
	@if [ ! -f .env.docker ]; then \
		cp .env.docker.example .env.docker; \
		echo "$(YELLOW)⚠️  Por favor edita .env.docker con tus configuraciones$(NC)"; \
		read -p "Presiona ENTER cuando hayas terminado: "; \
	fi
	@make deploy

clean: ## Limpiar contenedores, volúmenes e imágenes no utilizados
	@echo "$(YELLOW)⚠️  Esto eliminará contenedores, volúmenes e imágenes no utilizados$(NC)"
	@read -p "¿Estás seguro? (yes/no): " confirm; \
	if [ "$$confirm" = "yes" ]; then \
		docker system prune -a --volumes; \
		echo "$(GREEN)✅ Limpieza completada$(NC)"; \
	else \
		echo "$(RED)❌ Cancelado$(NC)"; \
	fi

test: ## Ejecutar tests
	@echo "$(BLUE)Ejecutando tests...$(NC)"
	docker-compose exec app php artisan test

tinker: ## Ejecutar Tinker
	docker-compose exec app php artisan tinker