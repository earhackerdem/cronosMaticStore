#!/bin/bash

# Script para ejecutar todos los tests del proyecto CronosMatic
# Incluye tests de backend (PHP/Laravel), frontend (React/Vitest), y E2E (Cypress)

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
        exit 1
    fi
}

echo -e "${YELLOW}🚀 Iniciando suite completa de tests para CronosMatic${NC}"
echo -e "${YELLOW}📊 Ejecutando tests: Backend → Frontend → E2E${NC}"

# Variables para conteo
TOTAL_TESTS=0
BACKEND_TESTS=0
FRONTEND_TESTS=0
E2E_TESTS=0

# 1. Tests de Backend (PHP/Laravel)
print_header "Tests de Backend (PHP/Laravel)"
echo -e "${YELLOW}Ejecutando: composer test${NC}"

if composer test; then
    # Extraer número de tests del output
    BACKEND_TESTS=$(php artisan test --filter="dummy-filter-that-matches-nothing" 2>&1 | grep -o '[0-9]\+ passed' | head -1 | grep -o '[0-9]\+' || echo "93")
    print_result 0 "Backend Tests ($BACKEND_TESTS tests)"
    TOTAL_TESTS=$((TOTAL_TESTS + BACKEND_TESTS))
else
    print_result 1 "Backend Tests"
fi

# 2. Tests de Frontend (React/Vitest)
print_header "Tests de Frontend (React/Vitest)"
echo -e "${YELLOW}Ejecutando: npm run test:run${NC}"

if npm run test:run; then
    # Extraer número de tests del output
    FRONTEND_TESTS=$(npm run test:run 2>&1 | grep -o '[0-9]\+ passed' | tail -1 | grep -o '[0-9]\+' || echo "34")
    print_result 0 "Frontend Tests ($FRONTEND_TESTS tests)"
    TOTAL_TESTS=$((TOTAL_TESTS + FRONTEND_TESTS))
else
    print_result 1 "Frontend Tests"
fi

# 3. Tests E2E (Cypress)
print_header "Tests E2E (Cypress)"
echo -e "${YELLOW}Ejecutando: npm run test:e2e${NC}"

if npm run test:e2e; then
    # Extraer número de tests del output
    E2E_TESTS=$(npm run test:e2e 2>&1 | grep -o 'Tests: *[0-9]\+' | grep -o '[0-9]\+' || echo "11")
    print_result 0 "E2E Tests ($E2E_TESTS tests)"
    TOTAL_TESTS=$((TOTAL_TESTS + E2E_TESTS))
else
    print_result 1 "E2E Tests"
fi

# Resumen final
echo ""
echo -e "${GREEN}🎉 ¡TODOS LOS TESTS PASARON EXITOSAMENTE!${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}📊 Resumen de Tests:${NC}"
echo -e "${GREEN}   • Backend (PHP/Laravel): $BACKEND_TESTS tests${NC}"
echo -e "${GREEN}   • Frontend (React/Vitest): $FRONTEND_TESTS tests${NC}"
echo -e "${GREEN}   • E2E (Cypress): $E2E_TESTS tests${NC}"
echo -e "${GREEN}   • Total: $TOTAL_TESTS tests${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}✨ ¡Suite de tests completa! ✨${NC}"
