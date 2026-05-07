# CronosMatic Store

Proyecto de comercio electrónico moderno construido con Laravel y React.

## Requisitos previos
- Docker & Docker Compose (recomendado)
- Opcional (instalación local): PHP 8.2+, Composer, Node.js 22+

## Inicio rápido con Docker

```bash
git clone https://github.com/earhackerdem/cronosMaticStore
cd cronosMaticStore
make setup
```

`make setup` es el comando de entrada para cualquier entorno nuevo. Es **idempotente** — puedes ejecutarlo varias veces sin romper nada. Hace exactamente esto:

1. Copia `.env.docker.example` → `.env` (si no existe)
2. Levanta los servicios Docker (`docker compose up -d`)
3. Instala dependencias PHP y Node
4. Genera `APP_KEY` si falta
5. Corre migraciones
6. Ejecuta seeders si la base de datos está vacía
7. Crea el symlink de storage

Cuando termina, la app queda disponible en **http://localhost:3000**.

## Cuándo usar cada comando de setup

| Comando | Cuándo usarlo |
|---|---|
| `make setup` | Primera vez, o para reparar un entorno sin perder datos |
| `make fresh` | Reset completo — destruye volúmenes, reconstruye imágenes desde cero |
| `make quick-start` | El entorno ya está configurado, solo necesitas levantar y migrar |

## Gestión de servicios

```bash
make up          # Levantar todos los servicios
make down        # Detener servicios
make restart     # Reiniciar servicios
make rebuild     # Reconstruir imágenes Docker
make status      # Ver estado de contenedores
make info        # Ver URLs y credenciales
make logs        # Ver logs de todos los servicios
make logs-dev    # Ver logs del contenedor dev
```

## URLs disponibles

| Servicio | URL |
|---|---|
| Aplicación | http://localhost:3000 |
| Vite HMR | http://localhost:5173 |
| phpMyAdmin | http://localhost:8080 |
| API REST | http://localhost:3000/api/v1/ |

phpMyAdmin: usuario `cronosmatic`, contraseña `cronosmatic_password`.

## Acceso a contenedores

```bash
make shell       # Bash en el contenedor dev
make shell-db    # Cliente MariaDB
make shell-redis # Redis CLI
make tinker      # Laravel Tinker
```

## Base de datos

```bash
make migrate            # Ejecutar migraciones pendientes
make migrate-fresh      # Reset completo con seed (destructivo)
make seed               # Ejecutar seeders
make db-reset           # Resetear base de datos
make db-backup          # Crear backup en database/backups/
make db-restore FILE=database/backups/backup.sql  # Restaurar backup
```

## Artisan y dependencias

```bash
make artisan CMD="route:list"   # Cualquier comando artisan
make cache-clear                # Limpiar cachés de Laravel
make optimize                   # Optimizar para producción
make composer-install           # Instalar dependencias PHP
make npm-install                # Instalar dependencias Node
make install                    # Instalar todas las dependencias
```

## Assets

```bash
make build       # Compilar assets de producción
make dev-assets  # Iniciar Vite dev server (alias: make watch)
```

## Code quality

```bash
make lint         # ESLint --fix
make pint         # Laravel Pint (PHP formatter)
make types        # tsc --noEmit
make format       # Prettier
make quality      # lint + types + pint
```

## Testing

La suite completa tiene **441 tests**: 284 backend, 109 frontend, 48 E2E.

```bash
make test-all     # Backend + Frontend + E2E (comando de referencia)

make test-backend                        # PHPUnit (284 tests)
make test-frontend                       # Vitest (109 tests)
make test-e2e                            # Cypress headless (48 tests)
make test-e2e-open                       # Cypress con UI interactiva

make test-filter FILTER="ProductTest"    # Filtrar tests backend por nombre
make test-coverage                       # PHPUnit con cobertura
make test-parallel                       # PHPUnit en paralelo
```

> **Docker vs local para E2E**: `make test-e2e` apunta a `:3000` (dev container).
> `npm run test:e2e` apunta a `:8000` (servicio app). En Docker usa siempre `make test-e2e`.

### Estructura de tests

```
tests/                          # PHPUnit
  Unit/
  Feature/Api/V1/

resources/js/__tests__/         # Vitest
  components/
  pages/

cypress/e2e/                    # Cypress
  products.cy.ts
  cart-functionality.cy.ts
  address-management.cy.ts
  checkout/checkout-flow.cy.ts
```

### Ver todos los comandos disponibles

```bash
make help
```

---

## Instalación local (sin Docker)

Si prefieres no usar Docker:

```bash
git clone https://github.com/earhackerdem/cronosMaticStore
cd cronosMaticStore
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Edita `.env` para usar SQLite:
```
DB_CONNECTION=sqlite
```

```bash
touch database/database.sqlite
php artisan migrate --seed
npm run build
php artisan serve   # terminal 1
npm run dev         # terminal 2
```

---

## CI/CD

GitHub Actions corre en push/PR a `develop` y `main`:

- **`tests.yml`** — Backend (PHPUnit + MariaDB service), Frontend (Vitest), E2E (Cypress)
- **`frontend-tests.yml`** — Solo cuando cambian archivos frontend; incluye type-check y cobertura
- **`lint.yml`** — ESLint, Prettier y Laravel Pint
