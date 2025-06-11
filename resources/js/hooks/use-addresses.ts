import { useState, useEffect, useCallback } from 'react';
import { addressAPI, CreateAddressData, UpdateAddressData } from '@/lib/address-api';
import { Address, SharedData } from '@/types';
import { toast } from 'sonner';
import { usePage } from '@inertiajs/react';

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
    const { auth } = usePage<SharedData>().props;
    const isAuthenticated = auth && auth.user;

    const [addresses, setAddresses] = useState<Address[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const refreshAddresses = useCallback(async () => {
        // Skip API call for guest users
        if (!isAuthenticated) {
            setIsLoading(false);
            setAddresses([]);
            return;
        }

        try {
            setIsLoading(true);
            setError(null);
            const data = await addressAPI.getAddresses(type);
            setAddresses(data);
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Error loading addresses';
            setError(errorMessage);
            console.warn('Address loading error (this is normal for guest users):', errorMessage);
            setAddresses([]);
        } finally {
            setIsLoading(false);
        }
    }, [type, isAuthenticated]);

    const createAddress = useCallback(async (data: CreateAddressData): Promise<Address | null> => {
        // Only allow address creation for authenticated users
        if (!isAuthenticated) {
            toast.error('Debes iniciar sesión para guardar direcciones');
            return null;
        }

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
    }, [isAuthenticated]);

    const updateAddress = useCallback(async (id: number, data: UpdateAddressData): Promise<Address | null> => {
        if (!isAuthenticated) {
            toast.error('Debes iniciar sesión para modificar direcciones');
            return null;
        }

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
    }, [isAuthenticated]);

    const deleteAddress = useCallback(async (id: number): Promise<boolean> => {
        if (!isAuthenticated) {
            toast.error('Debes iniciar sesión para eliminar direcciones');
            return false;
        }

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
    }, [isAuthenticated]);

    const setAsDefault = useCallback(async (id: number): Promise<Address | null> => {
        if (!isAuthenticated) {
            toast.error('Debes iniciar sesión para gestionar direcciones');
            return null;
        }

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
    }, [isAuthenticated]);

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
