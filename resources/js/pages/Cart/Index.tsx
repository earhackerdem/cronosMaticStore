import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Minus, Plus, Trash2, ShoppingBag, Package } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import { useCart } from '@/contexts/CartContext';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';

export default function CartIndex() {
    const { cart, isLoading, updateCartItem, removeCartItem, clearCart } = useCart();
    const [updatingItems, setUpdatingItems] = useState<Set<number>>(new Set());

    // Formatear precio en MXN
    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
        }).format(price);
    };

    const handleQuantityChange = async (itemId: number, newQuantity: number) => {
        if (newQuantity < 1) return;

        setUpdatingItems(prev => new Set(prev).add(itemId));
        try {
            await updateCartItem(itemId, newQuantity);
        } catch (error) {
            console.error('Error al actualizar cantidad:', error);
        } finally {
            setUpdatingItems(prev => {
                const newSet = new Set(prev);
                newSet.delete(itemId);
                return newSet;
            });
        }
    };

    const handleRemoveItem = async (itemId: number) => {
        setUpdatingItems(prev => new Set(prev).add(itemId));
        try {
            await removeCartItem(itemId);
        } catch (error) {
            console.error('Error al eliminar item:', error);
        } finally {
            setUpdatingItems(prev => {
                const newSet = new Set(prev);
                newSet.delete(itemId);
                return newSet;
            });
        }
    };

    const handleClearCart = async () => {
        if (window.confirm('¿Estás seguro de que quieres vaciar el carrito?')) {
            try {
                await clearCart();
            } catch (error) {
                console.error('Error al vaciar carrito:', error);
            }
        }
    };

    if (isLoading && !cart) {
        return (
            <AppLayout>
                <Head title="Carrito de Compras - CronosMatic" />
                <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                    <div className="text-center">
                        <div className="w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-4" />
                        <p className="text-gray-600">Cargando carrito...</p>
                    </div>
                </div>
            </AppLayout>
        );
    }

    const isEmpty = !cart || cart.items.length === 0;

    return (
        <AppLayout>
            <Head title="Carrito de Compras - CronosMatic" />

            <div className="min-h-screen bg-gray-50">
                <div className="container mx-auto px-4 py-8">
                    {/* Breadcrumb */}
                    <div className="mb-6">
                        <Link
                            href="/productos"
                            className="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors"
                        >
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Continuar comprando
                        </Link>
                    </div>

                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 mb-2">
                            Carrito de Compras
                        </h1>
                        {!isEmpty && (
                            <p className="text-gray-600">
                                {cart.total_items} {cart.total_items === 1 ? 'artículo' : 'artículos'} en tu carrito
                            </p>
                        )}
                    </div>

                    {isEmpty ? (
                        <Card className="text-center py-12" data-testid="empty-cart-message">
                            <CardContent>
                                <ShoppingBag className="w-24 h-24 mx-auto text-gray-300 mb-6" />
                                <h2 className="text-2xl font-semibold text-gray-900 mb-4">
                                    Tu carrito está vacío
                                </h2>
                                <p className="text-gray-600 mb-8">
                                    Descubre nuestros productos y añade algunos a tu carrito
                                </p>
                                <Link href="/productos">
                                    <Button size="lg" data-testid="continue-shopping-button">
                                        <Package className="w-5 h-5 mr-2" />
                                        Ver productos
                                    </Button>
                                </Link>
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            {/* Lista de productos */}
                            <div className="lg:col-span-2 space-y-4">
                                                                <div className="flex justify-between items-center">
                                    <h2 className="text-xl font-semibold">Productos</h2>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={handleClearCart}
                                        disabled={isLoading}
                                        data-testid="clear-cart-button"
                                    >
                                        <Trash2 className="w-4 h-4 mr-2" />
                                        Vaciar carrito
                                    </Button>
                                </div>

                                {cart.items.map((item) => {
                                    const isUpdating = updatingItems.has(item.id);

                                    return (
                                        <Card key={item.id} className={isUpdating ? 'opacity-50' : ''} data-testid="cart-item">
                                            <CardContent className="p-6">
                                                <div className="flex gap-4">
                                                    {/* Imagen del producto */}
                                                    <div className="flex-shrink-0">
                                                        <div className="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden">
                                                            <img
                                                                src={item.product.image_url}
                                                                alt={item.product.name}
                                                                className="w-full h-full object-cover"
                                                            />
                                                        </div>
                                                    </div>

                                                    {/* Información del producto */}
                                                    <div className="flex-1">
                                                        <div className="flex justify-between items-start mb-2">
                                                            <div>
                                                                <Link
                                                                    href={`/productos/${item.product.slug}`}
                                                                    className="font-semibold text-gray-900 hover:text-blue-600 transition-colors"
                                                                >
                                                                    {item.product.name}
                                                                </Link>
                                                                {item.product.brand && (
                                                                    <p className="text-sm text-gray-600">
                                                                        {item.product.brand}
                                                                    </p>
                                                                )}
                                                            </div>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleRemoveItem(item.id)}
                                                                disabled={isUpdating}
                                                                className="text-red-600 hover:text-red-700 hover:bg-red-50"
                                                                data-testid="remove-item"
                                                            >
                                                                <Trash2 className="w-4 h-4" />
                                                            </Button>
                                                        </div>

                                                        <div className="flex items-center justify-between">
                                                            {/* Controles de cantidad */}
                                                            <div className="flex items-center gap-2">
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    onClick={() => handleQuantityChange(item.id, item.quantity - 1)}
                                                                    disabled={item.quantity <= 1 || isUpdating}
                                                                    className="h-8 w-8 p-0"
                                                                    data-testid="decrement-quantity"
                                                                >
                                                                    <Minus className="w-3 h-3" />
                                                                </Button>
                                                                <span className="w-12 text-center font-medium" data-testid="item-quantity">
                                                                    {item.quantity}
                                                                </span>
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    onClick={() => handleQuantityChange(item.id, item.quantity + 1)}
                                                                    disabled={isUpdating || item.quantity >= item.product.stock_quantity}
                                                                    className="h-8 w-8 p-0"
                                                                    data-testid="increment-quantity"
                                                                >
                                                                    <Plus className="w-3 h-3" />
                                                                </Button>
                                                            </div>

                                                            {/* Precio */}
                                                            <div className="text-right">
                                                                <p className="font-semibold text-lg">
                                                                    {formatPrice(item.product.price * item.quantity)}
                                                                </p>
                                                                <p className="text-sm text-gray-600">
                                                                    {formatPrice(item.product.price)} c/u
                                                                </p>
                                                            </div>
                                                        </div>

                                                        {/* Stock warning */}
                                                        {item.quantity >= item.product.stock_quantity && (
                                                            <div className="mt-2">
                                                                <Badge variant="outline" className="text-orange-600 border-orange-200" data-testid="stock-warning">
                                                                    Stock máximo alcanzado
                                                                </Badge>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    );
                                })}
                            </div>

                            {/* Resumen del pedido */}
                            <div className="lg:col-span-1">
                                <Card className="sticky top-4" data-testid="order-summary">
                                    <CardHeader>
                                        <CardTitle>Resumen del pedido</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="flex justify-between">
                                            <span>Subtotal ({cart.total_items} artículos)</span>
                                            <span>{formatPrice(cart.total_amount)}</span>
                                        </div>

                                        <div className="flex justify-between">
                                            <span>Envío</span>
                                            <span className="text-green-600">Gratis</span>
                                        </div>

                                        <Separator />

                                        <div className="flex justify-between text-lg font-semibold">
                                            <span>Total</span>
                                            <span data-testid="total-amount">{formatPrice(cart.total_amount)}</span>
                                        </div>

                                                                <Link href="/checkout">
                            <Button className="w-full" size="lg" data-testid="checkout-button">
                                Proceder al pago
                            </Button>
                        </Link>

                                        <p className="text-xs text-gray-600 text-center">
                                            Los impuestos se calcularán en el checkout
                                        </p>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
