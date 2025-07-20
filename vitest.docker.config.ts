/// <reference types="vitest" />
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
  plugins: [react()],
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./resources/js/test-setup.ts'],
    include: ['resources/js/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}'],
    
    // Configuraciones específicas para Docker
    testTimeout: 15000, // Aumentado para Docker
    hookTimeout: 15000, // Aumentado para Docker
    teardownTimeout: 15000, // Aumentado para Docker
    
    // Pool de workers optimizado para Docker
    pool: 'forks',
    poolOptions: {
      forks: {
        minForks: 1,
        maxForks: 2, // Limitado para Docker
      },
    },
    
    // Configuración de cobertura
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html', 'cobertura'],
      include: ['resources/js/**/*.{js,ts,jsx,tsx}'],
      exclude: [
        'resources/js/**/*.{test,spec}.{js,ts,jsx,tsx}',
        'resources/js/test-setup.ts',
        'resources/js/app.tsx',
        'resources/js/ssr.tsx',
        'resources/js/**/__mocks__/**',
        'resources/js/**/*.d.ts'
      ],
      thresholds: {
        global: {
          branches: 70,
          functions: 70,
          lines: 70,
          statements: 70
        }
      },
      // Directorio de salida específico para Docker
      reportsDirectory: './tests/results/coverage'
    },
    
    // Configuración de reportes
    reporters: ['verbose', 'json'],
    outputFile: {
      json: './tests/results/vitest-results.json'
    },
    
    // Variables de entorno para Docker
    env: {
      DOCKER_ENV: 'true',
      NODE_ENV: 'test',
      VITE_APP_ENV: 'testing'
    }
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/js'),
    },
  },
})