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

## Testing

Este proyecto incluye una suite completa de testing:

### Comandos de Testing Disponibles
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
