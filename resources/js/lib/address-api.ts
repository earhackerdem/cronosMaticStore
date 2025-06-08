import axios from 'axios';
import { Address } from '@/types';

// Configure axios for web authentication (session-based)
const webAxios = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
    withCredentials: true,
});

// Add CSRF token
const csrfToken = document.head.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
if (csrfToken) {
    webAxios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
}

// Initialize CSRF cookie for SPA
async function initializeCSRF() {
    try {
        await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
    } catch (error) {
        console.warn('Failed to initialize CSRF cookie:', error);
    }
}

export interface CreateAddressData {
    type: 'shipping' | 'billing';
    first_name: string;
    last_name: string;
    company?: string;
    address_line_1: string;
    address_line_2?: string;
    city: string;
    state: string;
    postal_code: string;
    country: string;
    phone?: string;
    is_default?: boolean;
}

export type UpdateAddressData = Partial<CreateAddressData>;

export interface AddressApiResponse {
    data: Address;
}

export interface AddressListResponse {
    data: Address[];
}

class AddressAPI {
    private baseURL = '/api/v1/user/addresses';
    private csrfInitialized = false;

    private async ensureCSRF() {
        if (!this.csrfInitialized) {
            await initializeCSRF();
            this.csrfInitialized = true;
        }
    }

    async getAddresses(type?: 'shipping' | 'billing'): Promise<Address[]> {
        await this.ensureCSRF();
        const params = type ? { type } : {};
        const response = await webAxios.get<AddressListResponse>(this.baseURL, { params });
        return response.data.data;
    }

    async getAddress(id: number): Promise<Address> {
        const response = await webAxios.get<AddressApiResponse>(`${this.baseURL}/${id}`);
        return response.data.data;
    }

    async createAddress(data: CreateAddressData): Promise<Address> {
        await this.ensureCSRF();
        const response = await webAxios.post<AddressApiResponse>(this.baseURL, data);
        return response.data.data;
    }

    async updateAddress(id: number, data: UpdateAddressData): Promise<Address> {
        await this.ensureCSRF();
        const response = await webAxios.put<AddressApiResponse>(`${this.baseURL}/${id}`, data);
        return response.data.data;
    }

    async deleteAddress(id: number): Promise<void> {
        await this.ensureCSRF();
        await webAxios.delete(`${this.baseURL}/${id}`);
    }

    async setAsDefault(id: number): Promise<Address> {
        await this.ensureCSRF();
        const response = await webAxios.patch<AddressApiResponse>(`${this.baseURL}/${id}/set-default`);
        return response.data.data;
    }
}

export const addressAPI = new AddressAPI();
