import React, { useState, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Lock, CreditCard, Truck, MapPin, Receipt } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import { useCart } from '@/contexts/CartContext';
import { useAddresses } from '@/hooks/use-addresses';
import { Address, SharedData } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { AddressForm } from '@/components/address-form';
import { toast } from 'sonner';
import { CreateAddressData } from '@/lib/address-api';

interface CheckoutStep {
    id: number;
    title: string;
    icon: React.ReactNode;
    completed: boolean;
}

// Simple Progress component
const Progress = ({ value, className }: { value: number; className?: string }) => (
    <div className={`w-full bg-gray-200 rounded-full h-2 ${className}`}>
        <div
            className="bg-blue-600 h-2 rounded-full transition-all duration-300"
            style={{ width: `${value}%` }}
        />
    </div>
);

// Guest Address interface for manual entry
interface GuestAddress {
    id?: string;
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
    full_name?: string;
    full_address?: string;
}

// Order data interface
interface OrderData {
    payment_method: string;
    shipping_cost: number;
    shipping_method_name: string;
    notes: string;
    shipping_address_id?: number;
    billing_address_id?: number;
    guest_email?: string;
    shipping_address?: {
        first_name: string;
        last_name: string;
        company: string;
        address_line_1: string;
        address_line_2: string;
        city: string;
        state: string;
        postal_code: string;
        country: string;
        phone: string;
    };
    billing_address?: {
        first_name: string;
        last_name: string;
        company: string;
        address_line_1: string;
        address_line_2: string;
        city: string;
        state: string;
        postal_code: string;
        country: string;
        phone: string;
    };
}

export default function CheckoutIndex() {
    const { auth } = usePage<SharedData>().props;
    const isAuthenticated = auth && auth.user;

    const { cart, isLoading: cartLoading, refreshCart } = useCart();
    const { addresses, isLoading: addressesLoading, createAddress } = useAddresses();

    const [currentStep, setCurrentStep] = useState(1);
    const [selectedShippingAddress, setSelectedShippingAddress] = useState<Address | GuestAddress | null>(null);
    const [selectedBillingAddress, setSelectedBillingAddress] = useState<Address | GuestAddress | null>(null);
    const [useSameAddress, setUseSameAddress] = useState(true);
    const [shippingMethod, setShippingMethod] = useState('standard');
    const [showAddressForm, setShowAddressForm] = useState(false);

    const [isCreatingOrder, setIsCreatingOrder] = useState(false);
    const [guestEmail, setGuestEmail] = useState('');

    // Formatear precio en MXN
    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
        }).format(price);
    };

    // Helper to create full address string for guest addresses
    const createFullAddress = (addr: GuestAddress): GuestAddress => ({
        ...addr,
        full_name: `${addr.first_name} ${addr.last_name}`,
        full_address: `${addr.address_line_1}${addr.address_line_2 ? ', ' + addr.address_line_2 : ''}, ${addr.city}, ${addr.state} ${addr.postal_code}, ${addr.country}`
    });

    // Definir los pasos del checkout
    const steps: CheckoutStep[] = [
        {
            id: 1,
            title: 'Dirección de envío',
            icon: <MapPin className="w-5 h-5" />,
            completed: !!selectedShippingAddress
        },
        {
            id: 2,
            title: 'Dirección de facturación',
            icon: <Receipt className="w-5 h-5" />,
            completed: useSameAddress ? !!selectedShippingAddress : !!selectedBillingAddress
        },
        {
            id: 3,
            title: 'Método de envío',
            icon: <Truck className="w-5 h-5" />,
            completed: !!shippingMethod
        },
        {
            id: 4,
            title: 'Resumen y pago',
            icon: <CreditCard className="w-5 h-5" />,
            completed: false
        }
    ];

    // Calcular progreso
    const progress = (steps.filter(step => step.completed).length / steps.length) * 100;

    // Efecto para seleccionar la dirección predeterminada (solo para usuarios autenticados)
    useEffect(() => {
        if (isAuthenticated && addresses.length > 0 && !selectedShippingAddress) {
            const defaultShipping = addresses.find(addr => addr.type === 'shipping' && addr.is_default);
            if (defaultShipping) {
                setSelectedShippingAddress(defaultShipping);
            }
        }
    }, [addresses, selectedShippingAddress, isAuthenticated]);

    // Navegar entre pasos
    const goToStep = (step: number) => {
        if (step <= currentStep || steps[step - 1].completed) {
            setCurrentStep(step);
        }
    };

    const goToNextStep = () => {
        if (currentStep < steps.length) {
            setCurrentStep(currentStep + 1);
        }
    };

    const goToPreviousStep = () => {
        if (currentStep > 1) {
            setCurrentStep(currentStep - 1);
        }
    };

    // Manejar selección de dirección
    const handleSelectShippingAddress = (address: Address | GuestAddress) => {
        setSelectedShippingAddress(address);
        if (useSameAddress) {
            setSelectedBillingAddress(address);
        }
    };

    const handleSelectBillingAddress = (address: Address | GuestAddress) => {
        setSelectedBillingAddress(address);
    };

    // Manejar creación de nueva dirección
    const handleCreateAddress = () => {
        setShowAddressForm(true);
    };

    const handleSaveAddress = async (data: CreateAddressData | Partial<CreateAddressData>) => {
        const fullData = data as CreateAddressData;

        if (isAuthenticated) {
            // Para usuarios autenticados, usar la API
            const newAddress = await createAddress(fullData);
            if (newAddress) {
                if (fullData.type === 'shipping') {
                    setSelectedShippingAddress(newAddress);
                    if (useSameAddress) {
                        setSelectedBillingAddress(newAddress);
                    }
                } else {
                    setSelectedBillingAddress(newAddress);
                }
                setShowAddressForm(false);
            }
        } else {
            // Para usuarios invitados, crear dirección temporal
            const guestAddress: GuestAddress = {
                id: Date.now().toString(), // ID temporal
                ...fullData
            };

            const addressWithFullData = createFullAddress(guestAddress);

            if (fullData.type === 'shipping') {
                setSelectedShippingAddress(addressWithFullData);
                if (useSameAddress) {
                    setSelectedBillingAddress(addressWithFullData);
                }
            } else {
                setSelectedBillingAddress(addressWithFullData);
            }

            setShowAddressForm(false);
            toast.success('Dirección agregada exitosamente');
        }
    };

        // Crear orden
    const createOrder = async () => {
        console.log('createOrder function called');
        console.log('Cart:', cart);
        console.log('selectedShippingAddress:', selectedShippingAddress);
        console.log('selectedBillingAddress:', selectedBillingAddress);
        console.log('useSameAddress:', useSameAddress);

        if (!cart || !selectedShippingAddress) {
            console.log('Missing required data for order creation');
            toast.error('Faltan datos requeridos para crear la orden');
            return;
        }

        if (!useSameAddress && !selectedBillingAddress) {
            console.log('Billing address is required when not using same address');
            toast.error('Selecciona una dirección de facturación');
            return;
        }

        // Validate guest email for non-authenticated users
        if (!isAuthenticated && !guestEmail.trim()) {
            toast.error('El email es requerido para usuarios invitados');
            return;
        }

        setIsCreatingOrder(true);
        console.log('Starting order creation...');

        try {
            // Prepare order data based on authentication status
            const orderData: OrderData = {
                payment_method: 'paypal',
                shipping_cost: 0.00,
                shipping_method_name: shippingMethod === 'standard' ? 'Envío Estándar' : 'Envío Express',
                notes: ''
            };

            if (isAuthenticated) {
                // For authenticated users, use address IDs
                orderData.shipping_address_id = (selectedShippingAddress as Address).id;
                orderData.billing_address_id = useSameAddress
                    ? (selectedShippingAddress as Address).id
                    : (selectedBillingAddress as Address)?.id;
            } else {
                // For guest users, include email and full address data
                orderData.guest_email = guestEmail;

                const shippingAddr = selectedShippingAddress as GuestAddress;
                orderData.shipping_address = {
                    first_name: shippingAddr.first_name || '',
                    last_name: shippingAddr.last_name || '',
                    company: shippingAddr.company || '',
                    address_line_1: shippingAddr.address_line_1 || '',
                    address_line_2: shippingAddr.address_line_2 || '',
                    city: shippingAddr.city || '',
                    state: shippingAddr.state || '',
                    postal_code: shippingAddr.postal_code || '',
                    country: shippingAddr.country || '',
                    phone: shippingAddr.phone || ''
                };

                const billingAddr = useSameAddress ? selectedShippingAddress as GuestAddress : selectedBillingAddress as GuestAddress;
                orderData.billing_address = {
                    first_name: billingAddr.first_name || '',
                    last_name: billingAddr.last_name || '',
                    company: billingAddr.company || '',
                    address_line_1: billingAddr.address_line_1 || '',
                    address_line_2: billingAddr.address_line_2 || '',
                    city: billingAddr.city || '',
                    state: billingAddr.state || '',
                    postal_code: billingAddr.postal_code || '',
                    country: billingAddr.country || '',
                    phone: billingAddr.phone || ''
                };
            }

            console.log('Order data:', orderData);

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            console.log('CSRF Token:', csrfToken);

            const headers: Record<string, string> = {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'application/json',
            };

            // For guest users, add the session ID header to help backend find the cart
            if (!isAuthenticated && cart?.session_id) {
                headers['X-Session-ID'] = cart.session_id;
            }

            const response = await fetch('/api/v1/orders', {
                method: 'POST',
                headers,
                body: JSON.stringify(orderData)
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Response not ok:', response.status, errorText);
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            const result = await response.json();
            console.log('API Response:', result);

            if (result.success) {
                // La orden fue creada exitosamente y el pago fue simulado
                toast.success('¡Orden creada exitosamente!');
                console.log('Order created successfully, redirecting to confirmation');

                // Refresh cart to reflect that it's been cleared
                try {
                    await refreshCart();
                    console.log('Cart refreshed after successful order');
                } catch (cartError) {
                    console.warn('Failed to refresh cart after order:', cartError);
                    // Don't block the redirect if cart refresh fails
                }

                router.visit(`/orders/confirmation/${result.data.order.order_number}`);
            } else {
                console.error('Order creation failed:', result);
                toast.error(result.message || 'Error al crear la orden');

                // Show detailed errors if available
                if (result.errors) {
                    Object.values(result.errors).flat().forEach((error: unknown) => {
                        toast.error(String(error));
                    });
                }
            }
        } catch (error) {
            console.error('Error creating order:', error);
            const errorMessage = error instanceof Error ? error.message : 'Error desconocido';
            toast.error(`Error al crear la orden: ${errorMessage}`);
        } finally {
            console.log('Order creation process finished');
            setIsCreatingOrder(false);
        }
    };

    // Verificar si el carrito está vacío
    if (!cartLoading && (!cart || cart.items.length === 0)) {
        return (
            <AppLayout>
                <Head title="Checkout - CronosMatic" />
                <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                    <Card className="text-center p-8">
                        <CardContent>
                            <h2 className="text-2xl font-semibold mb-4">Tu carrito está vacío</h2>
                            <p className="text-gray-600 mb-6">
                                Necesitas productos en tu carrito para proceder con el checkout
                            </p>
                            <Link href="/productos">
                                <Button>Ver productos</Button>
                            </Link>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    if (cartLoading || addressesLoading) {
        return (
            <AppLayout>
                <Head title="Checkout - CronosMatic" />
                <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                    <div className="text-center">
                        <div className="w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-4" />
                        <p className="text-gray-600">Cargando checkout...</p>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title="Checkout - CronosMatic" />

            <div className="min-h-screen bg-gray-50">
                <div className="container mx-auto px-4 py-8">
                    {/* Breadcrumb */}
                    <div className="mb-6">
                        <Link
                            href="/carrito"
                            className="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors"
                        >
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Volver al carrito
                        </Link>
                    </div>

                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 mb-4">Checkout</h1>

                        {/* Progress Bar */}
                        <div className="mb-6">
                            <Progress value={progress} className="h-2 mb-4" />
                            <div className="flex justify-between">
                                {steps.map((step) => (
                                    <div
                                        key={step.id}
                                        className={`flex flex-col items-center cursor-pointer ${
                                            step.id === currentStep ? 'text-blue-600' :
                                            step.completed ? 'text-green-600' : 'text-gray-400'
                                        }`}
                                        onClick={() => goToStep(step.id)}
                                    >
                                        <div className={`w-10 h-10 rounded-full flex items-center justify-center mb-2 ${
                                            step.id === currentStep ? 'bg-blue-100 border-2 border-blue-600' :
                                            step.completed ? 'bg-green-100 border-2 border-green-600' : 'bg-gray-100'
                                        }`}>
                                            {step.icon}
                                        </div>
                                        <span className="text-sm font-medium text-center">{step.title}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Main Content */}
                        <div className="lg:col-span-2">
                            {/* Email para usuarios invitados */}
                            {!isAuthenticated && currentStep === 1 && (
                                <Card className="mb-6">
                                    <CardHeader>
                                        <CardTitle>Información de contacto</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div>
                                            <label htmlFor="guest-email" className="block text-sm font-medium text-gray-700 mb-2">
                                                Email *
                                            </label>
                                            <input
                                                id="guest-email"
                                                type="email"
                                                value={guestEmail}
                                                onChange={(e) => setGuestEmail(e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                placeholder="tu@email.com"
                                                required
                                            />
                                            <p className="text-xs text-gray-500 mt-1">
                                                Recibirás la confirmación de tu pedido en este email
                                            </p>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Paso 1: Dirección de envío */}
                            {currentStep === 1 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <MapPin className="w-5 h-5" />
                                            Dirección de envío
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        {isAuthenticated ? (
                                            // Sección for authenticated users
                                            addresses.filter(addr => addr.type === 'shipping').length === 0 ? (
                                                <div className="text-center py-6">
                                                    <p className="text-gray-600 mb-4">No tienes direcciones de envío guardadas</p>
                                                    <Button onClick={() => handleCreateAddress()}>
                                                        Agregar dirección de envío
                                                    </Button>
                                                </div>
                                            ) : (
                                                <>
                                                    <div className="grid gap-4">
                                                        {addresses
                                                            .filter(addr => addr.type === 'shipping')
                                                            .map((address) => (
                                                                <div
                                                                    key={address.id}
                                                                    className={`border rounded-lg p-4 cursor-pointer transition-colors ${
                                                                        selectedShippingAddress?.id === address.id
                                                                            ? 'border-blue-500 bg-blue-50'
                                                                            : 'border-gray-200 hover:border-gray-300'
                                                                    }`}
                                                                    onClick={() => handleSelectShippingAddress(address)}
                                                                >
                                                                    <div className="flex items-start justify-between">
                                                                        <div>
                                                                            <p className="font-medium">{address.full_name}</p>
                                                                            <p className="text-sm text-gray-600">{address.full_address}</p>
                                                                            {address.phone && (
                                                                                <p className="text-sm text-gray-600">{address.phone}</p>
                                                                            )}
                                                                        </div>
                                                                        {address.is_default && (
                                                                            <Badge variant="secondary">Predeterminada</Badge>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            ))}
                                                    </div>
                                                    <Button
                                                        variant="outline"
                                                        onClick={() => handleCreateAddress()}
                                                        className="w-full"
                                                    >
                                                        Agregar nueva dirección
                                                    </Button>
                                                </>
                                            )
                                        ) : (
                                            // Section for guest users
                                            <div className="space-y-4">
                                                {selectedShippingAddress ? (
                                                    <div className="border rounded-lg p-4 bg-blue-50 border-blue-200">
                                                        <div className="flex items-start justify-between">
                                                            <div>
                                                                <p className="font-medium">{selectedShippingAddress.full_name}</p>
                                                                <p className="text-sm text-gray-600">{selectedShippingAddress.full_address}</p>
                                                                {selectedShippingAddress.phone && (
                                                                    <p className="text-sm text-gray-600">{selectedShippingAddress.phone}</p>
                                                                )}
                                                            </div>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => handleCreateAddress()}
                                                            >
                                                                Editar
                                                            </Button>
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <div className="text-center py-6">
                                                        <p className="text-gray-600 mb-4">Agrega tu dirección de envío</p>
                                                        <Button onClick={() => handleCreateAddress()}>
                                                            Agregar dirección de envío
                                                        </Button>
                                                    </div>
                                                )}
                                            </div>
                                        )}

                                        {selectedShippingAddress && (isAuthenticated || guestEmail.trim()) && (
                                            <div className="flex justify-end pt-4">
                                                <Button onClick={goToNextStep}>
                                                    Continuar
                                                </Button>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            )}

                            {/* Paso 2: Dirección de facturación */}
                            {currentStep === 2 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <Receipt className="w-5 h-5" />
                                            Dirección de facturación
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="flex items-center space-x-2">
                                            <input
                                                type="checkbox"
                                                id="sameAddress"
                                                checked={useSameAddress}
                                                onChange={(e) => {
                                                    setUseSameAddress(e.target.checked);
                                                    if (e.target.checked) {
                                                        setSelectedBillingAddress(selectedShippingAddress);
                                                    } else {
                                                        setSelectedBillingAddress(null);
                                                    }
                                                }}
                                                className="rounded"
                                            />
                                            <label htmlFor="sameAddress" className="text-sm font-medium">
                                                Usar la misma dirección de envío
                                            </label>
                                        </div>

                                        {!useSameAddress && (
                                            <>
                                                {addresses.filter(addr => addr.type === 'billing').length === 0 ? (
                                                    <div className="text-center py-6">
                                                        <p className="text-gray-600 mb-4">No tienes direcciones de facturación guardadas</p>
                                                        <Button onClick={() => handleCreateAddress()}>
                                                            Agregar dirección de facturación
                                                        </Button>
                                                    </div>
                                                ) : (
                                                    <>
                                                        <div className="grid gap-4">
                                                            {addresses
                                                                .filter(addr => addr.type === 'billing')
                                                                .map((address) => (
                                                                    <div
                                                                        key={address.id}
                                                                        className={`border rounded-lg p-4 cursor-pointer transition-colors ${
                                                                            selectedBillingAddress?.id === address.id
                                                                                ? 'border-blue-500 bg-blue-50'
                                                                                : 'border-gray-200 hover:border-gray-300'
                                                                        }`}
                                                                        onClick={() => handleSelectBillingAddress(address)}
                                                                    >
                                                                        <div className="flex items-start justify-between">
                                                                            <div>
                                                                                <p className="font-medium">{address.full_name}</p>
                                                                                <p className="text-sm text-gray-600">{address.full_address}</p>
                                                                                {address.phone && (
                                                                                    <p className="text-sm text-gray-600">{address.phone}</p>
                                                                                )}
                                                                            </div>
                                                                            {address.is_default && (
                                                                                <Badge variant="secondary">Predeterminada</Badge>
                                                                            )}
                                                                        </div>
                                                                    </div>
                                                                ))}
                                                        </div>
                                                                                                                <Button
                                                            variant="outline"
                                                            onClick={() => handleCreateAddress()}
                                                            className="w-full"
                                                        >
                                                            Agregar nueva dirección
                                                        </Button>
                                                    </>
                                                )}
                                            </>
                                        )}

                                        <div className="flex justify-between pt-4">
                                            <Button variant="outline" onClick={goToPreviousStep}>
                                                Anterior
                                            </Button>
                                            <Button
                                                onClick={goToNextStep}
                                                disabled={!useSameAddress && !selectedBillingAddress}
                                            >
                                                Continuar
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Paso 3: Método de envío */}
                            {currentStep === 3 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <Truck className="w-5 h-5" />
                                            Método de envío
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="space-y-3">
                                            <div
                                                className={`border rounded-lg p-4 cursor-pointer transition-colors ${
                                                    shippingMethod === 'standard'
                                                        ? 'border-blue-500 bg-blue-50'
                                                        : 'border-gray-200 hover:border-gray-300'
                                                }`}
                                                onClick={() => setShippingMethod('standard')}
                                            >
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <p className="font-medium">Envío Estándar</p>
                                                        <p className="text-sm text-gray-600">5-7 días hábiles</p>
                                                    </div>
                                                    <div className="text-right">
                                                        <p className="font-semibold text-green-600">Gratis</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div
                                                className={`border rounded-lg p-4 cursor-pointer transition-colors ${
                                                    shippingMethod === 'express'
                                                        ? 'border-blue-500 bg-blue-50'
                                                        : 'border-gray-200 hover:border-gray-300'
                                                }`}
                                                onClick={() => setShippingMethod('express')}
                                            >
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <p className="font-medium">Envío Express</p>
                                                        <p className="text-sm text-gray-600">1-2 días hábiles</p>
                                                    </div>
                                                    <div className="text-right">
                                                        <p className="font-semibold text-green-600">Gratis</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="flex justify-between pt-4">
                                            <Button variant="outline" onClick={goToPreviousStep}>
                                                Anterior
                                            </Button>
                                            <Button onClick={goToNextStep}>
                                                Continuar
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Paso 4: Resumen y pago */}
                            {currentStep === 4 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <CreditCard className="w-5 h-5" />
                                            Resumen y pago
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        {/* Resumen de direcciones */}
                                        <div>
                                            <h3 className="font-semibold mb-3">Direcciones</h3>
                                            <div className="grid md:grid-cols-2 gap-4">
                                                <div className="border rounded-lg p-3">
                                                    <p className="font-medium text-sm mb-1">Envío</p>
                                                    <p className="text-sm">{selectedShippingAddress?.full_name}</p>
                                                    <p className="text-sm text-gray-600">{selectedShippingAddress?.full_address}</p>
                                                </div>
                                                <div className="border rounded-lg p-3">
                                                    <p className="font-medium text-sm mb-1">Facturación</p>
                                                    <p className="text-sm">
                                                        {useSameAddress ? selectedShippingAddress?.full_name : selectedBillingAddress?.full_name}
                                                    </p>
                                                    <p className="text-sm text-gray-600">
                                                        {useSameAddress ? selectedShippingAddress?.full_address : selectedBillingAddress?.full_address}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Resumen de productos */}
                                        <div>
                                            <h3 className="font-semibold mb-3">Productos</h3>
                                            <div className="space-y-2">
                                                {cart?.items.map((item) => (
                                                    <div key={item.id} className="flex justify-between items-center py-2 border-b border-gray-100">
                                                        <div className="flex items-center gap-3">
                                                            <div className="w-12 h-12 bg-gray-100 rounded overflow-hidden">
                                                                {item.product.image_url ? (
                                                                    <img
                                                                        src={item.product.image_url}
                                                                        alt={item.product.name}
                                                                        className="w-full h-full object-cover"
                                                                    />
                                                                ) : (
                                                                    <div className="w-full h-full bg-gray-200" />
                                                                )}
                                                            </div>
                                                            <div>
                                                                <p className="font-medium text-sm">{item.product.name}</p>
                                                                <p className="text-sm text-gray-600">Cantidad: {item.quantity}</p>
                                                            </div>
                                                        </div>
                                                        <p className="font-semibold">
                                                            {formatPrice(item.product.price * item.quantity)}
                                                        </p>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        {/* Método de pago */}
                                        <div>
                                            <h3 className="font-semibold mb-3">Método de pago</h3>
                                            <div className="border rounded-lg p-4 flex items-center gap-3 bg-blue-50 border-blue-200">
                                                <Lock className="w-5 h-5 text-blue-600" />
                                                <div>
                                                    <p className="font-medium">Pago con PayPal</p>
                                                    <p className="text-sm text-gray-600">Pago seguro procesado por PayPal</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="flex justify-between pt-4">
                                            <Button variant="outline" onClick={goToPreviousStep}>
                                                Anterior
                                            </Button>
                                            <Button
                                                onClick={createOrder}
                                                disabled={isCreatingOrder}
                                                className="bg-blue-600 hover:bg-blue-700"
                                            >
                                                {isCreatingOrder ? (
                                                    <>
                                                        <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2" />
                                                        Procesando...
                                                    </>
                                                ) : (
                                                    'Finalizar pedido'
                                                )}
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}
                        </div>

                        {/* Sidebar - Resumen del pedido */}
                        <div className="lg:col-span-1">
                            <Card className="sticky top-4">
                                <CardHeader>
                                    <CardTitle>Resumen del pedido</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex justify-between">
                                        <span>Subtotal ({cart?.total_items} artículos)</span>
                                        <span>{formatPrice(cart?.total_amount || 0)}</span>
                                    </div>

                                    <div className="flex justify-between">
                                        <span>Envío</span>
                                        <span className="text-green-600">Gratis</span>
                                    </div>

                                    <Separator />

                                    <div className="flex justify-between text-lg font-semibold">
                                        <span>Total</span>
                                        <span>{formatPrice(cart?.total_amount || 0)}</span>
                                    </div>

                                    <div className="text-xs text-gray-600 text-center">
                                        <Lock className="w-3 h-3 inline mr-1" />
                                        Pago seguro con cifrado SSL
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>

            {/* Address Form Modal */}
            <AddressForm
                isOpen={showAddressForm}
                onClose={() => setShowAddressForm(false)}
                onSave={handleSaveAddress}
                address={null}
            />
        </AppLayout>
    );
}
