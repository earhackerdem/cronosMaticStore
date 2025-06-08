import { useState, useEffect, useCallback } from 'react';
import { addressAPI, CreateAddressData, UpdateAddressData } from '@/lib/address-api';
import { Address } from '@/types';
import { toast } from 'sonner';

export interface UseAddressesReturn {
    addresses: Address[];
    isLoading: boolean;
    error: string | null;
    refreshAddresses: () => Promise<void>;
    createAddress: (data: CreateAddressData) => Promise<Address | null>;
    updateAddress: (id: number, data: UpdateAddressData) => Promise<Address | null>;
    deleteAddress: (id: number) => Promise<boolean>;
    setAsDefault: (id: number) => Promise<Address | null>;
}

export function useAddresses(type?: 'shipping' | 'billing'): UseAddressesReturn {
    const [addresses, setAddresses] = useState<Address[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const refreshAddresses = useCallback(async () => {
        try {
            setIsLoading(true);
            setError(null);
            const data = await addressAPI.getAddresses(type);
            setAddresses(data);
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Error loading addresses';
            setError(errorMessage);
            toast.error('Error al cargar las direcciones');
        } finally {
            setIsLoading(false);
        }
    }, [type]);

    const createAddress = useCallback(async (data: CreateAddressData): Promise<Address | null> => {
        try {
            const newAddress = await addressAPI.createAddress(data);
            setAddresses(prev => [newAddress, ...prev]);
            toast.success('Dirección creada exitosamente');
            return newAddress;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Error creating address';
            setError(errorMessage);
            toast.error('Error al crear la dirección');
            return null;
        }
    }, []);

    const updateAddress = useCallback(async (id: number, data: UpdateAddressData): Promise<Address | null> => {
        try {
            const updatedAddress = await addressAPI.updateAddress(id, data);
            setAddresses(prev => prev.map(addr => addr.id === id ? updatedAddress : addr));
            toast.success('Dirección actualizada exitosamente');
            return updatedAddress;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Error updating address';
            setError(errorMessage);
            toast.error('Error al actualizar la dirección');
            return null;
        }
    }, []);

    const deleteAddress = useCallback(async (id: number): Promise<boolean> => {
        try {
            await addressAPI.deleteAddress(id);
            setAddresses(prev => prev.filter(addr => addr.id !== id));
            toast.success('Dirección eliminada exitosamente');
            return true;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Error deleting address';
            setError(errorMessage);
            toast.error('Error al eliminar la dirección');
            return false;
        }
    }, []);

    const setAsDefault = useCallback(async (id: number): Promise<Address | null> => {
        try {
            const updatedAddress = await addressAPI.setAsDefault(id);
            // Update both the target address and clear default from others of the same type
            setAddresses(prev => prev.map(addr => {
                if (addr.id === id) {
                    return updatedAddress;
                } else if (addr.type === updatedAddress.type && addr.is_default) {
                    return { ...addr, is_default: false };
                }
                return addr;
            }));
            toast.success('Dirección marcada como predeterminada');
            return updatedAddress;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Error setting default address';
            setError(errorMessage);
            toast.error('Error al marcar la dirección como predeterminada');
            return null;
        }
    }, []);

    useEffect(() => {
        refreshAddresses();
    }, [refreshAddresses]);

    return {
        addresses,
        isLoading,
        error,
        refreshAddresses,
        createAddress,
        updateAddress,
        deleteAddress,
        setAsDefault,
    };
}
