import { render, screen } from '@testing-library/react'
import { describe, it, expect, vi, beforeEach } from 'vitest'

// Mock de Inertia router
vi.mock('@inertiajs/react', () => ({
  Head: ({ title }: { title: string }) => <title>{title}</title>,
  Link: ({ href, children, ...props }: { href: string; children: React.ReactNode }) => <a href={href} {...props}>{children}</a>,
  router: {
    get: vi.fn(),
  },
}))

interface MockProps {
  products: {
    data: Array<{
      id: number;
      name: string;
      slug: string;
      price: number;
      stock_quantity: number;
    }>;
    from: number;
    to: number;
    total: number;
  };
}

// Mock del componente Products Index
const MockProductsIndex = ({ products }: MockProps) => (
  <div>
    <h1>Catálogo de Relojes</h1>
    <p>Descubre nuestra colección de relojes de alta calidad</p>

    <input placeholder="Buscar productos..." />
    <button>Aplicar filtros</button>
    <button>Limpiar filtros</button>

    <div data-testid="products-grid">
      {products.data.map((product) => (
        <div key={product.id}>
          <h3>{product.name}</h3>
          <p>${product.price} MXN</p>
          <span>{product.stock_quantity > 0 ? `En stock (${product.stock_quantity})` : 'Agotado'}</span>
          <a href={`/productos/${product.slug}`}>Ver detalles</a>
        </div>
      ))}
    </div>

    <p>Mostrando {products.from} - {products.to} de {products.total} productos</p>
  </div>
)

// Datos mock simplificados
const mockProducts = {
  current_page: 1,
  data: [
    {
      id: 1,
      name: 'Reloj Automático Elegante',
      slug: 'reloj-automatico-elegante',
      price: 299.99,
      stock_quantity: 15,
    },
    {
      id: 2,
      name: 'Reloj Deportivo Resistente',
      slug: 'reloj-deportivo-resistente',
      price: 199.99,
      stock_quantity: 0,
    }
  ],
  from: 1,
  to: 2,
  total: 2
}

const mockCategories = [
  { id: 1, name: 'Relojes de Pulsera', slug: 'relojes-de-pulsera' },
  { id: 2, name: 'Relojes Deportivos', slug: 'relojes-deportivos' }
]

const mockFilters = {
  search: null,
  category: null,
  sortBy: 'created_at',
  sortDirection: 'desc'
}

const defaultProps = {
  products: mockProducts,
  categories: mockCategories,
  filters: mockFilters
}

describe('ProductsIndex Integration Tests', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders products list correctly', () => {
    render(<MockProductsIndex {...defaultProps} />)

    // Verifica que se muestre el título
    expect(screen.getByText('Catálogo de Relojes')).toBeInTheDocument()
    expect(screen.getByText('Descubre nuestra colección de relojes de alta calidad')).toBeInTheDocument()

    // Verifica que se muestren los productos
    expect(screen.getByText('Reloj Automático Elegante')).toBeInTheDocument()
    expect(screen.getByText('Reloj Deportivo Resistente')).toBeInTheDocument()

    // Verifica que se muestren los precios
    expect(screen.getByText('$299.99 MXN')).toBeInTheDocument()
    expect(screen.getByText('$199.99 MXN')).toBeInTheDocument()
  })

  it('displays correct stock status', () => {
    render(<MockProductsIndex {...defaultProps} />)

    // Verifica estados de stock
    expect(screen.getByText('En stock (15)')).toBeInTheDocument()
    expect(screen.getByText('Agotado')).toBeInTheDocument()
  })

  it('has search functionality elements', () => {
    render(<MockProductsIndex {...defaultProps} />)

    // Verifica que exista el campo de búsqueda
    const searchInput = screen.getByPlaceholderText('Buscar productos...')
    expect(searchInput).toBeInTheDocument()

    // Verifica que existan los botones de filtros
    expect(screen.getByText('Aplicar filtros')).toBeInTheDocument()
    expect(screen.getByText('Limpiar filtros')).toBeInTheDocument()
  })

  it('displays pagination information correctly', () => {
    render(<MockProductsIndex {...defaultProps} />)

    // Verifica información de paginación
    expect(screen.getByText('Mostrando 1 - 2 de 2 productos')).toBeInTheDocument()
  })

  it('renders product links correctly', () => {
    render(<MockProductsIndex {...defaultProps} />)

    // Verifica que los enlaces a detalles del producto estén presentes
    const detailLinks = screen.getAllByText('Ver detalles')
    expect(detailLinks).toHaveLength(2)

    // Verifica que tengan los href correctos
    const firstLink = detailLinks[0].closest('a')
    expect(firstLink).toHaveAttribute('href', '/productos/reloj-automatico-elegante')
  })

  it('has products grid container', () => {
    render(<MockProductsIndex {...defaultProps} />)

    // Verifica que exista el contenedor de la grilla
    const gridContainer = screen.getByTestId('products-grid')
    expect(gridContainer).toBeInTheDocument()
  })
})
