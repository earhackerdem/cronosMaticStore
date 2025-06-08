import axios from './axios';
import { Address } from '@/types';

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

    async getAddresses(type?: 'shipping' | 'billing'): Promise<Address[]> {
        const params = type ? { type } : {};
        const response = await axios.get<AddressListResponse>(this.baseURL, { params });
        return response.data.data;
    }

    async getAddress(id: number): Promise<Address> {
        const response = await axios.get<AddressApiResponse>(`${this.baseURL}/${id}`);
        return response.data.data;
    }

    async createAddress(data: CreateAddressData): Promise<Address> {
        const response = await axios.post<AddressApiResponse>(this.baseURL, data);
        return response.data.data;
    }

    async updateAddress(id: number, data: UpdateAddressData): Promise<Address> {
        const response = await axios.put<AddressApiResponse>(`${this.baseURL}/${id}`, data);
        return response.data.data;
    }

    async deleteAddress(id: number): Promise<void> {
        await axios.delete(`${this.baseURL}/${id}`);
    }

    async setAsDefault(id: number): Promise<Address> {
        const response = await axios.patch<AddressApiResponse>(`${this.baseURL}/${id}/set-default`);
        return response.data.data;
    }
}

export const addressAPI = new AddressAPI();
