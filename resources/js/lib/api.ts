import { Cart, Order, OrdersPaginatedResponse } from '@/types';

interface ApiResponse<T> {
    success: boolean;
    message: string;
    data: T;
}

export class CartApi {
    private static baseUrl = '/api/v1/cart';

        private static getSessionId(): string {
        // Primero intentar obtener desde localStorage (persistente)
        let sessionId = localStorage.getItem('cart_session_id');
        if (sessionId) return sessionId;

        // Intentar obtener session_id desde meta tag
        const metaSessionId = document.querySelector('meta[name="session-id"]')?.getAttribute('content');
        if (metaSessionId) {
            localStorage.setItem('cart_session_id', metaSessionId);
            return metaSessionId;
        }

        // Intentar obtener desde cookie de Laravel
        const sessionCookie = document.cookie
            .split('; ')
            .find(row => row.startsWith('laravel_session='));

        if (sessionCookie) {
            const cookieSessionId = sessionCookie.split('=')[1];
            localStorage.setItem('cart_session_id', cookieSessionId);
            return cookieSessionId;
        }

        // Si no existe, generar uno nuevo y persistirlo
        sessionId = 'guest_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        localStorage.setItem('cart_session_id', sessionId);
        return sessionId;
    }

    private static getHeaders(): Record<string, string> {
        return {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Session-ID': this.getSessionId(),
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        };
    }

    private static async handleResponse<T>(response: Response): Promise<T> {
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Error en la petición');
        }

        const data: ApiResponse<T> = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Error en la respuesta del servidor');
        }

        return data.data;
    }

    static async getCart(): Promise<Cart> {
        const response = await fetch(this.baseUrl, {
            method: 'GET',
            headers: this.getHeaders(),
            credentials: 'same-origin',
        });

        return this.handleResponse<Cart>(response);
    }

    static async addToCart(productId: number, quantity: number = 1): Promise<Cart> {
        const response = await fetch(`${this.baseUrl}/items`, {
            method: 'POST',
            headers: this.getHeaders(),
            credentials: 'same-origin',
            body: JSON.stringify({
                product_id: productId,
                quantity: quantity,
            }),
        });

        return this.handleResponse<Cart>(response);
    }

    static async updateCartItem(itemId: number, quantity: number): Promise<Cart> {
        const response = await fetch(`${this.baseUrl}/items/${itemId}`, {
            method: 'PUT',
            headers: this.getHeaders(),
            credentials: 'same-origin',
            body: JSON.stringify({
                quantity: quantity,
            }),
        });

        return this.handleResponse<Cart>(response);
    }

    static async removeCartItem(itemId: number): Promise<Cart> {
        const response = await fetch(`${this.baseUrl}/items/${itemId}`, {
            method: 'DELETE',
            headers: this.getHeaders(),
            credentials: 'same-origin',
        });

        return this.handleResponse<Cart>(response);
    }

    static async clearCart(): Promise<void> {
        const response = await fetch(this.baseUrl, {
            method: 'DELETE',
            headers: this.getHeaders(),
            credentials: 'same-origin',
        });

        await this.handleResponse<void>(response);
    }

    /**
     * Limpiar session_id almacenado (útil cuando usuario se autentica)
     */
    static clearStoredSessionId(): void {
        localStorage.removeItem('cart_session_id');
    }

    /**
     * Forzar actualización del session_id (para testing)
     */
    static setSessionId(sessionId: string): void {
        localStorage.setItem('cart_session_id', sessionId);
    }
}

export class UserOrderApi {
    private static baseUrl = '/api/v1/user/orders';

    private static getHeaders(): Record<string, string> {
        const token = document.querySelector('meta[name="auth-token"]')?.getAttribute('content') ||
                     localStorage.getItem('auth_token');

        return {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            ...(token && { 'Authorization': `Bearer ${token}` }),
        };
    }

    private static async handleResponse<T>(response: Response): Promise<T> {
        if (!response.ok) {
            if (response.status === 401) {
                // Redirect to login or handle authentication error
                window.location.href = '/login';
                throw new Error('No autorizado');
            }

            const errorData = await response.json();
            throw new Error(errorData.message || 'Error en la petición');
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Error en la respuesta del servidor');
        }

        return data;
    }

    static async getUserOrders(page: number = 1, perPage: number = 15): Promise<OrdersPaginatedResponse> {
        const response = await fetch(`${this.baseUrl}?page=${page}&per_page=${perPage}`, {
            method: 'GET',
            headers: this.getHeaders(),
            credentials: 'same-origin',
        });

        return this.handleResponse<OrdersPaginatedResponse>(response);
    }

    static async getUserOrder(orderNumber: string): Promise<{ success: boolean; data: { order: Order } }> {
        const response = await fetch(`${this.baseUrl}/${orderNumber}`, {
            method: 'GET',
            headers: this.getHeaders(),
            credentials: 'same-origin',
        });

        return this.handleResponse<{ success: boolean; data: { order: Order } }>(response);
    }
}
