## Requisitos previos
- PHP 8.2 o superior
- Composer
- Node.js (versión reciente)
- npm

## Pasos para la instalación

### 1. Clonar el repositorio
```
git clone https://github.com/earhackerdem/cronosMaticStore
cd cronosMaticStore
```

### 2. Instalar dependencias de PHP
```
composer install
```

### 3. Instalar dependencias de JavaScript
```
npm install
```

### 4. Configurar el entorno
```
cp .env.example .env
```

### 5. Configurar SQLite
Edita el archivo `.env` para usar SQLite:
```
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

### 6. Crear el archivo de base de datos SQLite
```
touch database/database.sqlite
```

### 7. Generar la clave de la aplicación
```
php artisan key:generate
```

### 8. Ejecutar las migraciones
```
php artisan migrate
```

### 9. Configurar base de datos de pruebas
Para ejecutar las pruebas, se utiliza una base de datos SQLite separada. El archivo de la base de datos de pruebas se creará automáticamente al ejecutar los tests o puede crearlo manualmente:
```
touch database/testing.sqlite
```
Asegúrate de que tu archivo `.env.testing` (si lo tienes) o tu configuración de `phpunit.xml` apunten a `database/testing.sqlite`.

### 10. Opcional: Cargar datos de prueba
```
php artisan db:seed
```

### 11. Compilar assets
```
npm run build
```

### 12. Iniciar el servidor
```
php artisan serve
```

Ahora puedes acceder a la aplicación en http://localhost:8000

## Desarrollo

Para trabajar en modo desarrollo con hot reload:
```
npm run dev
```

En otra terminal, inicia el servidor de Laravel:
```
php artisan serve
```

## Comando todo en uno para desarrollo
También puedes usar el comando definido en composer.json:
```
composer run dev
```

Esto iniciará concurrentemente el servidor Laravel, la cola de trabajos, los logs y Vite en modo desarrollo.

## 🐳 Instalación y Uso con Docker

Docker proporciona un entorno de desarrollo consistente y aislado. Este proyecto incluye soporte completo para Docker con comandos Make simplificados.

### Requisitos para Docker
- Docker
- Docker Compose (incluido con Docker Desktop)

### Setup Inicial con Docker

#### Opción 1: Setup Rápido con Script
```bash
# Entorno de desarrollo (recomendado)
./docker-setup.sh dev

# Entorno de desarrollo con phpMyAdmin
./docker-setup.sh dev-full

# Entorno de producción
./docker-setup.sh prod
```

#### Opción 2: Setup con Make (Recomendado)
```bash
# Setup completo desde cero
make fresh

# O inicio rápido si ya está configurado
make quick-start
```

### Comandos Docker Esenciales

#### Gestión de Servicios
```bash
make up          # Levantar servicios
make down        # Detener servicios
make restart     # Reiniciar servicios
make rebuild     # Reconstruir imágenes
make status      # Ver estado de servicios
make info        # Ver información y URLs
```

#### Acceso a Contenedores
```bash
make shell       # Acceder al contenedor dev
make shell-db    # Acceder a MariaDB
make shell-redis # Acceder a Redis CLI
make logs-dev    # Ver logs del contenedor dev
```

#### URLs Disponibles (Desarrollo)
- **Aplicación**: http://localhost:3000
- **Vite (Hot Reload)**: http://localhost:5173
- **phpMyAdmin**: http://localhost:8080
  - Usuario: `cronosmatic`
  - Contraseña: `cronosmatic_password`

### Comandos Laravel en Docker
```bash
make migrate            # Ejecutar migraciones
make migrate-fresh      # Resetear DB con seed
make seed              # Ejecutar seeders
make cache-clear       # Limpiar cachés
make optimize          # Optimizar Laravel
make artisan CMD="..." # Ejecutar comando artisan
```

### Gestión de Dependencias
```bash
make composer-install  # Instalar dependencias PHP
make composer-update   # Actualizar dependencias PHP
make npm-install       # Instalar dependencias Node
make npm-update        # Actualizar dependencias Node
make install           # Instalar todas las dependencias
```

### Assets y Build
```bash
make build        # Compilar assets de producción
make dev-assets   # Iniciar Vite dev server
make watch        # Alias para dev-assets
```

### Code Quality
```bash
make lint         # Ejecutar ESLint
make format       # Formatear código con Prettier
make format-check # Verificar formato
make pint         # Ejecutar Laravel Pint (PHP)
make types        # Verificar tipos TypeScript
make quality      # Ejecutar todas las verificaciones
```

### Base de Datos
```bash
make db-reset                    # Resetear base de datos
make db-backup                   # Crear backup
make db-restore FILE=backup.sql  # Restaurar backup
```

### Ver todos los comandos disponibles
```bash
make help  # Lista completa de comandos Make
```

## Testing

Este proyecto incluye una suite completa de testing con 138 tests distribuidos entre backend, frontend y E2E.

### Comandos de Testing (Local)
```bash
# Tests unitarios y de integración
npm run test                    # Ejecutar tests en modo watch
npm run test:run               # Ejecutar tests una vez
npm run test:coverage          # Generar reporte de cobertura

# Tests E2E con Cypress
npm run cypress:open           # Abrir Cypress UI
npm run cypress:run            # Ejecutar tests E2E headless
npm run test:e2e              # Alias para cypress:run
npm run test:e2e:open         # Alias para cypress:open

# Laravel Tests
./vendor/bin/phpunit           # Tests backend PHP
npm run test:backend           # Alias para PHPUnit
```

### Comandos de Testing con Docker/Make
```bash
# Tests individuales
make test-backend    # Tests PHP/Laravel (93 tests)
make test-frontend   # Tests React/Vitest (34 tests)
make test-e2e        # Tests E2E Cypress (11 tests)

# Tests E2E adicionales
make test-e2e-open       # Cypress modo interactivo
make test-e2e-headless   # Cypress para CI

# Ejecutar todos los tests
make test-all        # Backend + Frontend + E2E (138 tests)

# Tests avanzados
make test-filter FILTER="ProductTest"  # Filtrar tests
make test-coverage                     # Con cobertura
make test-parallel                     # Ejecutar en paralelo
```

### Resultado Esperado
Cuando todos los tests pasan exitosamente:
```
✅ Backend Tests (93 tests) - PASÓ
✅ Frontend Tests (34 tests) - PASÓ
✅ E2E Tests (11 tests) - PASÓ
🎉 Total: 138 tests
```

### Estructura de Testing
- **Unit Tests**: `resources/js/__tests__/components/` - Tests de componentes individuales
- **Integration Tests**: `resources/js/__tests__/pages/` - Tests de páginas completas
- **E2E Tests**: `cypress/e2e/` - Tests end-to-end con Cypress
- **Backend Tests**: `tests/` - Tests PHPUnit de Laravel

### Cobertura de Testing
- Umbral mínimo: 70% de cobertura
- Reportes HTML generados en: `coverage/index.html`
- Componentes principales cubiertos: LoadingSpinner, Button, ProductsIndex

### Configuración Cypress para Docker
Los tests E2E utilizan diferentes configuraciones según el entorno:
- **Docker**: `cypress.docker.config.ts` - Puerto 3000 (comando `make test-e2e`)
- **Local**: `cypress.config.ts` - Puerto 8000 (comando `npm run test:e2e`)

> ⚠️ **Importante**: Si usas Docker, siempre ejecuta tests E2E con `make test-e2e`, NO con `npm run test:e2e`

Para más detalles sobre testing, consulta [TESTING.md](TESTING.md)

## Workflows de GitHub Actions

Este proyecto utiliza GitHub Actions para automatizar CI/CD:

### Tests Completos (`tests.yml`)
Se ejecuta en push/PR a `develop` y `main`:
- **Backend Tests**: PHPUnit + Laravel
- **Frontend Tests**: Vitest + React Testing Library + cobertura
- **E2E Tests**: Cypress con servidor Laravel completo

### Tests Frontend Rápidos (`frontend-tests.yml`)
Se ejecuta solo cuando cambian archivos frontend:
- **Unit/Integration**: Tests rápidos con Vitest
- **Component Tests**: Cypress component testing
- **Coverage**: Reportes de cobertura automáticos en PRs
- **Type Check**: Validación TypeScript

### Linter (`lint.yml`)
Se ejecuta en push/PR a `develop` y `main`:
- **PHP**: Laravel Pint para formateo automático
- **Frontend**: ESLint + Prettier para código TypeScript/React
- **Auto-format**: Formatea código automáticamente
