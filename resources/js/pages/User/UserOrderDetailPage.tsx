import React, { useState, useEffect } from 'react';
import { Link, Head } from '@inertiajs/react';
import { Package, ArrowLeft, Loader2, User, Mail } from 'lucide-react';
import { Order } from '@/types';
import { UserOrderApi } from '@/lib/api';
import { formatCurrency } from '@/lib/utils';

const STATUS_COLORS = {
    pendiente_pago: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    procesando: 'bg-blue-100 text-blue-800 border-blue-200',
    enviado: 'bg-purple-100 text-purple-800 border-purple-200',
    entregado: 'bg-green-100 text-green-800 border-green-200',
    cancelado: 'bg-red-100 text-red-800 border-red-200',
};

const PAYMENT_STATUS_COLORS = {
    pendiente: 'bg-yellow-100 text-yellow-800',
    pagado: 'bg-green-100 text-green-800',
    fallido: 'bg-red-100 text-red-800',
    reembolsado: 'bg-gray-100 text-gray-800',
};

interface UserOrderDetailPageProps {
    orderNumber: string;
}

export default function UserOrderDetailPage({ orderNumber }: UserOrderDetailPageProps) {
    const [order, setOrder] = useState<Order | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchOrder = async () => {
            try {
                setLoading(true);
                setError(null);
                const response = await UserOrderApi.getUserOrder(orderNumber);
                setOrder(response.data.order);
            } catch (err) {
                setError(err instanceof Error ? err.message : 'Error al cargar el pedido');
            } finally {
                setLoading(false);
            }
        };

        fetchOrder();
    }, [orderNumber]);

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    if (loading) {
        return (
            <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                <Head title={`Pedido ${orderNumber}`} />
                <div className="text-center">
                    <Loader2 className="w-8 h-8 animate-spin mx-auto mb-4 text-blue-600" />
                    <p className="text-gray-600">Cargando detalles del pedido...</p>
                </div>
            </div>
        );
    }

    if (error || !order) {
        return (
            <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                <Head title={`Pedido ${orderNumber}`} />
                <div className="text-center max-w-md mx-auto">
                    <Package className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                    <h2 className="text-2xl font-bold text-gray-700 mb-2">Pedido no encontrado</h2>
                    <p className="text-gray-500 mb-6">
                        {error || 'No se pudo encontrar el pedido solicitado.'}
                    </p>
                    <Link
                        href="/user/orders"
                        className="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors gap-2"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Volver a Mis Pedidos
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50">
            <Head title={`Pedido ${order.order_number}`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="mb-8">
                    <Link
                        href="/user/orders"
                        className="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4 gap-2"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Volver a Mis Pedidos
                    </Link>

                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                                <Package className="w-8 h-8 text-blue-600" />
                                Pedido {order.order_number}
                            </h1>
                            <p className="text-gray-600 mt-2">
                                Realizado el {formatDate(order.created_at)}
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <span className={`inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium border ${STATUS_COLORS[order.status]}`}>
                                {order.status_label}
                            </span>
                            <span className={`inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium ${PAYMENT_STATUS_COLORS[order.payment_status]}`}>
                                {order.payment_status_label}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div className="lg:col-span-2 space-y-6">
                        <div className="bg-white rounded-lg shadow-sm p-6">
                            <h2 className="text-xl font-semibold text-gray-900 mb-4">Productos</h2>
                            <div className="space-y-4">
                                {order.order_items?.map((item) => (
                                    <div key={item.id} className="flex items-center gap-4 p-4 border border-gray-200 rounded-lg">
                                        <div className="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden">
                                            <img
                                                src={item.product.image_url}
                                                alt={item.product.name}
                                                className="w-full h-full object-cover"
                                            />
                                        </div>

                                        <div className="flex-1">
                                            <h3 className="font-semibold text-gray-900">{item.product.name}</h3>
                                            <p className="text-gray-600 text-sm">
                                                SKU: {item.product.sku || 'N/A'}
                                            </p>
                                            <div className="flex items-center justify-between mt-2">
                                                <span className="text-gray-600">
                                                    Cantidad: {item.quantity}
                                                </span>
                                                <div className="text-right">
                                                    <p className="text-gray-600 text-sm">
                                                        {formatCurrency(parseFloat(item.unit_price))} c/u
                                                    </p>
                                                    <p className="font-semibold text-gray-900">
                                                        {formatCurrency(parseFloat(item.total_price))}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="space-y-6">
                        <div className="bg-white rounded-lg shadow-sm p-6">
                            <h2 className="text-xl font-semibold text-gray-900 mb-4">Resumen del Pedido</h2>
                            <div className="space-y-3">
                                <div className="flex justify-between text-gray-600">
                                    <span>Subtotal</span>
                                    <span>{formatCurrency(parseFloat(order.subtotal_amount))}</span>
                                </div>
                                <div className="flex justify-between text-gray-600">
                                    <span>Envío</span>
                                    <span>{formatCurrency(parseFloat(order.shipping_cost))}</span>
                                </div>
                                <div className="border-t pt-3">
                                    <div className="flex justify-between text-lg font-semibold text-gray-900">
                                        <span>Total</span>
                                        <span>{formatCurrency(parseFloat(order.total_amount))}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow-sm p-6">
                            <h2 className="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <User className="w-5 h-5" />
                                Información del Cliente
                            </h2>
                            <div className="space-y-3">
                                {order.user ? (
                                    <>
                                        <div className="flex items-center gap-2">
                                            <User className="w-4 h-4 text-gray-400" />
                                            <span className="text-gray-600">{order.user.name}</span>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Mail className="w-4 h-4 text-gray-400" />
                                            <span className="text-gray-600">{order.user.email}</span>
                                        </div>
                                    </>
                                ) : (
                                    <div className="flex items-center gap-2">
                                        <Mail className="w-4 h-4 text-gray-400" />
                                        <span className="text-gray-600">{order.guest_email}</span>
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow-sm p-6">
                            <div className="space-y-3">
                                <Link
                                    href="/productos"
                                    className="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                >
                                    Seguir Comprando
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
