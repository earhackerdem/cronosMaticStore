import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { AddToCartButton } from '@/components/add-to-cart-button';
import { useCart } from '@/contexts/CartContext';
import { Product } from '@/types';

// Mock del contexto del carrito
vi.mock('@/contexts/CartContext');
const mockUseCart = vi.mocked(useCart);

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

describe('AddToCartButton', () => {
    const mockAddToCart = vi.fn();

    beforeEach(() => {
        vi.clearAllMocks();
        mockUseCart.mockReturnValue({
            cart: null,
            isLoading: false,
            error: null,
            addToCart: mockAddToCart,
            updateCartItem: vi.fn(),
            removeCartItem: vi.fn(),
            clearCart: vi.fn(),
            refreshCart: vi.fn(),
        });
    });

    it('muestra el botón "Añadir al carrito" cuando el producto tiene stock', () => {
        render(<AddToCartButton product={mockProduct} />);

        const button = screen.getByRole('button');
        expect(button).toBeInTheDocument();
        expect(button).toHaveTextContent('Añadir al carrito');
        expect(button).not.toBeDisabled();
    });

    it('muestra "Producto agotado" cuando no hay stock', () => {
        const outOfStockProduct = { ...mockProduct, stock_quantity: 0 };
        render(<AddToCartButton product={outOfStockProduct} />);

        const button = screen.getByRole('button');
        expect(button).toHaveTextContent('Producto agotado');
        expect(button).toBeDisabled();
    });

    it('llama a addToCart cuando se hace clic', async () => {
        render(<AddToCartButton product={mockProduct} />);

        const button = screen.getByRole('button');

        await act(async () => {
            fireEvent.click(button);
        });

        expect(mockAddToCart).toHaveBeenCalledWith(mockProduct.id, 1);
    });

        it('muestra estado de carga durante la operación', async () => {
        mockAddToCart.mockImplementation(() => new Promise(resolve => setTimeout(resolve, 100)));

        render(<AddToCartButton product={mockProduct} />);

        const button = screen.getByRole('button');

        await act(async () => {
            fireEvent.click(button);
        });

        expect(button).toHaveTextContent('Añadiendo...');
        expect(button).toBeDisabled();

        await waitFor(() => {
            expect(button).not.toHaveTextContent('Añadiendo...');
        });
    });

        it('muestra "¡Añadido!" después de añadir exitosamente', async () => {
        mockAddToCart.mockResolvedValue(undefined);

        render(<AddToCartButton product={mockProduct} />);

        const button = screen.getByRole('button');

        await act(async () => {
            fireEvent.click(button);
        });

        await waitFor(() => {
            expect(button).toHaveTextContent('¡Añadido!');
        });
    });

        it('maneja errores correctamente', async () => {
        const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
        mockAddToCart.mockRejectedValue(new Error('Error de prueba'));

        render(<AddToCartButton product={mockProduct} />);

        const button = screen.getByRole('button');

        await act(async () => {
            fireEvent.click(button);
        });

        await waitFor(() => {
            expect(consoleErrorSpy).toHaveBeenCalledWith('Error al añadir el producto:', expect.any(Error));
        });

        consoleErrorSpy.mockRestore();
    });

    it('respeta la cantidad personalizada', async () => {
        render(<AddToCartButton product={mockProduct} quantity={3} />);

        const button = screen.getByRole('button');

        await act(async () => {
            fireEvent.click(button);
        });

        expect(mockAddToCart).toHaveBeenCalledWith(mockProduct.id, 3);
    });

    it('está deshabilitado cuando isLoading es true', () => {
        mockUseCart.mockReturnValue({
            cart: null,
            isLoading: true,
            error: null,
            addToCart: mockAddToCart,
            updateCartItem: vi.fn(),
            removeCartItem: vi.fn(),
            clearCart: vi.fn(),
            refreshCart: vi.fn(),
        });

        render(<AddToCartButton product={mockProduct} />);

        const button = screen.getByRole('button');
        expect(button).toBeDisabled();
    });
});
