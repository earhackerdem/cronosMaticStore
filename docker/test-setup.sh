#!/bin/bash

# Script para configurar y ejecutar tests en el entorno Docker de CronosMatic
# Este script se ejecuta dentro del contenedor Docker

set -e  # Salir si cualquier comando falla

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para imprimir headers
print_header() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE} $1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

# Función para imprimir resultados
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2 - PASÓ${NC}"
    else
        echo -e "${RED}❌ $2 - FALLÓ${NC}"
        return 1
    fi
}

echo -e "${YELLOW}🐳 Configurando entorno de tests en Docker${NC}"

# 1. Verificar que las dependencias estén instaladas
print_header "Verificando Dependencias"

if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}📦 Instalando dependencias de PHP...${NC}"
    composer install --no-interaction --optimize-autoloader
fi

if [ ! -d "node_modules" ]; then
    echo -e "${YELLOW}📦 Instalando dependencias de Node.js...${NC}"
    npm ci
fi

# 2. Configurar base de datos de test
print_header "Configurando Base de Datos de Test"

# Crear base de datos SQLite para tests si no existe
if [ ! -f "database/testing.sqlite" ]; then
    echo -e "${YELLOW}🗄️  Creando base de datos de test SQLite...${NC}"
    touch database/testing.sqlite
fi

# Asegurar permisos correctos
chmod 666 database/testing.sqlite || true

# Crear directorio para resultados de tests
mkdir -p tests/results

# 3. Configurar entorno de test
print_header "Configurando Entorno de Test"

# Crear archivo .env.testing si no existe
if [ ! -f ".env.testing" ]; then
    echo -e "${YELLOW}⚙️  Creando archivo .env.testing...${NC}"
    cp .env.example .env.testing
    
    # Configurar variables específicas para testing
    sed -i 's/APP_ENV=local/APP_ENV=testing/' .env.testing
    sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env.testing
    sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env.testing
    sed -i 's/DB_DATABASE=.*/DB_DATABASE=database\/testing.sqlite/' .env.testing
    
    # Generar clave de aplicación para testing
    php artisan key:generate --env=testing --force
fi

# 4. Limpiar caché y configuración
print_header "Limpiando Caché"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo -e "${GREEN}✅ Configuración de tests completada${NC}"

# 5. Ejecutar tests según el parámetro
if [ "$1" = "backend" ]; then
    print_header "Tests de Backend (PHP/Laravel)"
    echo -e "${YELLOW}Ejecutando: php artisan test --configuration=phpunit.docker.xml${NC}"
    php artisan test --configuration=phpunit.docker.xml
    print_result $? "Backend Tests"
    
elif [ "$1" = "frontend" ]; then
    print_header "Tests de Frontend (React/Vitest)"
    echo -e "${YELLOW}Ejecutando: vitest run --config vitest.docker.config.ts${NC}"
    npx vitest run --config vitest.docker.config.ts
    print_result $? "Frontend Tests"
    
elif [ "$1" = "e2e" ]; then
    print_header "Tests E2E (Cypress)"
    echo -e "${YELLOW}Verificando servidor Laravel...${NC}"
    
    # Verificar si el servidor está corriendo
    if ! curl -s http://localhost:3000/ >/dev/null 2>&1; then
        echo -e "${YELLOW}🚀 Iniciando servidor Laravel para E2E...${NC}"
        php artisan serve --host=0.0.0.0 --port=3000 &
        SERVER_PID=$!
        
        # Esperar a que el servidor esté listo
        for i in {1..30}; do
            if curl -s http://localhost:3000/ >/dev/null 2>&1; then
                echo -e "${GREEN}✅ Servidor Laravel listo${NC}"
                break
            fi
            echo -e "${YELLOW}⏳ Esperando servidor... $i/30${NC}"
            sleep 1
        done
    fi
    
    echo -e "${YELLOW}Ejecutando: npm run test:e2e:docker${NC}"
    npm run test:e2e:docker
    TEST_RESULT=$?
    
    # Matar el servidor si lo iniciamos
    if [ ! -z "$SERVER_PID" ]; then
        kill $SERVER_PID 2>/dev/null || true
    fi
    
    print_result $TEST_RESULT "E2E Tests"
    
elif [ "$1" = "all" ]; then
    print_header "Ejecutando Todos los Tests"
    
    # Variables para conteo
    TOTAL_TESTS=0
    BACKEND_TESTS=0
    FRONTEND_TESTS=0
    E2E_TESTS=0
    
    # Tests de Backend
    echo -e "${YELLOW}🔧 Ejecutando tests de backend...${NC}"
    if php artisan test --configuration=phpunit.docker.xml; then
        BACKEND_TESTS=93  # Según documentación
        print_result 0 "Backend Tests ($BACKEND_TESTS tests)"
        TOTAL_TESTS=$((TOTAL_TESTS + BACKEND_TESTS))
    else
        print_result 1 "Backend Tests"
        exit 1
    fi
    
    # Tests de Frontend
    echo -e "${YELLOW}⚛️  Ejecutando tests de frontend...${NC}"
    if npx vitest run --config vitest.docker.config.ts; then
        FRONTEND_TESTS=34  # Según documentación
        print_result 0 "Frontend Tests ($FRONTEND_TESTS tests)"
        TOTAL_TESTS=$((TOTAL_TESTS + FRONTEND_TESTS))
    else
        print_result 1 "Frontend Tests"
        exit 1
    fi
    
    # Tests E2E
    echo -e "${YELLOW}🌐 Preparando tests E2E...${NC}"
    
    # Iniciar servidor para E2E
    php artisan serve --host=0.0.0.0 --port=3000 &
    SERVER_PID=$!
    
    # Esperar a que el servidor esté listo
    for i in {1..30}; do
        if curl -s http://localhost:3000/ >/dev/null 2>&1; then
            echo -e "${GREEN}✅ Servidor Laravel listo para E2E${NC}"
            break
        fi
        echo -e "${YELLOW}⏳ Esperando servidor... $i/30${NC}"
        sleep 1
    done
    
    if npm run test:e2e:docker; then
        E2E_TESTS=11  # Según documentación
        print_result 0 "E2E Tests ($E2E_TESTS tests)"
        TOTAL_TESTS=$((TOTAL_TESTS + E2E_TESTS))
    else
        print_result 1 "E2E Tests"
        kill $SERVER_PID 2>/dev/null || true
        exit 1
    fi
    
    # Limpiar servidor
    kill $SERVER_PID 2>/dev/null || true
    
    # Resumen final
    echo ""
    echo -e "${GREEN}🎉 ¡TODOS LOS TESTS PASARON EXITOSAMENTE!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}📊 Resumen de Tests en Docker:${NC}"
    echo -e "${GREEN}   • Backend (PHP/Laravel): $BACKEND_TESTS tests${NC}"
    echo -e "${GREEN}   • Frontend (React/Vitest): $FRONTEND_TESTS tests${NC}"
    echo -e "${GREEN}   • E2E (Cypress): $E2E_TESTS tests${NC}"
    echo -e "${GREEN}   • Total: $TOTAL_TESTS tests${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}✨ ¡Suite de tests completa en Docker! ✨${NC}"
    
else
    echo -e "${RED}❌ Uso: $0 [backend|frontend|e2e|all]${NC}"
    echo -e "${YELLOW}Ejemplos:${NC}"
    echo -e "${YELLOW}  $0 backend   # Solo tests de backend${NC}"
    echo -e "${YELLOW}  $0 frontend  # Solo tests de frontend${NC}"
    echo -e "${YELLOW}  $0 e2e       # Solo tests E2E${NC}"
    echo -e "${YELLOW}  $0 all       # Todos los tests${NC}"
    exit 1
fi