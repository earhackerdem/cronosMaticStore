import { render, screen, fireEvent, act } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import CartIndex from '@/pages/Cart/Index';
import { useCart } from '@/contexts/CartContext';
import { Cart, CartItem, Product } from '@/types';

// Mock del contexto del carrito
vi.mock('@/contexts/CartContext');
const mockUseCart = vi.mocked(useCart);

// Mock de Inertia
vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    Link: ({ children, href, className }: { children: React.ReactNode; href: string; className?: string }) => (
        <a href={href} className={className}>
            {children}
        </a>
    ),
    usePage: () => ({
        props: {
            auth: { user: null },
            sidebarOpen: false,
        },
    }),
}));

const mockProduct: Product = {
    id: 1,
    name: 'Reloj Test',
    slug: 'reloj-test',
    description: 'Un reloj de prueba',
    sku: 'TEST-001',
    price: 1500,
    stock_quantity: 10,
    brand: 'Test Brand',
    movement_type: 'Automático',
    image_path: null,
    image_url: null,
    is_active: true,
    category: null,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
};

const mockCartItem: CartItem = {
    id: 1,
    cart_id: 1,
    product_id: 1,
    quantity: 2,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
    product: mockProduct,
};

const mockCart: Cart = {
    id: 1,
    user_id: 1,
    session_id: null,
    total_amount: 3000,
    total_items: 2,
    expires_at: null,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
    items: [mockCartItem],
};

describe('CartIndex', () => {
    const mockUpdateCartItem = vi.fn();
    const mockRemoveCartItem = vi.fn();
    const mockClearCart = vi.fn();

    beforeEach(() => {
        vi.clearAllMocks();
        // Mock window.confirm
        Object.defineProperty(window, 'confirm', {
            writable: true,
            value: vi.fn(() => true),
        });
    });

    it('muestra estado de carga cuando isLoading es true y no hay carrito', () => {
        mockUseCart.mockReturnValue({
            cart: null,
            isLoading: true,
            error: null,
            addToCart: vi.fn(),
            updateCartItem: mockUpdateCartItem,
            removeCartItem: mockRemoveCartItem,
            clearCart: mockClearCart,
            refreshCart: vi.fn(),
        });

        render(<CartIndex />);

        expect(screen.getByText('Cargando carrito...')).toBeInTheDocument();
    });

    it('muestra mensaje de carrito vacío cuando no hay items', () => {
        const emptyCart: Cart = {
            ...mockCart,
            items: [],
            total_items: 0,
            total_amount: 0,
        };

        mockUseCart.mockReturnValue({
            cart: emptyCart,
            isLoading: false,
            error: null,
            addToCart: vi.fn(),
            updateCartItem: mockUpdateCartItem,
            removeCartItem: mockRemoveCartItem,
            clearCart: mockClearCart,
            refreshCart: vi.fn(),
        });

        render(<CartIndex />);

        expect(screen.getByText('Tu carrito está vacío')).toBeInTheDocument();
        expect(screen.getByText('Ver productos')).toBeInTheDocument();
    });

    it('muestra los items del carrito correctamente', () => {
        mockUseCart.mockReturnValue({
            cart: mockCart,
            isLoading: false,
            error: null,
            addToCart: vi.fn(),
            updateCartItem: mockUpdateCartItem,
            removeCartItem: mockRemoveCartItem,
            clearCart: mockClearCart,
            refreshCart: vi.fn(),
        });

        render(<CartIndex />);

        expect(screen.getByText('Reloj Test')).toBeInTheDocument();
        expect(screen.getByText('Test Brand')).toBeInTheDocument();
        expect(screen.getByTestId('item-quantity')).toHaveTextContent('2'); // cantidad específica del item
        expect(screen.getAllByText('$3,000.00')).toHaveLength(3); // aparece en item, subtotal y total
        expect(screen.getByText('$1,500.00 c/u')).toBeInTheDocument(); // precio unitario
    });

    it('muestra el resumen del pedido correctamente', () => {
        mockUseCart.mockReturnValue({
            cart: mockCart,
            isLoading: false,
            error: null,
            addToCart: vi.fn(),
            updateCartItem: mockUpdateCartItem,
            removeCartItem: mockRemoveCartItem,
            clearCart: mockClearCart,
            refreshCart: vi.fn(),
        });

        render(<CartIndex />);

        expect(screen.getByText('Resumen del pedido')).toBeInTheDocument();
        expect(screen.getByText('Subtotal (2 artículos)')).toBeInTheDocument();
        expect(screen.getByText('Gratis')).toBeInTheDocument(); // envío
        expect(screen.getByText('Proceder al pago')).toBeInTheDocument();
    });

    it('permite incrementar la cantidad de un item', async () => {
        mockUseCart.mockReturnValue({
            cart: mockCart,
            isLoading: false,
            error: null,
            addToCart: vi.fn(),
            updateCartItem: mockUpdateCartItem,
            removeCartItem: mockRemoveCartItem,
            clearCart: mockClearCart,
            refreshCart: vi.fn(),
        });

        render(<CartIndex />);

                const incrementButton = screen.getAllByRole('button').find(btn =>
            btn.querySelector('svg')?.classList.contains('lucide-plus')
        );

        if (incrementButton) {
            await act(async () => {
                fireEvent.click(incrementButton);
            });
            expect(mockUpdateCartItem).toHaveBeenCalledWith(1, 3);
        }
    });

    it('permite decrementar la cantidad de un item', async () => {
        mockUseCart.mockReturnValue({
            cart: mockCart,
            isLoading: false,
            error: null,
            addToCart: vi.fn(),
            updateCartItem: mockUpdateCartItem,
            removeCartItem: mockRemoveCartItem,
            clearCart: mockClearCart,
            refreshCart: vi.fn(),
        });

        render(<CartIndex />);

                const decrementButton = screen.getAllByRole('button').find(btn =>
            btn.querySelector('svg')?.classList.contains('lucide-minus')
        );

        if (decrementButton) {
            await act(async () => {
                fireEvent.click(decrementButton);
            });
            expect(mockUpdateCartItem).toHaveBeenCalledWith(1, 1);
        }
    });

    it('permite eliminar un item del carrito', async () => {
        mockUseCart.mockReturnValue({
            cart: mockCart,
            isLoading: false,
            error: null,
            addToCart: vi.fn(),
            updateCartItem: mockUpdateCartItem,
            removeCartItem: mockRemoveCartItem,
            clearCart: mockClearCart,
            refreshCart: vi.fn(),
        });

        render(<CartIndex />);

        const deleteButtons = screen.getAllByRole('button').filter(btn =>
            btn.querySelector('svg')?.classList.contains('lucide-trash-2')
        );

        if (deleteButtons.length > 0) {
            await act(async () => {
                fireEvent.click(deleteButtons[0]);
            });
            expect(mockRemoveCartItem).toHaveBeenCalledWith(1);
        }
    });

    it('permite vaciar el carrito completo', async () => {
        mockUseCart.mockReturnValue({
            cart: mockCart,
            isLoading: false,
            error: null,
            addToCart: vi.fn(),
            updateCartItem: mockUpdateCartItem,
            removeCartItem: mockRemoveCartItem,
            clearCart: mockClearCart,
            refreshCart: vi.fn(),
        });

        render(<CartIndex />);

        const clearButton = screen.getByText('Vaciar carrito');

        await act(async () => {
            fireEvent.click(clearButton);
        });

        expect(window.confirm).toHaveBeenCalledWith('¿Estás seguro de que quieres vaciar el carrito?');
        expect(mockClearCart).toHaveBeenCalled();
    });

    it('muestra warning cuando se alcanza el stock máximo', () => {
        const cartWithMaxStock: Cart = {
            ...mockCart,
            items: [{
                ...mockCartItem,
                quantity: 10, // igual al stock_quantity del producto
            }],
        };

        mockUseCart.mockReturnValue({
            cart: cartWithMaxStock,
            isLoading: false,
            error: null,
            addToCart: vi.fn(),
            updateCartItem: mockUpdateCartItem,
            removeCartItem: mockRemoveCartItem,
            clearCart: mockClearCart,
            refreshCart: vi.fn(),
        });

        render(<CartIndex />);

        expect(screen.getByText('Stock máximo alcanzado')).toBeInTheDocument();
    });
});
