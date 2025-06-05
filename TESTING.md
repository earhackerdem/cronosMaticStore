# 🧪 Testing Guide - CronosMatic

Esta guía describe la estrategia de testing implementada en el proyecto CronosMatic, incluyendo unit tests, integration tests y end-to-end tests.

## 📋 Tabla de Contenidos

- [Configuración](#configuración)
- [Unit Tests](#unit-tests)
- [Integration Tests](#integration-tests)
- [End-to-End Tests](#end-to-end-tests)
- [Coverage Reports](#coverage-reports)
- [Comandos Disponibles](#comandos-disponibles)
- [Mejores Prácticas](#mejores-prácticas)

## 🛠️ Configuración

### Herramientas Utilizadas

- **Vitest**: Framework de testing para unit e integration tests
- **React Testing Library**: Utilidades para testing de componentes React
- **Cypress**: Framework para end-to-end testing
- **@vitest/coverage-v8**: Generación de reportes de coverage

### Instalación

Las dependencias de testing ya están incluidas en el proyecto:

```bash
npm install
```

## 🔬 Unit Tests

Los unit tests se enfocan en probar componentes individuales de forma aislada.

### Ubicación
```
resources/js/__tests__/components/ui/
```

### Ejemplos Implementados

#### LoadingSpinner Component
- ✅ Renderizado con props por defecto
- ✅ Renderizado con diferentes tamaños
- ✅ Atributos de accesibilidad correctos

#### Button Component
- ✅ Renderizado con props por defecto
- ✅ Manejo de eventos de click
- ✅ Diferentes variantes (default, destructive, outline, etc.)
- ✅ Diferentes tamaños (sm, md, lg, icon)
- ✅ Estado disabled
- ✅ Aplicación de clases CSS personalizadas

### Ejecutar Unit Tests

```bash
# Ejecutar todos los unit tests
npm run test

# Ejecutar tests específicos
npm run test:run loading-spinner
npm run test:run button

# Modo watch (desarrollo)
npm run test
```

## 🔗 Integration Tests

Los integration tests verifican la interacción entre múltiples componentes.

### Ubicación
```
resources/js/__tests__/pages/Products/
```

### Ejemplos Implementados

#### Products Index Page
- ✅ Renderizado correcto de la lista de productos
- ✅ Visualización de estados de stock
- ✅ Elementos de funcionalidad de búsqueda
- ✅ Información de paginación
- ✅ Enlaces a detalles de productos
- ✅ Contenedor de grilla de productos

### Ejecutar Integration Tests

```bash
# Ejecutar integration tests
npm run test:run Index.test
```

## 🌐 End-to-End Tests

Los tests E2E verifican el flujo completo de la aplicación desde la perspectiva del usuario.

### Ubicación
```
cypress/e2e/
```

### Ejemplos Implementados

#### Products E2E Tests
- ✅ Visualización de la página del catálogo
- ✅ Funcionalidad de búsqueda
- ✅ Filtros de categoría
- ✅ Alternancia entre vistas (grilla/lista)
- ✅ Navegación a detalles de producto
- ✅ Información de paginación
- ✅ Diseño responsivo
- ✅ Manejo de resultados vacíos

### Ejecutar E2E Tests

```bash
# Ejecutar tests E2E en modo headless
npm run test:e2e

# Abrir interfaz de Cypress
npm run test:e2e:open

# Comandos alternativos
npm run cypress:run
npm run cypress:open
```

### Prerequisitos para E2E Tests

1. **Servidor Laravel ejecutándose**:
   ```bash
   php artisan serve
   ```

2. **Base de datos con datos de prueba**:
   ```bash
   php artisan migrate:fresh --seed
   ```

3. **Servidor Vite ejecutándose** (opcional, para desarrollo):
   ```bash
   npm run dev
   ```

## 📊 Coverage Reports

### Generar Reportes

```bash
# Generar reporte de coverage
npm run test:coverage
```

### Visualizar Reportes

Los reportes se generan en formato HTML y se pueden visualizar en:
```
coverage/index.html
```

### Umbrales de Coverage

Configurados en `vitest.config.ts`:
- **Branches**: 70%
- **Functions**: 70%
- **Lines**: 70%
- **Statements**: 70%

## 🚀 Comandos Disponibles

### Testing Commands

```bash
# Unit & Integration Tests
npm run test              # Modo watch
npm run test:run          # Ejecutar una vez
npm run test:ui           # Interfaz web de Vitest
npm run test:coverage     # Con reporte de coverage

# End-to-End Tests
npm run test:e2e          # Ejecutar E2E tests
npm run test:e2e:open     # Abrir interfaz de Cypress
npm run cypress:run       # Ejecutar Cypress
npm run cypress:open      # Abrir Cypress

# Otros
npm run lint              # Linting
npm run format            # Formateo de código
npm run types             # Verificación de tipos
```

## 📝 Mejores Prácticas

### Unit Tests

1. **Aislamiento**: Cada test debe ser independiente
2. **Mocking**: Usar mocks para dependencias externas
3. **Descriptivos**: Nombres de tests claros y descriptivos
4. **AAA Pattern**: Arrange, Act, Assert

```typescript
it('should render with custom size', () => {
  // Arrange
  render(<LoadingSpinner size="lg" />)
  
  // Act
  const spinner = screen.getByTestId('loading-spinner')
  
  // Assert
  expect(spinner).toHaveClass('w-8', 'h-8')
})
```

### Integration Tests

1. **Flujos reales**: Probar interacciones entre componentes
2. **Datos mock**: Usar datos realistas pero controlados
3. **Estados**: Verificar diferentes estados de la aplicación

### E2E Tests

1. **Flujos de usuario**: Simular acciones reales del usuario
2. **Datos de prueba**: Asegurar datos consistentes
3. **Esperas**: Usar esperas apropiadas para elementos dinámicos
4. **Selectores estables**: Usar data-testid o aria-labels

```typescript
cy.get('[data-testid="products-grid"]').should('be.visible')
cy.get('[aria-label="Vista de lista"]').click()
```

### Estructura de Archivos

```
resources/js/__tests__/
├── components/
│   └── ui/
│       ├── button.test.tsx
│       └── loading-spinner.test.tsx
├── pages/
│   └── Products/
│       └── Index.test.tsx
└── utils/
    └── test-utils.tsx

cypress/
├── e2e/
│   └── products.cy.ts
├── support/
│   ├── commands.ts
│   └── e2e.ts
└── fixtures/
```

## 🔧 Configuración Avanzada

### Vitest Configuration

Ver `vitest.config.ts` para configuración detallada:
- Entorno jsdom
- Alias de paths
- Setup files
- Coverage settings

### Cypress Configuration

Ver `cypress.config.ts` para configuración:
- Base URL
- Timeouts
- Viewport settings
- Screenshot settings

## 🐛 Troubleshooting

### Problemas Comunes

1. **Tests E2E fallan**: Verificar que el servidor Laravel esté ejecutándose
2. **Coverage bajo**: Revisar archivos excluidos en configuración
3. **Timeouts**: Ajustar timeouts en configuración de Cypress
4. **Mocks no funcionan**: Verificar configuración de mocks en test-setup.ts

### Debugging

```bash
# Debug tests con UI
npm run test:ui

# Debug E2E tests
npm run test:e2e:open
```

## 📈 Métricas Actuales

### Coverage Report (Última ejecución)
- **Test Files**: 3 passed
- **Tests**: 17 passed
- **Components cubiertos**: LoadingSpinner (100%), Button (100%)
- **Coverage general**: Mejorando progresivamente

### Tests E2E
- **Scenarios cubiertos**: 10 escenarios principales
- **Responsive testing**: Desktop, Tablet, Mobile
- **Cross-browser**: Configurado para Chrome (extensible)

---

## 🎯 Próximos Pasos

1. **Expandir coverage**: Agregar tests para más componentes
2. **Visual testing**: Considerar herramientas como Percy o Chromatic
3. **Performance testing**: Agregar tests de rendimiento
4. **API testing**: Tests de integración con endpoints
5. **Accessibility testing**: Tests automatizados de a11y

---

**Nota**: Esta documentación se actualiza conforme se agregan nuevos tests y funcionalidades al proyecto. 
