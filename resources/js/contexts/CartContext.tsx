import React, { createContext, useContext, useEffect, useReducer, ReactNode } from 'react';
import { Cart, CartContextType } from '@/types';
import { CartApi } from '@/lib/api';

// Actions del reducer
type CartAction =
    | { type: 'SET_LOADING'; payload: boolean }
    | { type: 'SET_CART'; payload: Cart | null }
    | { type: 'SET_ERROR'; payload: string | null }
    | { type: 'CLEAR_ERROR' };

// Estado inicial
interface CartState {
    cart: Cart | null;
    isLoading: boolean;
    error: string | null;
}

const initialState: CartState = {
    cart: null,
    isLoading: false,
    error: null,
};

// Reducer
function cartReducer(state: CartState, action: CartAction): CartState {
    switch (action.type) {
        case 'SET_LOADING':
            return { ...state, isLoading: action.payload };
        case 'SET_CART':
            return { ...state, cart: action.payload, error: null };
        case 'SET_ERROR':
            return { ...state, error: action.payload, isLoading: false };
        case 'CLEAR_ERROR':
            return { ...state, error: null };
        default:
            return state;
    }
}

// Context
const CartContext = createContext<CartContextType | undefined>(undefined);

// Provider Component
interface CartProviderProps {
    children: ReactNode;
}

export function CartProvider({ children }: CartProviderProps) {
    const [state, dispatch] = useReducer(cartReducer, initialState);

    // Funciones del contexto
    const refreshCart = async () => {
        try {
            dispatch({ type: 'SET_LOADING', payload: true });
            dispatch({ type: 'CLEAR_ERROR' });
            const cart = await CartApi.getCart();
            dispatch({ type: 'SET_CART', payload: cart });
        } catch (error) {
            console.error('Error al obtener el carrito:', error);
            dispatch({ type: 'SET_ERROR', payload: error instanceof Error ? error.message : 'Error desconocido' });
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    };

    const addToCart = async (productId: number, quantity: number = 1) => {
        try {
            dispatch({ type: 'SET_LOADING', payload: true });
            dispatch({ type: 'CLEAR_ERROR' });
            const updatedCart = await CartApi.addToCart(productId, quantity);
            dispatch({ type: 'SET_CART', payload: updatedCart });
        } catch (error) {
            console.error('Error al añadir al carrito:', error);
            dispatch({ type: 'SET_ERROR', payload: error instanceof Error ? error.message : 'Error al añadir producto' });
            throw error; // Re-throw para que el componente pueda manejarlo
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    };

    const updateCartItem = async (itemId: number, quantity: number) => {
        try {
            dispatch({ type: 'SET_LOADING', payload: true });
            dispatch({ type: 'CLEAR_ERROR' });
            const updatedCart = await CartApi.updateCartItem(itemId, quantity);
            dispatch({ type: 'SET_CART', payload: updatedCart });
        } catch (error) {
            console.error('Error al actualizar item del carrito:', error);
            dispatch({ type: 'SET_ERROR', payload: error instanceof Error ? error.message : 'Error al actualizar item' });
            throw error;
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    };

    const removeCartItem = async (itemId: number) => {
        try {
            dispatch({ type: 'SET_LOADING', payload: true });
            dispatch({ type: 'CLEAR_ERROR' });
            const updatedCart = await CartApi.removeCartItem(itemId);
            dispatch({ type: 'SET_CART', payload: updatedCart });
        } catch (error) {
            console.error('Error al eliminar item del carrito:', error);
            dispatch({ type: 'SET_ERROR', payload: error instanceof Error ? error.message : 'Error al eliminar item' });
            throw error;
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    };

    const clearCart = async () => {
        try {
            dispatch({ type: 'SET_LOADING', payload: true });
            dispatch({ type: 'CLEAR_ERROR' });
            await CartApi.clearCart();
            dispatch({ type: 'SET_CART', payload: null });
        } catch (error) {
            console.error('Error al vaciar el carrito:', error);
            dispatch({ type: 'SET_ERROR', payload: error instanceof Error ? error.message : 'Error al vaciar carrito' });
            throw error;
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    };

    // Cargar carrito al montar el componente
    useEffect(() => {
        refreshCart();
    }, []);

    const contextValue: CartContextType = {
        cart: state.cart,
        isLoading: state.isLoading,
        error: state.error,
        addToCart,
        updateCartItem,
        removeCartItem,
        clearCart,
        refreshCart,
    };

    return (
        <CartContext.Provider value={contextValue}>
            {children}
        </CartContext.Provider>
    );
}

// Hook personalizado para usar el contexto
export function useCart(): CartContextType {
    const context = useContext(CartContext);
    if (context === undefined) {
        throw new Error('useCart debe ser usado dentro de un CartProvider');
    }
    return context;
}
