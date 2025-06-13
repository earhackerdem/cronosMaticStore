import { renderHook, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useAddresses } from '@/hooks/use-addresses';
import { addressAPI } from '@/lib/address-api';
import { Address } from '@/types';

// Mock the address API
vi.mock('@/lib/address-api', () => ({
    addressAPI: {
        getAddresses: vi.fn(),
        createAddress: vi.fn(),
        updateAddress: vi.fn(),
        deleteAddress: vi.fn(),
        setAsDefault: vi.fn(),
    },
}));

// Mock sonner
vi.mock('sonner', () => ({
    toast: {
        success: vi.fn(),
        error: vi.fn(),
    },
}));

// Mock Inertia usePage hook
vi.mock('@inertiajs/react', () => ({
    usePage: vi.fn(() => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test User',
                    email: 'test@example.com',
                },
            },
        },
    })),
}));

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

describe('useAddresses', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('should fetch addresses on mount', async () => {
        const mockAddresses = [mockAddress];
        vi.mocked(addressAPI.getAddresses).mockResolvedValue(mockAddresses);

        const { result } = renderHook(() => useAddresses());

        expect(result.current.isLoading).toBe(true);

        await waitFor(() => {
            expect(result.current.isLoading).toBe(false);
        });

        expect(result.current.addresses).toEqual(mockAddresses);
        expect(result.current.error).toBe(null);
        expect(addressAPI.getAddresses).toHaveBeenCalledWith(undefined);
    });

    it('should filter addresses by type when provided', async () => {
        const mockAddresses = [mockAddress];
        vi.mocked(addressAPI.getAddresses).mockResolvedValue(mockAddresses);

        renderHook(() => useAddresses('shipping'));

        await waitFor(() => {
            expect(addressAPI.getAddresses).toHaveBeenCalledWith('shipping');
        });
    });

    it('should handle fetch error', async () => {
        const errorMessage = 'Failed to fetch addresses';
        vi.mocked(addressAPI.getAddresses).mockRejectedValue(new Error(errorMessage));

        const { result } = renderHook(() => useAddresses());

        await waitFor(() => {
            expect(result.current.isLoading).toBe(false);
        });

        expect(result.current.error).toBe(errorMessage);
        expect(result.current.addresses).toEqual([]);
    });

    it('should provide createAddress function', async () => {
        vi.mocked(addressAPI.getAddresses).mockResolvedValue([]);

        const { result } = renderHook(() => useAddresses());

        await waitFor(() => {
            expect(result.current.isLoading).toBe(false);
        });

        expect(typeof result.current.createAddress).toBe('function');
    });

    it('should provide updateAddress function', async () => {
        vi.mocked(addressAPI.getAddresses).mockResolvedValue([]);

        const { result } = renderHook(() => useAddresses());

        await waitFor(() => {
            expect(result.current.isLoading).toBe(false);
        });

        expect(typeof result.current.updateAddress).toBe('function');
    });

    it('should provide deleteAddress function', async () => {
        vi.mocked(addressAPI.getAddresses).mockResolvedValue([]);

        const { result } = renderHook(() => useAddresses());

        await waitFor(() => {
            expect(result.current.isLoading).toBe(false);
        });

        expect(typeof result.current.deleteAddress).toBe('function');
    });

    it('should provide setAsDefault function', async () => {
        vi.mocked(addressAPI.getAddresses).mockResolvedValue([]);

        const { result } = renderHook(() => useAddresses());

        await waitFor(() => {
            expect(result.current.isLoading).toBe(false);
        });

        expect(typeof result.current.setAsDefault).toBe('function');
    });

    it('should handle create address error gracefully', async () => {
        vi.mocked(addressAPI.getAddresses).mockResolvedValue([]);
        vi.mocked(addressAPI.createAddress).mockRejectedValue(new Error('Create failed'));

        const { result } = renderHook(() => useAddresses());

        await waitFor(() => {
            expect(result.current.isLoading).toBe(false);
        });

        const createData = {
            type: 'shipping' as const,
            first_name: 'Jane',
            last_name: 'Smith',
            address_line_1: '456 Oak St',
            city: 'Los Angeles',
            state: 'CA',
            postal_code: '90210',
            country: 'USA',
        };

        const created = await result.current.createAddress(createData);

        expect(created).toBe(null);
        expect(result.current.addresses).toEqual([]);
    });

    it('should provide refreshAddresses function', async () => {
        vi.mocked(addressAPI.getAddresses).mockResolvedValue([]);

        const { result } = renderHook(() => useAddresses());

        await waitFor(() => {
            expect(result.current.isLoading).toBe(false);
        });

        expect(typeof result.current.refreshAddresses).toBe('function');
    });
});
