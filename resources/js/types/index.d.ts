import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    image_path: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Product {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    sku: string | null;
    price: number;
    stock_quantity: number;
    brand: string | null;
    movement_type: string | null;
    image_path: string | null;
    image_url: string; // Ahora siempre es string, nunca null
    is_active: boolean;
    category: Category | null;
    created_at: string;
    updated_at: string;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

export interface ProductsIndexProps {
    products: PaginatedResponse<Product>;
    categories: Category[];
    filters: {
        search?: string;
        category?: string;
        sortBy?: string;
        sortDirection?: 'asc' | 'desc';
    };
}

// Cart interfaces
export interface CartItem {
    id: number;
    cart_id: number;
    product_id: number;
    quantity: number;
    created_at: string;
    updated_at: string;
    product: Product;
}

export interface Cart {
    id: number;
    user_id: number | null;
    session_id: string | null;
    total_amount: number;
    total_items: number;
    expires_at: string | null;
    created_at: string;
    updated_at: string;
    items: CartItem[];
}

export interface CartContextType {
    cart: Cart | null;
    isLoading: boolean;
    error: string | null;
    addToCart: (productId: number, quantity?: number) => Promise<void>;
    updateCartItem: (itemId: number, quantity: number) => Promise<void>;
    removeCartItem: (itemId: number) => Promise<void>;
    clearCart: () => Promise<void>;
    refreshCart: () => Promise<void>;
}

// Address interfaces
export interface Address {
    id: number;
    type: 'shipping' | 'billing';
    first_name: string;
    last_name: string;
    full_name: string;
    company?: string | null;
    address_line_1: string;
    address_line_2?: string | null;
    city: string;
    state: string;
    postal_code: string;
    country: string;
    phone?: string | null;
    is_default: boolean;
    full_address: string;
    created_at: string;
    updated_at: string;
}

// Order interfaces
export interface OrderItem {
    id: number;
    order_id: number;
    product_id: number;
    quantity: number;
    unit_price: string;
    total_price: string;
    created_at: string;
    updated_at: string;
    product: Product;
}

export interface Order {
    id: number;
    order_number: string;
    status: 'pendiente_pago' | 'procesando' | 'enviado' | 'entregado' | 'cancelado';
    payment_status: 'pendiente' | 'pagado' | 'fallido' | 'reembolsado';
    subtotal_amount: string;
    shipping_cost: string;
    total_amount: string;
    payment_gateway?: string | null;
    payment_id?: string | null;
    shipping_method_name?: string | null;
    notes?: string | null;
    created_at: string;
    updated_at: string;
    user?: {
        id: number;
        name: string;
        email: string;
    } | null;
    guest_email?: string | null;
    shipping_address?: Address | null;
    billing_address?: Address | null;
    order_items?: OrderItem[];
    can_be_cancelled?: boolean;
    status_label: string;
    payment_status_label: string;
}

export interface OrdersPaginatedResponse {
    success: boolean;
    data: {
        orders: Order[];
        pagination: {
            current_page: number;
            per_page: number;
            total: number;
            last_page: number;
            from: number | null;
            to: number | null;
        };
    };
}
