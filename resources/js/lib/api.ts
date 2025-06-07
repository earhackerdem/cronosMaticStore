import { Cart } from '@/types';

interface ApiResponse<T> {
    success: boolean;
    message: string;
    data: T;
}

export class CartApi {
    private static baseUrl = '/api/v1/cart';

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
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        return this.handleResponse<Cart>(response);
    }

    static async addToCart(productId: number, quantity: number = 1): Promise<Cart> {
        const response = await fetch(this.baseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
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
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
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
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
        });

        return this.handleResponse<Cart>(response);
    }

    static async clearCart(): Promise<void> {
        const response = await fetch(`${this.baseUrl}/clear`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
        });

        await this.handleResponse<void>(response);
    }
}
