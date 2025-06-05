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
      }
    }
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/js'),
    },
  },
})
