import { render, screen } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { CartIndicator } from '@/components/cart-indicator';
import { useCart } from '@/contexts/CartContext';
import { Cart } from '@/types';

// Mock del contexto del carrito
vi.mock('@/contexts/CartContext');
const mockUseCart = vi.mocked(useCart);

// Mock de Inertia Link
vi.mock('@inertiajs/react', () => ({
    Link: ({ children, href, className }: { children: React.ReactNode; href: string; className?: string }) => (
        <a href={href} className={className}>
            {children}
        </a>
    ),
}));

describe('CartIndicator', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('muestra el icono del carrito sin badge cuando está vacío', () => {
        mockUseCart.mockReturnValue({
            cart: null,
            isLoading: false,
            error: null,
            addToCart: vi.fn(),
            updateCartItem: vi.fn(),
            removeCartItem: vi.fn(),
            clearCart: vi.fn(),
            refreshCart: vi.fn(),
        });

        render(<CartIndicator />);

        expect(screen.getByRole('link')).toHaveAttribute('href', '/carrito');
        expect(screen.queryByText(/\d+/)).not.toBeInTheDocument();
    });

    it('muestra el badge con la cantidad correcta cuando hay items', () => {
        const mockCart: Cart = {
            id: 1,
            user_id: 1,
            session_id: null,
            total_amount: 1500,
            total_items: 3,
            expires_at: null,
            created_at: '2024-01-01T00:00:00Z',
            updated_at: '2024-01-01T00:00:00Z',
            items: [],
        };

        mockUseCart.mockReturnValue({
            cart: mockCart,
            isLoading: false,
            error: null,
            addToCart: vi.fn(),
            updateCartItem: vi.fn(),
            removeCartItem: vi.fn(),
            clearCart: vi.fn(),
            refreshCart: vi.fn(),
        });

        render(<CartIndicator />);

        expect(screen.getByText('3')).toBeInTheDocument();
    });

    it('muestra "99+" cuando hay más de 99 items', () => {
        const mockCart: Cart = {
            id: 1,
            user_id: 1,
            session_id: null,
            total_amount: 15000,
            total_items: 150,
            expires_at: null,
            created_at: '2024-01-01T00:00:00Z',
            updated_at: '2024-01-01T00:00:00Z',
            items: [],
        };

        mockUseCart.mockReturnValue({
            cart: mockCart,
            isLoading: false,
            error: null,
            addToCart: vi.fn(),
            updateCartItem: vi.fn(),
            removeCartItem: vi.fn(),
            clearCart: vi.fn(),
            refreshCart: vi.fn(),
        });

        render(<CartIndicator />);

        expect(screen.getByText('99+')).toBeInTheDocument();
    });

    it('muestra el indicador de carga cuando isLoading es true', () => {
        mockUseCart.mockReturnValue({
            cart: null,
            isLoading: true,
            error: null,
            addToCart: vi.fn(),
            updateCartItem: vi.fn(),
            removeCartItem: vi.fn(),
            clearCart: vi.fn(),
            refreshCart: vi.fn(),
        });

        render(<CartIndicator />);

        const loadingIndicator = document.querySelector('.animate-spin');
        expect(loadingIndicator).toBeInTheDocument();
    });
});
