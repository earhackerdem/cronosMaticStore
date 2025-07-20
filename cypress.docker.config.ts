import { defineConfig } from 'cypress'

export default defineConfig({
  e2e: {
    baseUrl: 'http://localhost:3000',
    supportFile: 'cypress/support/e2e.ts',
    specPattern: 'cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
    viewportWidth: 1280,
    viewportHeight: 720,
    video: false,
    screenshotOnRunFailure: true,
    defaultCommandTimeout: 15000, // Aumentado para Docker
    requestTimeout: 15000, // Aumentado para Docker
    responseTimeout: 15000, // Aumentado para Docker
    pageLoadTimeout: 45000, // Aumentado para Docker
    chromeWebSecurity: false,
    
    // Configuraciones específicas para Docker
    retries: {
      runMode: 2, // Reintentos en modo headless
      openMode: 0, // Sin reintentos en modo interactivo
    },
    
    // Variables de entorno para Docker
    env: {
      DOCKER_ENV: true,
      API_BASE_URL: 'http://localhost:3000/api',
    },
    
    setupNodeEvents(on, config) {
      // Configuraciones específicas para Docker
      on('before:browser:launch', (browser, launchOptions) => {
        if (browser.name === 'chrome' || browser.name === 'chromium') {
          // Configuraciones adicionales para Chrome en Docker
          launchOptions.args.push('--no-sandbox')
          launchOptions.args.push('--disable-dev-shm-usage')
          launchOptions.args.push('--disable-gpu')
        }
        
        return launchOptions
      })
      
      // Manejar tareas específicas para Docker
      on('task', {
        log(message) {
          console.log(message)
          return null
        },
        
        // Tarea para esperar a que el servidor esté listo
        waitForServer() {
          return new Promise((resolve) => {
            const maxAttempts = 30
            let attempts = 0
            
            const checkServer = () => {
              attempts++
              fetch('http://localhost:3000')
                .then(() => resolve(true))
                .catch(() => {
                  if (attempts < maxAttempts) {
                    setTimeout(checkServer, 1000)
                  } else {
                    resolve(false)
                  }
                })
            }
            
            checkServer()
          })
        },
      })
      
      return config
    },
  },
  
  component: {
    devServer: {
      framework: 'react',
      bundler: 'vite',
    },
    supportFile: 'cypress/support/component.ts',
    specPattern: 'cypress/component/**/*.cy.{js,jsx,ts,tsx}',
  },
})