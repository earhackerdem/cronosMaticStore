import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { useAddresses } from '@/hooks/use-addresses';
import { Address } from '@/types';
import { CreateAddressData, UpdateAddressData } from '@/lib/address-api';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { AddressCard } from '@/components/address-card';
import { AddressForm } from '@/components/address-form';
import { DeleteAddressDialog } from '@/components/delete-address-dialog';
import { Plus, MapPin, Loader2 } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Address settings',
        href: '/settings/addresses',
    },
];

export default function Addresses() {
    const {
        addresses,
        isLoading,
        error,
        createAddress,
        updateAddress,
        deleteAddress,
        setAsDefault
    } = useAddresses();

    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingAddress, setEditingAddress] = useState<Address | null>(null);
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
    const [deletingAddress, setDeletingAddress] = useState<Address | null>(null);
    const [activeTab, setActiveTab] = useState('all');

    // Filter addresses by type
    const shippingAddresses = addresses.filter(addr => addr.type === 'shipping');
    const billingAddresses = addresses.filter(addr => addr.type === 'billing');

    const getDisplayAddresses = () => {
        switch (activeTab) {
            case 'shipping':
                return shippingAddresses;
            case 'billing':
                return billingAddresses;
            default:
                return addresses;
        }
    };

    const handleCreateAddress = () => {
        setEditingAddress(null);
        setIsFormOpen(true);
    };

    const handleEditAddress = (address: Address) => {
        setEditingAddress(address);
        setIsFormOpen(true);
    };

    const handleDeleteAddress = (address: Address) => {
        setDeletingAddress(address);
        setIsDeleteDialogOpen(true);
    };

    const handleSetDefault = async (address: Address) => {
        await setAsDefault(address.id);
    };

    const handleSaveAddress = async (data: CreateAddressData | UpdateAddressData) => {
        if (editingAddress) {
            await updateAddress(editingAddress.id, data);
        } else {
            await createAddress(data as CreateAddressData);
        }
    };

    const handleConfirmDelete = async (address: Address) => {
        await deleteAddress(address.id);
    };

    const displayAddresses = getDisplayAddresses();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Address settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <HeadingSmall
                            title="Libreta de Direcciones"
                            description="Gestiona tus direcciones de envío y facturación"
                        />
                        <Button onClick={handleCreateAddress} data-testid="add-address-button">
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva Dirección
                        </Button>
                    </div>

                    {error && (
                        <div className="rounded-lg border border-destructive/20 bg-destructive/10 p-4">
                            <p className="text-sm text-destructive">{error}</p>
                        </div>
                    )}

                    <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
                        <TabsList className="grid w-full grid-cols-3">
                            <TabsTrigger value="all">
                                Todas ({addresses.length})
                            </TabsTrigger>
                            <TabsTrigger value="shipping">
                                Envío ({shippingAddresses.length})
                            </TabsTrigger>
                            <TabsTrigger value="billing">
                                Facturación ({billingAddresses.length})
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value={activeTab} className="mt-6">
                            {isLoading ? (
                                <div className="flex items-center justify-center py-12">
                                    <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                                    <span className="ml-2 text-muted-foreground">Cargando direcciones...</span>
                                </div>
                            ) : displayAddresses.length === 0 ? (
                                <div className="text-center py-12">
                                    <MapPin className="mx-auto h-12 w-12 text-muted-foreground/50" />
                                    <h3 className="mt-4 text-lg font-medium text-foreground">
                                        {activeTab === 'all'
                                            ? 'No tienes direcciones guardadas'
                                            : `No tienes direcciones de ${activeTab === 'shipping' ? 'envío' : 'facturación'}`
                                        }
                                    </h3>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        Agrega una nueva dirección para comenzar.
                                    </p>
                                                                        <Button
                                        onClick={handleCreateAddress}
                                        className="mt-4"
                                        variant="outline"
                                        data-testid="empty-state-add-button"
                                    >
                                        <Plus className="mr-2 h-4 w-4" />
                                        Agregar Dirección
                                    </Button>
                                </div>
                            ) : (
                                <div className="grid gap-4 md:grid-cols-2">
                                    {displayAddresses.map((address) => (
                                        <AddressCard
                                            key={address.id}
                                            address={address}
                                            onEdit={handleEditAddress}
                                            onDelete={handleDeleteAddress}
                                            onSetDefault={handleSetDefault}
                                        />
                                    ))}
                                </div>
                            )}
                        </TabsContent>
                    </Tabs>
                </div>

                {/* Address Form Dialog */}
                <AddressForm
                    address={editingAddress}
                    isOpen={isFormOpen}
                    onClose={() => {
                        setIsFormOpen(false);
                        setEditingAddress(null);
                    }}
                    onSave={handleSaveAddress}
                />

                {/* Delete Confirmation Dialog */}
                <DeleteAddressDialog
                    address={deletingAddress}
                    isOpen={isDeleteDialogOpen}
                    onClose={() => {
                        setIsDeleteDialogOpen(false);
                        setDeletingAddress(null);
                    }}
                    onConfirm={handleConfirmDelete}
                />
            </SettingsLayout>
        </AppLayout>
    );
}
