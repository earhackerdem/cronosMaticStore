import { render, RenderOptions } from '@testing-library/react'
import { ReactElement } from 'react'

// Función auxiliar para crear props de productos mock
export const createMockProduct = (overrides: Partial<Record<string, unknown>> = {}) => ({
  id: 1,
  name: 'Reloj de Prueba',
  slug: 'reloj-de-prueba',
  description: 'Un reloj de prueba para testing',
  price: 199.99,
  stock_quantity: 10,
  brand: 'TestBrand',
  movement_type: 'Quartz',
  image_path: null,
  sku: 'TEST001',
  image_url: 'https://img.chrono24.com/images/uhren/40851974-em5oh9xyb3j849bffkxv8rls-Zoom.jpg', // Imagen por defecto para ID=1
  is_active: true,
  created_at: '2024-01-01T00:00:00.000000Z',
  updated_at: '2024-01-01T00:00:00.000000Z',
  category: {
    id: 1,
    name: 'Relojes de Prueba',
    slug: 'relojes-de-prueba'
  },
  ...overrides
})

// Función auxiliar para crear props de categorías mock
export const createMockCategory = (overrides: Partial<Record<string, unknown>> = {}) => ({
  id: 1,
  name: 'Categoría de Prueba',
  slug: 'categoria-de-prueba',
  description: 'Una categoría de prueba',
  image_path: null,
  is_active: true,
  created_at: '2024-01-01T00:00:00.000000Z',
  updated_at: '2024-01-01T00:00:00.000000Z',
  ...overrides
})

// Función auxiliar para crear respuesta paginada mock
export const createMockPaginatedResponse = (data: unknown[], overrides: Partial<Record<string, unknown>> = {}) => ({
  current_page: 1,
  data,
  first_page_url: 'http://localhost:8000/productos?page=1',
  from: 1,
  last_page: 1,
  last_page_url: 'http://localhost:8000/productos?page=1',
  links: [
    { url: null, label: '&laquo; Previous', active: false },
    { url: 'http://localhost:8000/productos?page=1', label: '1', active: true },
    { url: null, label: 'Next &raquo;', active: false }
  ],
  next_page_url: null,
  path: 'http://localhost:8000/productos',
  per_page: 12,
  prev_page_url: null,
  to: data.length,
  total: data.length,
  ...overrides
})

// Función para esperar que un elemento aparezca
export const waitForElement = async (callback: () => HTMLElement | null) => {
  return new Promise((resolve, reject) => {
    const timeout = setTimeout(() => {
      reject(new Error('Element not found within timeout'))
    }, 5000)

    const interval = setInterval(() => {
      const element = callback()
      if (element) {
        clearInterval(interval)
        clearTimeout(timeout)
        resolve(element)
      }
    }, 100)
  })
}

// Función para simular delay
export const delay = (ms: number) => new Promise(resolve => setTimeout(resolve, ms))

// Custom render que puede incluir providers en el futuro
type CustomRenderOptions = Omit<RenderOptions, 'wrapper'>

export const customRender = (
  ui: ReactElement,
  options?: CustomRenderOptions
) => {
  return render(ui, {
    // wrapper: ({ children }) => <CustomProviders>{children}</CustomProviders>,
    ...options,
  })
}

// Re-export everything
export * from '@testing-library/react'

// Override render method
export { customRender as render }
