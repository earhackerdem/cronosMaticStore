import { render, screen } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import ProductShow from '@/pages/Products/Show';
import { Product, Category } from '@/types';

// Mock de Inertia
vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    Link: ({ href, children, className }: { href: string; children: React.ReactNode; className?: string }) => (
        <a href={href} className={className}>{children}</a>
    ),
    usePage: () => ({
        props: {
            auth: { user: null },
            sidebarOpen: false,
        },
    }),
}));

// Mock de los componentes UI
vi.mock('@/components/ui/button', () => ({
    Button: ({ children, disabled, size, className }: {
        children: React.ReactNode;
        disabled?: boolean;
        size?: string;
        className?: string;
    }) => (
        <button disabled={disabled} className={`btn ${size} ${className}`}>
            {children}
        </button>
    ),
}));

vi.mock('@/components/ui/card', () => ({
    Card: ({ children }: { children: React.ReactNode }) => <div className="card">{children}</div>,
    CardContent: ({ children }: { children: React.ReactNode }) => <div className="card-content">{children}</div>,
    CardHeader: ({ children }: { children: React.ReactNode }) => <div className="card-header">{children}</div>,
    CardTitle: ({ children }: { children: React.ReactNode }) => <h3 className="card-title">{children}</h3>,
}));

vi.mock('@/components/ui/badge', () => ({
    Badge: ({ children, variant }: { children: React.ReactNode; variant?: string }) => <span className={`badge ${variant}`}>{children}</span>,
}));

vi.mock('@/components/ui/separator', () => ({
    Separator: () => <hr className="separator" />,
}));

// Mock del contexto del carrito
vi.mock('@/contexts/CartContext', () => ({
    CartProvider: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    useCart: () => ({
        cart: null,
        isLoading: false,
        error: null,
        addToCart: vi.fn(),
        updateCartItem: vi.fn(),
        removeCartItem: vi.fn(),
        clearCart: vi.fn(),
        refreshCart: vi.fn(),
    }),
}));

// Mock del componente AddToCartButton
vi.mock('@/components/add-to-cart-button', () => ({
    AddToCartButton: ({ product }: { product: Product }) => {
        const isOutOfStock = product.stock_quantity === 0;
        return (
            <button disabled={isOutOfStock} data-testid="add-to-cart-button">
                {isOutOfStock ? 'Producto agotado' : 'Añadir al carrito'}
            </button>
        );
    },
}));



describe('ProductShow', () => {
    const mockCategory: Category = {
        id: 1,
        name: 'Relojes Deportivos',
        slug: 'relojes-deportivos',
        description: 'Relojes para actividades deportivas',
        image_path: null,
        is_active: true,
        created_at: '2024-01-01T00:00:00.000000Z',
        updated_at: '2024-01-01T00:00:00.000000Z',
    };

    const mockProductWithStock: Product = {
        id: 1,
        name: 'Reloj Deportivo Casio G-Shock',
        slug: 'reloj-deportivo-casio-g-shock',
        description: 'Reloj resistente al agua y a los golpes, perfecto para deportes extremos.',
        sku: 'CASIO-GSHOCK-001',
        price: 2500.00,
        stock_quantity: 10,
        brand: 'Casio',
        movement_type: 'Quartz',
        image_path: '/storage/products/casio-gshock.jpg',
        image_url: 'http://localhost/storage/products/casio-gshock.jpg',
        is_active: true,
        category: mockCategory,
        created_at: '2024-01-01T00:00:00.000000Z',
        updated_at: '2024-01-01T00:00:00.000000Z',
    };

    const mockProductOutOfStock: Product = {
        ...mockProductWithStock,
        id: 2,
        name: 'Reloj Agotado',
        slug: 'reloj-agotado',
        stock_quantity: 0,
    };

    const mockProductLowStock: Product = {
        ...mockProductWithStock,
        id: 3,
        name: 'Reloj Pocas Unidades',
        slug: 'reloj-pocas-unidades',
        stock_quantity: 3,
    };

    const mockProductWithoutImage: Product = {
        ...mockProductWithStock,
        id: 4,
        name: 'Reloj Sin Imagen',
        slug: 'reloj-sin-imagen',
        image_path: null,
        image_url: 'https://img.chrono24.com/images/uhren/26900830-3i5ennqwbi0zcqufcqyxjs5v-Zoom.jpg', // Imagen por defecto basada en ID=4
    };

    const mockProductMinimalData: Product = {
        id: 5,
        name: 'Reloj Básico',
        slug: 'reloj-basico',
        description: null,
        sku: null,
        price: 1000.00,
        stock_quantity: 5,
        brand: null,
        movement_type: null,
        image_path: null,
        image_url: 'https://img.chrono24.com/images/uhren/40851974-em5oh9xyb3j849bffkxv8rls-Zoom.jpg', // Imagen por defecto basada en ID=5
        is_active: true,
        category: null,
        created_at: '2024-01-01T00:00:00.000000Z',
        updated_at: '2024-01-01T00:00:00.000000Z',
    };

    describe('Criterios de Aceptación HU1.2', () => {
        it('AC2: Muestra todos los detalles del producto requeridos', () => {
            render(<ProductShow product={mockProductWithStock} />);

            // Verificar nombre completo
            expect(screen.getByText('Reloj Deportivo Casio G-Shock')).toBeInTheDocument();

            // Verificar descripción detallada
            expect(screen.getByText('Reloj resistente al agua y a los golpes, perfecto para deportes extremos.')).toBeInTheDocument();

            // Verificar precio en MXN
            expect(screen.getByText('$2,500.00')).toBeInTheDocument();

            // Verificar marca
            expect(screen.getAllByText('Marca:')).toHaveLength(2); // Una en el encabezado y otra en información adicional
            expect(screen.getAllByText('Casio')).toHaveLength(2); // Una en el encabezado y otra en información adicional

            // Verificar tipo de movimiento
            expect(screen.getByText('Movimiento: Quartz')).toBeInTheDocument();
            expect(screen.getByText(/Tipo de movimiento:/)).toBeInTheDocument();

            // Verificar disponibilidad de stock
            expect(screen.getByText('En stock (10)')).toBeInTheDocument();

            // Verificar imagen
            const image = screen.getByAltText('Reloj Deportivo Casio G-Shock');
            expect(image).toBeInTheDocument();
            expect(image).toHaveAttribute('src', 'http://localhost/storage/products/casio-gshock.jpg');

            // Verificar categoría
            expect(screen.getAllByText('Relojes Deportivos')).toHaveLength(2); // Una en badge y otra en información adicional

            // Verificar SKU
            expect(screen.getByText('CASIO-GSHOCK-001')).toBeInTheDocument();
        });

        it('AC3: Maneja producto sin imagen mostrando imagen por defecto', () => {
            render(<ProductShow product={mockProductWithoutImage} />);

            // Verificar que muestra la imagen por defecto en lugar de "Sin imagen disponible"
            const image = screen.getByAltText('Reloj Sin Imagen');
            expect(image).toBeInTheDocument();
            expect(image).toHaveAttribute('src', 'https://img.chrono24.com/images/uhren/26900830-3i5ennqwbi0zcqufcqyxjs5v-Zoom.jpg');

            // Ya no debe mostrar "Sin imagen disponible"
            expect(screen.queryByText('Sin imagen disponible')).not.toBeInTheDocument();
        });

        it('Maneja producto con datos mínimos', () => {
            render(<ProductShow product={mockProductMinimalData} />);

            // Verificar que muestra el nombre y precio
            expect(screen.getByText('Reloj Básico')).toBeInTheDocument();
            expect(screen.getByText('$1,000.00')).toBeInTheDocument();

            // Verificar que no muestra campos opcionales cuando son null
            expect(screen.queryByText(/Marca:/)).not.toBeInTheDocument();
            expect(screen.queryByText(/Movimiento:/)).not.toBeInTheDocument();
            expect(screen.queryByText(/SKU:/)).not.toBeInTheDocument();
            expect(screen.queryByText(/Descripción/)).not.toBeInTheDocument();
        });
    });

    describe('Criterios de Aceptación HU1.5', () => {
        it('AC1: Muestra indicación visual clara del estado del stock - En Stock', () => {
            render(<ProductShow product={mockProductWithStock} />);

            const stockIndicator = screen.getByText('En stock (10)');
            expect(stockIndicator).toBeInTheDocument();
            expect(stockIndicator.closest('div')).toHaveClass('text-green-600');
        });

        it('AC1: Muestra indicación visual clara del estado del stock - Pocas Unidades', () => {
            render(<ProductShow product={mockProductLowStock} />);

            const stockIndicator = screen.getByText('Pocas unidades (3)');
            expect(stockIndicator).toBeInTheDocument();
            expect(stockIndicator.closest('div')).toHaveClass('text-orange-600');
        });

        it('AC1: Muestra indicación visual clara del estado del stock - Agotado', () => {
            render(<ProductShow product={mockProductOutOfStock} />);

            const stockIndicator = screen.getByText('Agotado');
            expect(stockIndicator).toBeInTheDocument();
            expect(stockIndicator.closest('div')).toHaveClass('text-red-600');
        });

        it('AC2: Botón "Añadir al Carrito" deshabilitado cuando producto está agotado', () => {
            render(<ProductShow product={mockProductOutOfStock} />);

            const addToCartButton = screen.getByTestId('add-to-cart-button');
            expect(addToCartButton).toBeInTheDocument();
            expect(addToCartButton).toBeDisabled();
            expect(addToCartButton).toHaveTextContent('Producto agotado');
        });

        it('AC2: Botón "Añadir al Carrito" habilitado cuando producto tiene stock', () => {
            render(<ProductShow product={mockProductWithStock} />);

            const addToCartButton = screen.getByText('Añadir al carrito');
            expect(addToCartButton).toBeInTheDocument();
            expect(addToCartButton).not.toBeDisabled();
        });
    });

    describe('Funcionalidad adicional', () => {
        it('Muestra el título correcto en el Head', () => {
            render(<ProductShow product={mockProductWithStock} />);

            expect(document.title).toBe('Reloj Deportivo Casio G-Shock - CronosMatic');
        });

        it('Muestra enlace de navegación de vuelta al catálogo', () => {
            render(<ProductShow product={mockProductWithStock} />);

            const backLink = screen.getByText('Volver al catálogo');
            expect(backLink).toBeInTheDocument();
            expect(backLink.closest('a')).toHaveAttribute('href', '/productos');
        });

        it('Muestra información adicional en cards (Garantía, Envío, Calidad)', () => {
            render(<ProductShow product={mockProductWithStock} />);

            expect(screen.getByText('Garantía')).toBeInTheDocument();
            expect(screen.getByText('Envío')).toBeInTheDocument();
            expect(screen.getByText('Calidad')).toBeInTheDocument();

            expect(screen.getByText('Garantía de fábrica incluida en todos nuestros productos')).toBeInTheDocument();
            expect(screen.getByText('Envío seguro a toda la República Mexicana')).toBeInTheDocument();
            expect(screen.getByText('Productos de alta calidad seleccionados cuidadosamente')).toBeInTheDocument();
        });

        it('Muestra sección de productos relacionados', () => {
            render(<ProductShow product={mockProductWithStock} />);

            expect(screen.getByText('Productos relacionados')).toBeInTheDocument();
            expect(screen.getByText('Próximamente: productos relacionados y recomendaciones')).toBeInTheDocument();
            expect(screen.getByText('Ver más productos')).toBeInTheDocument();
        });

        it('Formatea correctamente el precio en MXN', () => {
            render(<ProductShow product={mockProductWithStock} />);

            // El precio debe estar formateado como moneda mexicana
            expect(screen.getByText('$2,500.00')).toBeInTheDocument();
        });

        it('Muestra mensaje de disponibilidad para entrega cuando hay stock', () => {
            render(<ProductShow product={mockProductWithStock} />);

            expect(screen.getByText('Disponible para entrega inmediata')).toBeInTheDocument();
        });

        it('No muestra mensaje de disponibilidad cuando no hay stock', () => {
            render(<ProductShow product={mockProductOutOfStock} />);

            expect(screen.queryByText('Disponible para entrega inmediata')).not.toBeInTheDocument();
        });
    });

    describe('Estructura y Layout', () => {
        it('Renderiza la estructura básica de la página', () => {
            render(<ProductShow product={mockProductWithStock} />);

            // Verificar que se renderiza el contenedor principal
            expect(screen.getByText('Reloj Deportivo Casio G-Shock').closest('.min-h-screen')).toBeInTheDocument();
        });

        it('Muestra badges para categoría y tipo de movimiento', () => {
            render(<ProductShow product={mockProductWithStock} />);

            const categoryBadges = screen.getAllByText('Relojes Deportivos');
            const movementBadge = screen.getByText('Movimiento: Quartz');

            expect(categoryBadges).toHaveLength(2); // Una en badge y otra en información adicional
            expect(movementBadge).toBeInTheDocument();
            expect(categoryBadges[0].closest('.badge')).toBeInTheDocument();
            expect(movementBadge.closest('.badge')).toBeInTheDocument();
        });
    });
});
