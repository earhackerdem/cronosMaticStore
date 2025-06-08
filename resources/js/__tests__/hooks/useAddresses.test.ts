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

    it('should create address successfully', async () => {
        const newAddress = { ...mockAddress, id: 2 };
        vi.mocked(addressAPI.getAddresses).mockResolvedValue([mockAddress]);
        vi.mocked(addressAPI.createAddress).mockResolvedValue(newAddress);

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

        const createdAddress = await result.current.createAddress(createData);

        expect(createdAddress).toEqual(newAddress);
        await waitFor(() => {
            expect(result.current.addresses).toContain(newAddress);
        });
        expect(addressAPI.createAddress).toHaveBeenCalledWith(createData);
    });

    it('should update address successfully', async () => {
        const updatedAddress = { ...mockAddress, first_name: 'Jane' };
        vi.mocked(addressAPI.getAddresses).mockResolvedValue([mockAddress]);
        vi.mocked(addressAPI.updateAddress).mockResolvedValue(updatedAddress);

        const { result } = renderHook(() => useAddresses());

        await waitFor(() => {
            expect(result.current.isLoading).toBe(false);
        });

        const updateData = { first_name: 'Jane' };
        const updated = await result.current.updateAddress(mockAddress.id, updateData);

        expect(updated).toEqual(updatedAddress);
        await waitFor(() => {
            expect(result.current.addresses[0]).toEqual(updatedAddress);
        });
        expect(addressAPI.updateAddress).toHaveBeenCalledWith(mockAddress.id, updateData);
    });

    it('should delete address successfully', async () => {
        vi.mocked(addressAPI.getAddresses).mockResolvedValue([mockAddress]);
        vi.mocked(addressAPI.deleteAddress).mockResolvedValue();

        const { result } = renderHook(() => useAddresses());

        await waitFor(() => {
            expect(result.current.isLoading).toBe(false);
        });

        const deleted = await result.current.deleteAddress(mockAddress.id);

        expect(deleted).toBe(true);
        await waitFor(() => {
            expect(result.current.addresses).toEqual([]);
        });
        expect(addressAPI.deleteAddress).toHaveBeenCalledWith(mockAddress.id);
    });

    it('should set address as default successfully', async () => {
        const defaultAddress = { ...mockAddress, is_default: true };
        vi.mocked(addressAPI.getAddresses).mockResolvedValue([mockAddress]);
        vi.mocked(addressAPI.setAsDefault).mockResolvedValue(defaultAddress);

        const { result } = renderHook(() => useAddresses());

        await waitFor(() => {
            expect(result.current.isLoading).toBe(false);
        });

        const updated = await result.current.setAsDefault(mockAddress.id);

        expect(updated).toEqual(defaultAddress);
        await waitFor(() => {
            expect(result.current.addresses[0]).toEqual(defaultAddress);
        });
        expect(addressAPI.setAsDefault).toHaveBeenCalledWith(mockAddress.id);
    });

    it('should handle create address error', async () => {
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

    it('should refresh addresses', async () => {
        const initialAddresses = [mockAddress];
        const refreshedAddresses = [mockAddress, { ...mockAddress, id: 2 }];

        vi.mocked(addressAPI.getAddresses)
            .mockResolvedValueOnce(initialAddresses)
            .mockResolvedValueOnce(refreshedAddresses);

        const { result } = renderHook(() => useAddresses());

        await waitFor(() => {
            expect(result.current.isLoading).toBe(false);
        });

        expect(result.current.addresses).toEqual(initialAddresses);

        await result.current.refreshAddresses();

        await waitFor(() => {
            expect(result.current.addresses).toEqual(refreshedAddresses);
        });
        expect(addressAPI.getAddresses).toHaveBeenCalledTimes(2);
    });
});
