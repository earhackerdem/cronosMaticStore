# Makefile para CronosMatic Store - Gestión de Docker
# Usa 'make help' para ver todos los comandos disponibles

.PHONY: help
.DEFAULT_GOAL := help

# Colores para output
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[1;33m
RED := \033[0;31m
NC := \033[0m # No Color

# Configuración
DOCKER_COMPOSE := docker compose
DEV_SERVICE := dev
DB_SERVICE := db
REDIS_SERVICE := redis

##@ Ayuda

help: ## Mostrar esta ayuda
	@echo "$(BLUE)CronosMatic Store - Docker Management$(NC)"
	@echo ""
	@awk 'BEGIN {FS = ":.*##"; printf "Usage: make $(YELLOW)<target>$(NC)\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  $(YELLOW)%-20s$(NC) %s\n", $$1, $$2 } /^##@/ { printf "\n$(BLUE)%s$(NC)\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

##@ Docker - Servicios

up: ## Levantar todos los servicios
	@echo "$(GREEN)Levantando servicios...$(NC)"
	$(DOCKER_COMPOSE) up -d

down: ## Detener todos los servicios
	@echo "$(YELLOW)Deteniendo servicios...$(NC)"
	$(DOCKER_COMPOSE) down

restart: down up ## Reiniciar todos los servicios

rebuild: ## Reconstruir imágenes y levantar servicios
	@echo "$(BLUE)Reconstruyendo imágenes...$(NC)"
	$(DOCKER_COMPOSE) build --no-cache
	@echo "$(GREEN)Levantando servicios...$(NC)"
	$(DOCKER_COMPOSE) up -d

logs: ## Ver logs de todos los servicios
	$(DOCKER_COMPOSE) logs -f

logs-dev: ## Ver logs del servicio dev
	$(DOCKER_COMPOSE) logs -f $(DEV_SERVICE)

status: ## Mostrar estado de los servicios
	@echo "$(BLUE)Estado de servicios:$(NC)"
	$(DOCKER_COMPOSE) ps

info: ## Mostrar información del entorno
	@echo "$(BLUE)Información del Entorno Docker$(NC)"
	@echo "$(GREEN)Servicios activos:$(NC)"
	@$(DOCKER_COMPOSE) ps --format table
	@echo ""
	@echo "$(GREEN)URLs disponibles:$(NC)"
	@echo "  • Aplicación: http://localhost:3000"
	@echo "  • Vite (hot reload): http://localhost:5173"
	@echo "  • phpMyAdmin: http://localhost:8080"

##@ Docker - Shell

shell: ## Acceder a bash del contenedor dev
	@echo "$(BLUE)Accediendo al contenedor dev...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) bash

shell-db: ## Acceder a bash del contenedor db
	@echo "$(BLUE)Accediendo al contenedor db...$(NC)"
	$(DOCKER_COMPOSE) exec $(DB_SERVICE) bash

shell-redis: ## Acceder a redis-cli
	@echo "$(BLUE)Accediendo a redis-cli...$(NC)"
	$(DOCKER_COMPOSE) exec $(REDIS_SERVICE) redis-cli

##@ Laravel - Artisan

artisan: ## Ejecutar comando artisan (uso: make artisan CMD="migrate")
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan $(CMD)

migrate: ## Ejecutar migraciones
	@echo "$(BLUE)Ejecutando migraciones...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan migrate

migrate-fresh: ## Ejecutar migraciones desde cero con seed
	@echo "$(YELLOW)¡ADVERTENCIA! Esto borrará todos los datos$(NC)"
	@echo "$(YELLOW)Presiona Ctrl+C para cancelar...$(NC)"
	@sleep 3
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan migrate:fresh --seed

seed: ## Ejecutar seeders
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan db:seed

tinker: ## Abrir Laravel Tinker
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan tinker

cache-clear: ## Limpiar todos los caches de Laravel
	@echo "$(BLUE)Limpiando caches...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan config:clear
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan cache:clear
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan route:clear
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan view:clear
	@echo "$(GREEN)✓ Caches limpiados$(NC)"

optimize: ## Optimizar Laravel
	@echo "$(BLUE)Optimizando Laravel...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan config:cache
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan route:cache
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan view:cache
	@echo "$(GREEN)✓ Optimización completada$(NC)"

##@ Tests

test: ## Ejecutar todos los tests
	@echo "$(BLUE)Ejecutando todos los tests...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan test

test-backend: ## Ejecutar solo tests de backend (PHP/Laravel)
	@echo "$(BLUE)Ejecutando tests de backend...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan test

test-filter: ## Ejecutar tests filtrados (uso: make test-filter FILTER="ProfileTest")
	@echo "$(BLUE)Ejecutando tests filtrados: $(FILTER)$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan test --filter=$(FILTER)

test-frontend: ## Ejecutar tests de frontend (Vitest)
	@echo "$(BLUE)Ejecutando tests de frontend...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npm run test:run

test-coverage: ## Ejecutar tests con reporte de cobertura
	@echo "$(BLUE)Ejecutando tests con cobertura...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan test --coverage

test-parallel: ## Ejecutar tests en paralelo
	@echo "$(BLUE)Ejecutando tests en paralelo...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan test --parallel

test-e2e: ## Ejecutar tests E2E (Cypress)
	@echo "$(BLUE)Ejecutando tests E2E con Cypress...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npm run test:e2e

test-e2e-open: ## Abrir Cypress en modo interactivo
	@echo "$(BLUE)Abriendo Cypress UI...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npm run test:e2e:open

test-e2e-docker: ## Ejecutar tests E2E con configuración Docker
	@echo "$(BLUE)Ejecutando tests E2E con cypress.docker.config.ts...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npx cypress run --config-file cypress.docker.config.ts

test-all: ## Ejecutar TODOS los tests (backend + frontend + e2e)
	@echo "$(BLUE)Ejecutando suite completa de tests...$(NC)"
	@make test-backend
	@make test-frontend
	@make test-e2e
	@echo "$(GREEN)✓ Suite completa de tests ejecutada$(NC)"

##@ Dependencias

composer-install: ## Instalar dependencias PHP
	@echo "$(BLUE)Instalando dependencias PHP...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) composer install

composer-update: ## Actualizar dependencias PHP
	@echo "$(BLUE)Actualizando dependencias PHP...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) composer update

npm-install: ## Instalar dependencias Node
	@echo "$(BLUE)Instalando dependencias Node...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npm ci

npm-update: ## Actualizar dependencias Node
	@echo "$(BLUE)Actualizando dependencias Node...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npm update

install: composer-install npm-install ## Instalar todas las dependencias

##@ Build & Assets

build: ## Build assets de producción
	@echo "$(BLUE)Compilando assets...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npm run build

dev-assets: ## Iniciar Vite dev server
	@echo "$(BLUE)Iniciando Vite dev server...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npm run dev

watch: dev-assets ## Alias para dev-assets

##@ Code Quality

lint: ## Ejecutar linter
	@echo "$(BLUE)Ejecutando linter...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npm run lint

format: ## Formatear código
	@echo "$(BLUE)Formateando código...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npm run format

format-check: ## Verificar formato del código
	@echo "$(BLUE)Verificando formato...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npm run format:check

pint: ## Ejecutar Laravel Pint (PHP formatter)
	@echo "$(BLUE)Ejecutando Laravel Pint...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) ./vendor/bin/pint

types: ## Verificar tipos TypeScript
	@echo "$(BLUE)Verificando tipos TypeScript...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npm run types

quality: lint types pint ## Ejecutar todas las verificaciones de calidad

##@ Database

db-reset: migrate-fresh ## Resetear base de datos (alias)

db-backup: ## Crear backup de la base de datos
	@echo "$(BLUE)Creando backup de la base de datos...$(NC)"
	@mkdir -p backups
	$(DOCKER_COMPOSE) exec $(DB_SERVICE) mysqldump -u cronosmatic -pcronosmatic_password cronosmatic > backups/db_backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)✓ Backup creado en backups/$(NC)"

db-restore: ## Restaurar backup (uso: make db-restore FILE=backups/db_backup_xxx.sql)
	@echo "$(BLUE)Restaurando backup...$(NC)"
	$(DOCKER_COMPOSE) exec -T $(DB_SERVICE) mysql -u cronosmatic -pcronosmatic_password cronosmatic < $(FILE)
	@echo "$(GREEN)✓ Backup restaurado$(NC)"

##@ Mantenimiento

clean: ## Limpiar archivos temporales y caches
	@echo "$(BLUE)Limpiando archivos temporales...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan cache:clear
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan config:clear
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan route:clear
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan view:clear
	@echo "$(GREEN)✓ Limpieza completada$(NC)"

prune: ## Limpiar contenedores, volúmenes e imágenes no utilizados
	@echo "$(YELLOW)Limpiando Docker (contenedores, volúmenes, imágenes no usados)...$(NC)"
	docker system prune -a --volumes
	@echo "$(GREEN)✓ Docker limpiado$(NC)"

reset: down prune up migrate ## Reset completo del entorno

##@ Desarrollo

fresh: ## Setup completo desde cero
	@echo "$(BLUE)Setup completo del proyecto...$(NC)"
	$(DOCKER_COMPOSE) down -v
	$(DOCKER_COMPOSE) build --no-cache
	$(DOCKER_COMPOSE) up -d
	@echo "$(YELLOW)Esperando servicios...$(NC)"
	@sleep 10
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) composer install
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npm ci
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan key:generate
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan migrate --seed
	@echo "$(GREEN)✓ Setup completado!$(NC)"
	@make info

quick-start: up migrate seed ## Inicio rápido (levantar servicios y preparar DB)
	@echo "$(GREEN)✓ Proyecto listo!$(NC)"
	@make info
