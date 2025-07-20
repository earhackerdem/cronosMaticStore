# 🐳 Testing en Docker - CronosMatic

Esta guía explica cómo ejecutar la suite completa de tests en el entorno Docker de desarrollo.

## 🚀 Inicio Rápido

```bash
# Ejecutar todos los tests en Docker
./run-tests-docker.sh all

# O ejecutar tipos específicos
./run-tests-docker.sh backend    # Solo tests de backend
./run-tests-docker.sh frontend   # Solo tests de frontend  
./run-tests-docker.sh e2e        # Solo tests E2E
```

## 📋 Prerequisitos

1. **Docker y Docker Compose** instalados
2. **Rama feature/docker-dev-setup** activa
3. **Permisos de ejecución** en los scripts

```bash
chmod +x run-tests-docker.sh
chmod +x docker/test-setup.sh
```

## 🏗️ Arquitectura de Testing

### Scripts Principales
- `run-tests-docker.sh` - Script wrapper que orquesta la ejecución desde fuera del contenedor
- `docker/test-setup.sh` - Script interno que configura y ejecuta tests dentro del contenedor

### Configuraciones Específicas
- `phpunit.docker.xml` - Configuración de PHPUnit optimizada para Docker
- `vitest.docker.config.ts` - Configuración de Vitest con timeouts aumentados
- `cypress.docker.config.ts` - Configuración de Cypress para contenedores
- `.env.testing` - Variables de entorno específicas para testing

## 🔧 Configuración Automática

El sistema configura automáticamente:

### Base de Datos
- ✅ Crea `database/testing.sqlite` si no existe
- ✅ Configura permisos correctos
- ✅ Usa SQLite para tests rápidos

### Dependencias
- ✅ Verifica e instala dependencias PHP (`composer install`)
- ✅ Verifica e instala dependencias Node.js (`npm ci`)
- ✅ Limpia caché de Laravel

### Servidores
- ✅ Inicia Laravel en puerto 3000 para tests E2E
- ✅ Maneja el ciclo de vida de servidores automáticamente

## 📊 Tipos de Tests

### 1. Backend Tests (PHP/Laravel)
- **Cantidad**: ~93 tests
- **Framework**: PHPUnit
- **Base de datos**: SQLite (testing.sqlite)
- **Configuración**: `phpunit.docker.xml`

```bash
./run-tests-docker.sh backend
```

### 2. Frontend Tests (React/TypeScript)
- **Cantidad**: ~34 tests  
- **Framework**: Vitest + Testing Library
- **Configuración**: `vitest.docker.config.ts`

```bash
./run-tests-docker.sh frontend
```

### 3. E2E Tests (Cypress)
- **Cantidad**: ~11 tests
- **Framework**: Cypress
- **URL base**: http://localhost:3000
- **Configuración**: `cypress.docker.config.ts`

```bash
./run-tests-docker.sh e2e
```

## 🐳 Servicios Docker Necesarios

Los tests requieren estos servicios corriendo:

```yaml
services:
  dev:      # Contenedor principal de desarrollo
  db:       # MariaDB (para la aplicación, no para tests)
  redis:    # Redis para caché y sesiones
```

El script automáticamente:
1. ✅ Verifica que los servicios estén corriendo
2. ✅ Los inicia si es necesario (`docker compose up -d dev db redis`)
3. ✅ Espera a que estén listos antes de ejecutar tests

## 📁 Estructura de Archivos

```
.
├── run-tests-docker.sh           # Script principal (externo)
├── docker/
│   └── test-setup.sh             # Script de configuración (interno)
├── phpunit.docker.xml            # Configuración PHPUnit para Docker
├── vitest.docker.config.ts       # Configuración Vitest para Docker
├── cypress.docker.config.ts      # Configuración Cypress para Docker
├── .env.testing                  # Variables de entorno para testing
└── tests/
    └── results/                  # Directorio para reportes de tests
        ├── junit.xml
        ├── testdox.html
        ├── coverage/
        └── vitest-results.json
```

## 🎯 Flujo de Ejecución

### Para `./run-tests-docker.sh all`:

1. **Verificación** - Docker instalado y funcionando
2. **Contenedores** - Verifica/inicia servicios necesarios  
3. **Backend** - Ejecuta tests PHP/Laravel con SQLite
4. **Frontend** - Ejecuta tests React/Vitest
5. **E2E Setup** - Inicia servidor Laravel en puerto 3000
6. **E2E Tests** - Ejecuta tests Cypress
7. **Cleanup** - Termina servidores temporales
8. **Reporte** - Muestra resumen completo

## 🔍 Debugging

### Acceder al Contenedor
```bash
docker compose exec dev bash
```

### Ejecutar Tests Manualmente
```bash
# Dentro del contenedor
php artisan test --configuration=phpunit.docker.xml
npx vitest run --config vitest.docker.config.ts  
npm run test:e2e:docker
```

### Ver Logs de Contenedores
```bash
docker compose logs -f dev
docker compose logs -f db
```

### Verificar Estado de Servicios
```bash
docker compose ps
```

## ⚡ Optimizaciones para Docker

### PHPUnit
- Memoria aumentada a 512M
- Timeout de 300 segundos
- SQLite para velocidad
- Reportes en `tests/results/`

### Vitest  
- Workers limitados (1-2) para Docker
- Timeouts aumentados (15s)
- Pool optimizado para contenedores
- Cobertura en `tests/results/coverage/`

### Cypress
- Timeouts aumentados (15s comandos, 45s páginas)
- Reintentos automáticos (2x en headless)
- Flags Chrome optimizados (`--no-sandbox`, `--disable-dev-shm-usage`)
- Base URL apunta a puerto 3000

## 🚨 Troubleshooting Común

### Error: "Docker no encontrado"
```bash
# Instalar Docker y Docker Compose
# Verificar que estén en el PATH
docker --version
docker compose version
```

### Error: "Contenedores no inician"
```bash
# Limpiar y reiniciar
docker compose down
docker compose up -d dev db redis
```

### Error: "Base de datos no accesible"
```bash
# Verificar permisos del archivo SQLite
docker compose exec dev ls -la database/testing.sqlite
docker compose exec dev chmod 666 database/testing.sqlite
```

### Error: "Tests E2E fallan"
```bash
# Verificar que Laravel esté en puerto 3000
docker compose exec dev curl http://localhost:3000
# Si no responde, verificar logs
docker compose logs dev
```

### Error: "Dependencias faltantes"
```bash
# Reinstalar dependencias en el contenedor
docker compose exec dev composer install
docker compose exec dev npm ci
```

## 🎉 Resultado Exitoso

Cuando todos los tests pasan, verás:

```
🎉 ¡TODOS LOS TESTS PASARON EXITOSAMENTE!
========================================
📊 Resumen de Tests en Docker:
   • Backend (PHP/Laravel): 93 tests
   • Frontend (React/Vitest): 34 tests
   • E2E (Cypress): 11 tests
   • Total: 138 tests
========================================
✨ ¡Suite de tests completa en Docker! ✨
```

## 📝 Notas Importantes

1. **Primera ejecución** puede tomar más tiempo (instalación de dependencias)
2. **SQLite** se usa para tests, no MariaDB (para velocidad)
3. **Puerto 3000** se usa para E2E, no 8000 (configuración Docker)
4. **Resultados** se guardan en `tests/results/` para análisis
5. **Limpieza automática** de servidores temporales al finalizar

---

Para más información sobre testing en general, consulta [TESTING.md](./TESTING.md)