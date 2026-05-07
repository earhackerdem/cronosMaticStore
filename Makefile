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

# Variables comunes para apuntar al servicio db_test (MariaDB aislada).
# Se inyectan al `docker compose exec` para que phpunit/artisan usen esa BD
# sin tocar la de desarrollo (`cronosmatic`).
TEST_DB_ENV := -e APP_ENV=testing \
	-e DB_CONNECTION=mariadb \
	-e DB_HOST=db_test \
	-e DB_PORT=3306 \
	-e DB_DATABASE=cronosmatic_test \
	-e DB_USERNAME=cronosmatic \
	-e DB_PASSWORD=cronosmatic_password \
	-e SESSION_DRIVER=array \
	-e CACHE_STORE=array \
	-e QUEUE_CONNECTION=sync \
	-e MAIL_MAILER=array \
	-e PAYPAL_SIMULATE_PAYMENTS=true

# Variables para el server HTTP que usa Cypress: la sesión NO puede ser `array`
# (rompe el login/register entre requests). Usamos `file` con un path aislado
# para evitar colisiones con Redis/Sessions del server normal de dev.
TEST_E2E_SERVER_ENV := -e APP_ENV=testing \
	-e DB_CONNECTION=mariadb \
	-e DB_HOST=db_test \
	-e DB_PORT=3306 \
	-e DB_DATABASE=cronosmatic_test \
	-e DB_USERNAME=cronosmatic \
	-e DB_PASSWORD=cronosmatic_password \
	-e SESSION_DRIVER=file \
	-e SESSION_FILES=/tmp/cm_test_sessions \
	-e CACHE_STORE=array \
	-e QUEUE_CONNECTION=sync \
	-e MAIL_MAILER=array \
	-e PAYPAL_SIMULATE_PAYMENTS=true

test: ## Ejecutar todos los tests
	@echo "$(BLUE)Ejecutando todos los tests...$(NC)"
	$(DOCKER_COMPOSE) exec $(TEST_DB_ENV) $(DEV_SERVICE) php artisan test

test-db-prepare: ## Preparar BD de testing (cronosmatic_test): migrate:fresh + seed
	@echo "$(BLUE)Preparando BD de testing (cronosmatic_test)...$(NC)"
	$(DOCKER_COMPOSE) exec $(TEST_DB_ENV) $(DEV_SERVICE) php artisan migrate:fresh --seed --force
	@echo "$(GREEN)✓ BD de testing preparada$(NC)"

test-backend: ## Ejecutar solo tests de backend (PHP/Laravel)
	@echo "$(BLUE)Ejecutando tests de backend...$(NC)"
	$(DOCKER_COMPOSE) exec $(TEST_DB_ENV) $(DEV_SERVICE) php artisan test

test-filter: ## Ejecutar tests filtrados (uso: make test-filter FILTER="ProfileTest")
	@echo "$(BLUE)Ejecutando tests filtrados: $(FILTER)$(NC)"
	$(DOCKER_COMPOSE) exec $(TEST_DB_ENV) $(DEV_SERVICE) php artisan test --filter=$(FILTER)

test-frontend: ## Ejecutar tests de frontend (Vitest)
	@echo "$(BLUE)Ejecutando tests de frontend...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npm run test:run

test-coverage: ## Ejecutar tests con reporte de cobertura
	@echo "$(BLUE)Ejecutando tests con cobertura...$(NC)"
	$(DOCKER_COMPOSE) exec $(TEST_DB_ENV) $(DEV_SERVICE) php artisan test --coverage

test-parallel: ## Ejecutar tests en paralelo
	@echo "$(BLUE)Ejecutando tests en paralelo...$(NC)"
	$(DOCKER_COMPOSE) exec $(TEST_DB_ENV) $(DEV_SERVICE) php artisan test --parallel

# Para Cypress E2E reconfiguramos el server existente del puerto 3000 para que
# apunte temporalmente a la BD de testing (cronosmatic_test). Hacemos esto
# escribiendo un `.env` con valores de testing, reiniciando el server Laravel
# (matando `php artisan serve` que supervisord levanta de nuevo en segundos),
# y al terminar restauramos el `.env` original. Esto preserva la integración
# Vite/Inertia y evita los problemas de un segundo `php artisan serve`.
#
# La BD de desarrollo (cronosmatic) NO se modifica en ningún momento — solo
# cambia a qué BD apunta el server durante el run de Cypress.

define start_test_server
	@echo "$(BLUE)Backup del .env del container y aplicación de overrides de testing...$(NC)"
	@$(DOCKER_COMPOSE) exec -T $(DEV_SERVICE) bash -c "cp -f .env .env.backup-test 2>/dev/null || true"
	@$(DOCKER_COMPOSE) exec -T $(DEV_SERVICE) bash -c "sed -i -E 's|^DB_HOST=.*|DB_HOST=db_test|; s|^DB_DATABASE=.*|DB_DATABASE=cronosmatic_test|; s|^APP_ENV=.*|APP_ENV=testing|; s|^PAYPAL_SIMULATE_PAYMENTS=.*|PAYPAL_SIMULATE_PAYMENTS=true|; s|^MAIL_MAILER=.*|MAIL_MAILER=array|' .env"
	@$(DOCKER_COMPOSE) exec -T $(DEV_SERVICE) php artisan config:clear > /dev/null
	@$(DOCKER_COMPOSE) exec -T $(DEV_SERVICE) php artisan route:clear > /dev/null
	@echo "$(BLUE)Reiniciando server Laravel (supervisord lo relanza apuntando a BD de testing)...$(NC)"
	@$(DOCKER_COMPOSE) exec -T $(DEV_SERVICE) bash -c "pkill -HUP -f 'artisan serve' 2>/dev/null; pkill -f 'artisan serve' 2>/dev/null; true" || true
	@$(DOCKER_COMPOSE) exec -T -d $(DEV_SERVICE) bash -c "php artisan serve --host=0.0.0.0 --port=3000 > /tmp/test-server.log 2>&1"
	@echo "$(BLUE)Esperando a que el server responda...$(NC)"
	@$(DOCKER_COMPOSE) exec $(DEV_SERVICE) bash -c 'for i in {1..30}; do curl -sf http://localhost:3000/up > /dev/null && exit 0; sleep 1; done; echo "Timeout"; exit 1'
endef

define stop_test_server
	@echo "$(BLUE)Restaurando .env original y reiniciando server de desarrollo...$(NC)"
	@$(DOCKER_COMPOSE) exec -T $(DEV_SERVICE) bash -c "[ -f .env.backup-test ] && mv -f .env.backup-test .env || true"
	@$(DOCKER_COMPOSE) exec -T $(DEV_SERVICE) php artisan config:clear > /dev/null 2>&1 || true
	@$(DOCKER_COMPOSE) exec -T $(DEV_SERVICE) bash -c "pkill -f 'artisan serve' 2>/dev/null; sleep 1; nohup php artisan serve --host=0.0.0.0 --port=3000 > /dev/null 2>&1 &" || true
endef

test-e2e: test-db-prepare ## Ejecutar tests E2E (Cypress) contra BD de testing aislada
	$(call start_test_server)
	@echo "$(BLUE)Ejecutando tests E2E con Cypress (puerto 3000, BD cronosmatic_test)...$(NC)"
	@$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npx cypress run --config-file cypress.docker.config.ts; \
	  exit_code=$$?; \
	  $(MAKE) -s _stop_test_server; \
	  exit $$exit_code

test-e2e-open: test-db-prepare ## Abrir Cypress en modo interactivo (BD de testing aislada)
	$(call start_test_server)
	@echo "$(BLUE)Abriendo Cypress UI (puerto 3000, BD cronosmatic_test)...$(NC)"
	@$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npx cypress open --config-file cypress.docker.config.ts; \
	  $(MAKE) -s _stop_test_server

test-e2e-headless: test-db-prepare ## Ejecutar tests E2E sin configuración especial (para CI)
	$(call start_test_server)
	@$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npm run test:e2e; \
	  exit_code=$$?; \
	  $(MAKE) -s _stop_test_server; \
	  exit $$exit_code

# Helper interno usado por los targets test-e2e* — restaura el .env y reinicia
# el server de desarrollo apuntando a la BD `cronosmatic` original.
_stop_test_server:
	$(call stop_test_server)

test-all: ## Ejecutar TODOS los tests (backend + frontend + e2e) usando db_test
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
	$(DOCKER_COMPOSE) exec -T $(DB_SERVICE) sh -c 'mysqldump -u "$$MYSQL_USER" -p"$$MYSQL_PASSWORD" "$$MYSQL_DATABASE"' > backups/db_backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)✓ Backup creado en backups/$(NC)"

db-restore: ## Restaurar backup (uso: make db-restore FILE=backups/db_backup_xxx.sql)
	@echo "$(BLUE)Restaurando backup...$(NC)"
	$(DOCKER_COMPOSE) exec -T $(DB_SERVICE) sh -c 'mysql -u "$$MYSQL_USER" -p"$$MYSQL_PASSWORD" "$$MYSQL_DATABASE"' < $(FILE)
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

setup: ## Configurar el proyecto para desarrollo (idempotente, no destructivo)
	@echo "$(BLUE)Configurando proyecto para desarrollo...$(NC)"
	@if [ ! -f .env ]; then \
		echo "$(YELLOW)No se encontró .env, copiando desde .env.docker.example...$(NC)"; \
		if [ -f .env.docker.example ]; then \
			cp .env.docker.example .env; \
		elif [ -f .env.example ]; then \
			cp .env.example .env; \
		else \
			echo "$(RED)✗ No se encontró .env.docker.example ni .env.example$(NC)"; \
			exit 1; \
		fi; \
		echo "$(GREEN)✓ .env creado$(NC)"; \
	else \
		echo "$(GREEN)✓ .env ya existe, no se sobreescribe$(NC)"; \
	fi
	@echo "$(BLUE)Levantando servicios Docker...$(NC)"
	$(DOCKER_COMPOSE) up -d
	@echo "$(YELLOW)Esperando a que los servicios estén listos...$(NC)"
	@sleep 10
	@echo "$(BLUE)Instalando dependencias PHP...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) composer install
	@echo "$(BLUE)Instalando dependencias Node...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) npm ci
	@echo "$(BLUE)Generando APP_KEY si no existe...$(NC)"
	@$(DOCKER_COMPOSE) exec -T $(DEV_SERVICE) bash -c 'grep -qE "^APP_KEY=base64:" .env || php artisan key:generate --force'
	@echo "$(BLUE)Ejecutando migraciones...$(NC)"
	$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan migrate --force
	@echo "$(BLUE)Verificando si la BD necesita seed...$(NC)"
	@if $(DOCKER_COMPOSE) exec -T $(DEV_SERVICE) php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tail -n1 | grep -qE '^0$$'; then \
		echo "$(BLUE)BD vacía, ejecutando seeders...$(NC)"; \
		$(DOCKER_COMPOSE) exec $(DEV_SERVICE) php artisan db:seed --force; \
	else \
		echo "$(GREEN)✓ BD ya tiene datos, omitiendo seed$(NC)"; \
	fi
	@echo "$(BLUE)Creando enlace simbólico de storage...$(NC)"
	@$(DOCKER_COMPOSE) exec -T $(DEV_SERVICE) php artisan storage:link 2>/dev/null || true
	@echo "$(GREEN)✓ Proyecto configurado y listo para desarrollar!$(NC)"
	@make info

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
