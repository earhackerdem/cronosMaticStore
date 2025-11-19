# 🧪 Guía de Testing - CronosMatic

Esta guía explica cómo ejecutar los diferentes tipos de tests en el proyecto CronosMatic.

## 📊 Tipos de Tests Disponibles

### 1. Tests de Backend (PHP/Laravel)
- **Cantidad**: 93 tests
- **Tipos**: Unit tests, Feature tests, API tests
- **Framework**: PHPUnit
- **Ubicación**: `tests/` directory

### 2. Tests de Frontend (React/TypeScript)
- **Cantidad**: 34 tests
- **Tipos**: Component tests, Page tests
- **Framework**: Vitest + Testing Library
- **Ubicación**: `resources/js/__tests__/` directory

### 3. Tests E2E (End-to-End)
- **Cantidad**: 11 tests
- **Tipos**: Integration tests, User journey tests
- **Framework**: Cypress
- **Ubicación**: `cypress/e2e/` directory

## 🚀 Comandos para Ejecutar Tests

### Ejecutar Tests Individuales

```bash
# Backend (PHP/Laravel)
composer test
# o
npm run test:backend

# Frontend (React/Vitest)
npm run test:run
# o
npm run test:frontend

# E2E (Cypress)
npm run test:e2e
```

### Ejecutar Todos los Tests

```bash
# Opción 1: Comando npm secuencial
npm run test:all

# Opción 2: Script shell con mejor formato
./run-all-tests.sh
```

## 🐳 Tests en Docker con Make

Si estás usando Docker, puedes ejecutar todos los tests usando comandos Make simplificados:

### Comandos Básicos

```bash
# Backend (PHP/Laravel)
make test
# o
make test-backend

# Frontend (React/Vitest)
make test-frontend

# E2E (Cypress) - Usa configuración Docker con puerto 3000
make test-e2e

# E2E modo headless (para CI) - Usa configuración default
make test-e2e-headless

# E2E modo interactivo
make test-e2e-open

# Ejecutar TODOS los tests (backend + frontend + e2e)
make test-all
```

### Comandos Avanzados

```bash
# Tests con filtro específico
make test-filter FILTER="ProductTest"

# Tests con cobertura
make test-coverage

# Tests en paralelo (más rápido)
make test-parallel
```

### Configuración Docker para E2E

El entorno Docker usa una configuración especial para Cypress:
- **Archivo**: `cypress.docker.config.ts`
- **Base URL**: `http://localhost:3000` (contenedor dev)
- **Timeouts**: Aumentados a 15000ms para Docker
- **Reintentos**: 2 intentos automáticos en modo run
- **Chrome Flags**: `--no-sandbox`, `--disable-dev-shm-usage`, `--disable-gpu`

### Otros Comandos Make Útiles

```bash
# Limpiar cachés de Laravel
make cache-clear

# Ver logs del contenedor
make logs-dev

# Acceder al shell del contenedor
make shell

# Ver estado de servicios
make status

# Información del entorno
make info
```

## 📋 Comandos Adicionales

### Tests Frontend con Watch Mode
```bash
npm run test        # Modo watch (desarrollo)
npm run test:ui     # Interfaz gráfica de Vitest
npm run test:coverage  # Con reporte de cobertura
```

### Tests E2E Interactivos
```bash
npm run test:e2e:open  # Abre Cypress en modo interactivo
```

### Linting y Formateo
```bash
npm run lint        # ESLint con auto-fix
npm run format      # Prettier formatting
npm run format:check # Verificar formato
npm run types       # TypeScript type checking
```

## ✅ Resultado Esperado

Cuando todos los tests pasan exitosamente, deberías ver:

```
✅ Backend Tests (93 tests) - PASÓ
✅ Frontend Tests (34 tests) - PASÓ  
✅ E2E Tests (11 tests) - PASÓ
🎉 Total: 138 tests
```

## 🛠️ Configuración de Tests

### Backend (PHPUnit)
- Configuración: `phpunit.xml`
- Base de datos: SQLite en memoria para tests
- Factories: `database/factories/`
- Seeders para tests: `database/seeders/`

### Frontend (Vitest)
- Configuración: `vitest.config.ts`
- Setup: `resources/js/__tests__/setup.ts`
- Mocks: Incluye mocks para Inertia.js y componentes UI

### E2E (Cypress)
- Configuración local: `cypress.config.ts` (puerto 8000)
- Configuración Docker: `cypress.docker.config.ts` (puerto 3000)
- Base URL local: `http://localhost:8000`
- Base URL Docker: `http://localhost:3000`
- Soporte: `cypress/support/`

## 🔧 Troubleshooting

### Tests de Backend Fallan

**Local:**
```bash
# Limpiar caché y configuración
php artisan config:clear
php artisan cache:clear
php artisan test
```

**Docker:**
```bash
# Limpiar caché
make cache-clear

# Ejecutar tests
make test-backend

# Ver logs si hay errores
make logs-dev
```

### Tests E2E Fallan

**Local:**
```bash
# Asegúrate de que el servidor esté corriendo
php artisan serve  # Puerto 8000
npm run dev        # Puerto 5173
```

**Docker:**
```bash
# Verificar que los servicios estén corriendo
make status

# Verificar logs
make logs-dev

# Reiniciar servicios si es necesario
make restart

# Verificar URLs disponibles
make info
```

### Tests de Frontend Fallan

**Local:**
```bash
# Reinstalar dependencias
npm ci
npm run test:run
```

**Docker:**
```bash
# Reinstalar dependencias
make npm-install

# Ejecutar tests
make test-frontend
```

### Cypress no puede conectar al servidor (Docker)

Si ves el error "Cypress failed to verify that your server is running":

```bash
# Verificar que estés usando la configuración correcta
make test-e2e  # Usa cypress.docker.config.ts (puerto 3000) ✅

# NO uses:
npm run test:e2e  # Usa cypress.config.ts (puerto 8000) ❌
```

## 📝 Notas Importantes

1. **Prerequisitos**: Asegúrate de que los servidores estén corriendo para los tests E2E
   - **Local**: `php artisan serve` (puerto 8000) + `npm run dev` (puerto 5173)
   - **Docker**: `make up` o `docker compose up -d`
2. **Orden de Ejecución**: Backend → Frontend → E2E (para máxima confiabilidad)
3. **CI/CD**: Todos los tests deben pasar antes de hacer merge a main
4. **Cobertura**: Se recomienda mantener > 80% de cobertura en componentes críticos
5. **Docker**: Usa comandos `make` para ejecutar tests en Docker, no comandos `npm` directamente
6. **Configuración Cypress**:
   - Usa `cypress.docker.config.ts` en Docker (puerto 3000)
   - Usa `cypress.config.ts` en local (puerto 8000)

## 🎯 Tests por Funcionalidad

### TASK-CM-012 (Página de Detalle de Producto)
- **Backend**: `tests/Feature/Http/Controllers/ProductControllerTest.php`
- **Frontend**: `resources/js/__tests__/pages/Products/Show.test.tsx`
- **E2E**: `cypress/e2e/products.cy.ts`

### Criterios de Aceptación Cubiertos
- ✅ HU1.2: Vista de detalle de producto
- ✅ HU1.5: Visualización de stock
- ✅ Manejo de errores 404
- ✅ Integración con API endpoints
- ✅ Responsividad y UX
