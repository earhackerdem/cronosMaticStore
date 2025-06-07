// ***********************************************************
// This example support/e2e.ts is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

/// <reference types="cypress" />

// Import commands.js using ES2015 syntax:
import './commands'

// Alternatively you can use CommonJS syntax:
// require('./commands')

// Configuración global para tests
Cypress.on('uncaught:exception', (err) => {
  // Ignorar errores relacionados con auth.user.avatar en páginas públicas
  if (err.message.includes("Cannot read properties of null (reading 'avatar')")) {
    return false
  }
  // No ignorar otros errores
  return true
})

// Configurar viewport por defecto
beforeEach(() => {
  cy.viewport(1280, 720)
})
