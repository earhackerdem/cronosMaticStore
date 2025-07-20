#!/bin/bash

# Script wrapper para ejecutar tests en el entorno Docker de CronosMatic
# Este script se ejecuta desde fuera del contenedor y orquesta la ejecución de tests

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

# Función para verificar Docker
check_docker() {
    if ! command -v docker &> /dev/null; then
        echo -e "${RED}❌ Docker no está instalado o no está en el PATH${NC}"
        exit 1
    fi
    
    if ! docker compose version &> /dev/null; then
        echo -e "${RED}❌ Docker Compose no está disponible${NC}"
        exit 1
    fi
}

# Función para verificar que los contenedores estén corriendo
check_containers() {
    echo -e "${YELLOW}🔍 Verificando estado de contenedores...${NC}"
    
    if ! docker compose ps --services --filter "status=running" | grep -q "dev\|db\|redis"; then
        echo -e "${YELLOW}⚠️  Los contenedores no están corriendo. Iniciando entorno de desarrollo...${NC}"
        
        # Iniciar contenedores necesarios
        docker compose up -d db redis dev
        
        # Esperar a que estén listos
        echo -e "${YELLOW}⏳ Esperando a que los servicios estén listos...${NC}"
        sleep 30
        
        # Verificar que estén corriendo
        if ! docker compose ps --services --filter "status=running" | grep -q "dev"; then
            echo -e "${RED}❌ Error: No se pudieron iniciar los contenedores${NC}"
            echo -e "${YELLOW}💡 Intenta ejecutar: docker compose up -d dev${NC}"
            exit 1
        fi
        
        echo -e "${GREEN}✅ Contenedores iniciados correctamente${NC}"
    else
        echo -e "${GREEN}✅ Contenedores ya están corriendo${NC}"
    fi
}

# Función para ejecutar comando en el contenedor
run_in_container() {
    local cmd="$1"
    echo -e "${BLUE}🐳 Ejecutando en contenedor: $cmd${NC}"
    docker compose exec -T dev bash -c "$cmd"
}

# Verificar argumentos
if [ $# -eq 0 ]; then
    echo -e "${RED}❌ Error: Debes especificar el tipo de test a ejecutar${NC}"
    echo -e "${YELLOW}Uso: $0 [backend|frontend|e2e|all]${NC}"
    echo -e "${YELLOW}Ejemplos:${NC}"
    echo -e "${YELLOW}  $0 backend   # Solo tests de backend${NC}"
    echo -e "${YELLOW}  $0 frontend  # Solo tests de frontend${NC}"
    echo -e "${YELLOW}  $0 e2e       # Solo tests E2E${NC}"
    echo -e "${YELLOW}  $0 all       # Todos los tests${NC}"
    exit 1
fi

TEST_TYPE="$1"

# Validar tipo de test
if [[ ! "$TEST_TYPE" =~ ^(backend|frontend|e2e|all)$ ]]; then
    echo -e "${RED}❌ Error: Tipo de test inválido: $TEST_TYPE${NC}"
    echo -e "${YELLOW}Tipos válidos: backend, frontend, e2e, all${NC}"
    exit 1
fi

print_header "🧪 Ejecutor de Tests Docker - CronosMatic"
echo -e "${YELLOW}Tipo de test: $TEST_TYPE${NC}"

# 1. Verificar Docker
check_docker

# 2. Verificar contenedores
check_containers

# 3. Ejecutar tests en el contenedor
print_header "Ejecutando Tests en Docker"

# Ejecutar el script de configuración y tests dentro del contenedor
if run_in_container "./docker/test-setup.sh $TEST_TYPE"; then
    echo ""
    echo -e "${GREEN}🎉 Tests ejecutados exitosamente en Docker!${NC}"
    echo -e "${GREEN}✨ Todos los tests del tipo '$TEST_TYPE' pasaron correctamente${NC}"
else
    echo ""
    echo -e "${RED}❌ Error: Los tests fallaron${NC}"
    echo -e "${YELLOW}💡 Revisa los logs arriba para más detalles${NC}"
    echo -e "${YELLOW}💡 Para debugging, puedes acceder al contenedor con:${NC}"
    echo -e "${YELLOW}   docker compose exec dev bash${NC}"
    exit 1
fi

# 4. Mostrar información adicional
print_header "Información Adicional"
echo -e "${BLUE}📊 Contenedores activos:${NC}"
docker compose ps

echo ""
echo -e "${BLUE}🔗 Servicios disponibles:${NC}"
echo -e "${YELLOW}  • Aplicación (dev): http://localhost:3000${NC}"
echo -e "${YELLOW}  • Vite (hot reload): http://localhost:5173${NC}"
echo -e "${YELLOW}  • phpMyAdmin: http://localhost:8080${NC}"

echo ""
echo -e "${GREEN}✅ Ejecución de tests en Docker completada${NC}"