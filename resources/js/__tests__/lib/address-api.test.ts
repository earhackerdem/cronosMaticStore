import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import axios from 'axios';
import { Address } from '@/types';

// Mock axios
vi.mock('axios');
const mockedAxios = vi.mocked(axios);

// Mock axios instance methods
const mockAxiosInstance = {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    defaults: {
        headers: {
            common: {}
        }
    }
};

// Mock axios.create to return our mock instance
mockedAxios.create.mockReturnValue(mockAxiosInstance as any);

// Mock document.head.querySelector for CSRF token
Object.defineProperty(document, 'head', {
    value: {
        querySelector: vi.fn().mockReturnValue({
            content: 'mock-csrf-token'
        })
    },
    configurable: true
});

const mockAddress: Address = {
    id: 1,
    type: 'shipping',
    first_name: 'John',
    last_name: 'Doe',
    full_name: 'John Doe',
    company: 'Acme Corp',
    address_line_1: '123 Main St',
    address_line_2: 'Apt 4B',
    city: 'New York',
    state: 'NY',
    postal_code: '10001',
    country: 'USA',
    phone: '+1234567890',
    is_default: false,
    full_address: '123 Main St, Apt 4B, New York, NY 10001, USA',
    created_at: '2023-01-01T00:00:00.000000Z',
    updated_at: '2023-01-01T00:00:00.000000Z',
};

describe('AddressAPI', () => {
    // Import inside describe to ensure mocks are set up
    let addressAPI: any;
    let CreateAddressData: any;
    let UpdateAddressData: any;

    beforeEach(async () => {
        vi.clearAllMocks();

        // Dynamically import the module after mocks are set up
        const module = await import('@/lib/address-api');
        addressAPI = module.addressAPI;
        CreateAddressData = module.CreateAddressData;
        UpdateAddressData = module.UpdateAddressData;

        // Reset CSRF initialization flag
        (addressAPI as any).csrfInitialized = false;
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe('getAddresses', () => {
        it('should fetch all addresses', async () => {
            const mockResponse = { data: { data: [mockAddress] } };
            mockAxiosInstance.get.mockResolvedValue(mockResponse);
            mockedAxios.get.mockResolvedValue(undefined);

            const result = await addressAPI.getAddresses();

            expect(mockAxiosInstance.get).toHaveBeenCalledWith('/api/v1/user/addresses', { params: {} });
            expect(result).toEqual([mockAddress]);
        });

        it('should fetch addresses filtered by type', async () => {
            const mockResponse = { data: { data: [mockAddress] } };
            mockAxiosInstance.get.mockResolvedValue(mockResponse);
            mockedAxios.get.mockResolvedValue(undefined);

            const result = await addressAPI.getAddresses('shipping');

            expect(mockAxiosInstance.get).toHaveBeenCalledWith('/api/v1/user/addresses', {
                params: { type: 'shipping' }
            });
            expect(result).toEqual([mockAddress]);
        });

        it('should initialize CSRF before request', async () => {
            const mockResponse = { data: { data: [mockAddress] } };
            mockAxiosInstance.get.mockResolvedValue(mockResponse);
            mockedAxios.get.mockResolvedValue(undefined);

            await addressAPI.getAddresses();

            expect(mockedAxios.get).toHaveBeenCalledWith('/sanctum/csrf-cookie', { withCredentials: true });
        });
    });

    describe('getAddress', () => {
        it('should fetch single address by id', async () => {
            const mockResponse = { data: { data: mockAddress } };
            mockAxiosInstance.get.mockResolvedValue(mockResponse);

            const result = await addressAPI.getAddress(1);

            expect(mockAxiosInstance.get).toHaveBeenCalledWith('/api/v1/user/addresses/1');
            expect(result).toEqual(mockAddress);
        });
    });

    describe('createAddress', () => {
        it('should create new address', async () => {
            const mockResponse = { data: { data: mockAddress } };
            const createData = {
                type: 'shipping' as const,
                first_name: 'John',
                last_name: 'Doe',
                address_line_1: '123 Main St',
                city: 'New York',
                state: 'NY',
                postal_code: '10001',
                country: 'USA'
            };

            mockAxiosInstance.post.mockResolvedValue(mockResponse);
            mockedAxios.get.mockResolvedValue(undefined);

            const result = await addressAPI.createAddress(createData);

            expect(mockAxiosInstance.post).toHaveBeenCalledWith('/api/v1/user/addresses', createData);
            expect(result).toEqual(mockAddress);
        });
    });

    describe('updateAddress', () => {
        it('should update existing address', async () => {
            const mockResponse = { data: { data: mockAddress } };
            const updateData = {
                first_name: 'Jane'
            };

            mockAxiosInstance.put.mockResolvedValue(mockResponse);
            mockedAxios.get.mockResolvedValue(undefined);

            const result = await addressAPI.updateAddress(1, updateData);

            expect(mockAxiosInstance.put).toHaveBeenCalledWith('/api/v1/user/addresses/1', updateData);
            expect(result).toEqual(mockAddress);
        });
    });

    describe('deleteAddress', () => {
        it('should delete address by id', async () => {
            mockAxiosInstance.delete.mockResolvedValue(undefined);
            mockedAxios.get.mockResolvedValue(undefined);

            await addressAPI.deleteAddress(1);

            expect(mockAxiosInstance.delete).toHaveBeenCalledWith('/api/v1/user/addresses/1');
        });
    });

    describe('setAsDefault', () => {
        it('should set address as default', async () => {
            const mockResponse = { data: { data: { ...mockAddress, is_default: true } } };

            mockAxiosInstance.patch.mockResolvedValue(mockResponse);
            mockedAxios.get.mockResolvedValue(undefined);

            const result = await addressAPI.setAsDefault(1);

            expect(mockAxiosInstance.patch).toHaveBeenCalledWith('/api/v1/user/addresses/1/set-default');
            expect(result).toEqual({ ...mockAddress, is_default: true });
        });
    });

    describe('CSRF handling', () => {
        it('should handle CSRF initialization failure gracefully', async () => {
            const consoleWarnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
            const mockResponse = { data: { data: [mockAddress] } };

            mockAxiosInstance.get.mockResolvedValue(mockResponse);
            mockedAxios.get.mockRejectedValue(new Error('CSRF failed'));

            const result = await addressAPI.getAddresses();

            expect(consoleWarnSpy).toHaveBeenCalledWith('Failed to initialize CSRF cookie:', expect.any(Error));
            expect(result).toEqual([mockAddress]);

            consoleWarnSpy.mockRestore();
        });

        it('should only initialize CSRF once', async () => {
            const mockResponse = { data: { data: [mockAddress] } };
            mockAxiosInstance.get.mockResolvedValue(mockResponse);
            mockedAxios.get.mockResolvedValue(undefined);

            await addressAPI.getAddresses();
            await addressAPI.getAddresses();

            // CSRF should only be called once (on first call)
            expect(mockedAxios.get).toHaveBeenCalledTimes(1);
            expect(mockedAxios.get).toHaveBeenCalledWith('/sanctum/csrf-cookie', { withCredentials: true });
        });
    });
});