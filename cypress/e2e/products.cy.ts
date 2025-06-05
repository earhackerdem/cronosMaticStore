/// <reference types="cypress" />

describe('Products E2E Tests', () => {
  beforeEach(() => {
    // Visitar la página de productos antes de cada test
    cy.visit('/productos')
  })

  it('should display the products catalog page', () => {
    // Verificar que el título esté presente
    cy.contains('Catálogo de Relojes').should('be.visible')
    cy.contains('Descubre nuestra colección de relojes de alta calidad').should('be.visible')

    // Verificar que haya productos en la página
    cy.get('[data-testid="products-grid"]').should('exist')

    // Verificar que se muestren algunos productos (asumiendo que hay datos de prueba)
    cy.get('[data-testid="products-grid"]').children().should('have.length.greaterThan', 0)
  })

  it('should have search functionality', () => {
    // Verificar que el campo de búsqueda esté presente
    cy.get('input[placeholder="Buscar productos..."]').should('be.visible')

    // Verificar que los botones de filtros estén presentes
    cy.contains('Aplicar filtros').should('be.visible')
    cy.contains('Limpiar filtros').should('be.visible')
  })

  it('should have category filter', () => {
    // Verificar que el select de categorías esté presente
    cy.contains('Todas las categorías').should('be.visible')
  })

  it('should have view mode toggles', () => {
    // Verificar que los botones de vista estén presentes
    cy.get('[aria-label="Vista de grilla"]').should('be.visible')
    cy.get('[aria-label="Vista de lista"]').should('be.visible')
  })

  it('should switch between grid and list view', () => {
    // Por defecto debería estar en vista de grilla
    cy.get('[data-testid="products-grid"]').should('be.visible')

    // Cambiar a vista de lista
    cy.get('[aria-label="Vista de lista"]').click()

    // Verificar que cambió a vista de lista
    cy.get('[data-testid="products-list"]').should('be.visible')

    // Cambiar de vuelta a vista de grilla
    cy.get('[aria-label="Vista de grilla"]').click()

    // Verificar que volvió a vista de grilla
    cy.get('[data-testid="products-grid"]').should('be.visible')
  })

  it('should perform search', () => {
    // Escribir en el campo de búsqueda
    cy.get('input[placeholder="Buscar productos..."]').type('reloj')

    // Presionar Enter para buscar
    cy.get('input[placeholder="Buscar productos..."]').type('{enter}')

    // Verificar que la URL cambió con el parámetro de búsqueda
    cy.url().should('include', 'search=reloj')
  })

  it('should clear filters', () => {
    // Escribir algo en la búsqueda
    cy.get('input[placeholder="Buscar productos..."]').type('test')

    // Hacer clic en limpiar filtros
    cy.contains('Limpiar filtros').click()

    // Verificar que el campo de búsqueda se limpió
    cy.get('input[placeholder="Buscar productos..."]').should('have.value', '')
  })

  it('should navigate to product detail', () => {
    // Hacer clic en el primer enlace "Ver detalles" (si existe)
    cy.get('a').contains('Ver detalles').first().click()

    // Verificar que navegó a una página de detalle de producto
    cy.url().should('include', '/productos/')
    cy.url().should('not.equal', Cypress.config().baseUrl + '/productos')
  })

  it('should display pagination info', () => {
    // Verificar que se muestre información de paginación
    cy.contains(/Mostrando \d+ - \d+ de \d+ productos/).should('be.visible')
  })

  it('should be responsive', () => {
    // Probar en diferentes tamaños de pantalla

    // Desktop
    cy.viewport(1280, 720)
    cy.get('[data-testid="products-grid"]').should('be.visible')

    // Tablet
    cy.viewport(768, 1024)
    cy.get('[data-testid="products-grid"]').should('be.visible')

    // Mobile
    cy.viewport(375, 667)
    cy.get('[data-testid="products-grid"]').should('be.visible')
  })

  it('should handle empty search results gracefully', () => {
    // Buscar algo que probablemente no exista
    cy.get('input[placeholder="Buscar productos..."]').type('xyzabc123nonexistent')
    cy.get('input[placeholder="Buscar productos..."]').type('{enter}')

    // La página debería cargar sin errores (no verificamos contenido específico
    // porque depende de los datos de prueba)
    cy.get('body').should('be.visible')
  })
})
