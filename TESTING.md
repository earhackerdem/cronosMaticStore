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
- Configuración: `cypress.config.ts`
- Base URL: `http://localhost:8000`
- Soporte: `cypress/support/`

## 🔧 Troubleshooting

### Tests de Backend Fallan
```bash
# Limpiar caché y configuración
php artisan config:clear
php artisan cache:clear
php artisan test
```

### Tests E2E Fallan
```bash
# Asegúrate de que el servidor esté corriendo
php artisan serve  # Puerto 8000
npm run dev        # Puerto 5173
```

### Tests de Frontend Fallan
```bash
# Reinstalar dependencias
npm ci
npm run test:run
```

## 📝 Notas Importantes

1. **Prerequisitos**: Asegúrate de que los servidores estén corriendo para los tests E2E
2. **Orden de Ejecución**: Backend → Frontend → E2E (para máxima confiabilidad)
3. **CI/CD**: Todos los tests deben pasar antes de hacer merge a main
4. **Cobertura**: Se recomienda mantener > 80% de cobertura en componentes críticos

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
