import React, { useState, useEffect } from 'react';
import { Link, Head } from '@inertiajs/react';
import { Package, Search, Calendar, Eye, Loader2 } from 'lucide-react';
import { Order, OrdersPaginatedResponse } from '@/types';
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

export default function UserOrdersPage() {
    const [orders, setOrders] = useState<Order[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [currentPage, setCurrentPage] = useState(1);
    const [pagination, setPagination] = useState<OrdersPaginatedResponse['data']['pagination'] | null>(null);
    const [searchTerm, setSearchTerm] = useState('');

    const fetchOrders = async (page: number = 1) => {
        try {
            setLoading(true);
            setError(null);
            const response = await UserOrderApi.getUserOrders(page, 10);
            setOrders(response.data.orders);
            setPagination(response.data.pagination);
            setCurrentPage(page);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Error al cargar los pedidos');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchOrders();
    }, []);

    const handlePageChange = (page: number) => {
        fetchOrders(page);
    };

    const filteredOrders = orders.filter(order =>
        order.order_number.toLowerCase().includes(searchTerm.toLowerCase()) ||
        order.status_label.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    if (loading && orders.length === 0) {
        return (
            <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                <Head title="Mis Pedidos" />
                <div className="text-center">
                    <Loader2 className="w-8 h-8 animate-spin mx-auto mb-4 text-blue-600" />
                    <p className="text-gray-600">Cargando pedidos...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50">
            <Head title="Mis Pedidos" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header */}
                <div className="mb-8">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                                <Package className="w-8 h-8 text-blue-600" />
                                Mis Pedidos
                            </h1>
                            <p className="text-gray-600 mt-2">
                                Revisa el estado de tus pedidos y su historial
                            </p>
                        </div>
                        <Link
                            href="/"
                            className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            Seguir Comprando
                        </Link>
                    </div>
                </div>

                {/* Search and Filters */}
                <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div className="flex flex-col md:flex-row gap-4">
                        <div className="flex-1 relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                            <input
                                type="text"
                                placeholder="Buscar por número de pedido o estado..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                    </div>
                </div>

                {error && (
                    <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <p className="text-red-800">{error}</p>
                        <button
                            onClick={() => fetchOrders(currentPage)}
                            className="mt-2 text-red-600 hover:text-red-800 underline"
                        >
                            Reintentar
                        </button>
                    </div>
                )}

                {/* Orders List */}
                {filteredOrders.length === 0 ? (
                    <div className="bg-white rounded-lg shadow-sm p-12 text-center">
                        <Package className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                        <h3 className="text-xl font-semibold text-gray-700 mb-2">
                            {searchTerm ? 'No se encontraron pedidos' : 'No tienes pedidos aún'}
                        </h3>
                        <p className="text-gray-500 mb-6">
                            {searchTerm
                                ? 'Intenta con otros términos de búsqueda'
                                : 'Cuando realices tu primera compra, aparecerá aquí'
                            }
                        </p>
                        {!searchTerm && (
                            <Link
                                href="/productos"
                                className="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                            >
                                Explorar Productos
                            </Link>
                        )}
                    </div>
                ) : (
                    <div className="space-y-4">
                        {filteredOrders.map((order) => (
                            <div key={order.id} className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                                <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-4 mb-3">
                                            <h3 className="text-lg font-semibold text-gray-900">
                                                {order.order_number}
                                            </h3>
                                            <div className="flex gap-2">
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${STATUS_COLORS[order.status]}`}>
                                                    {order.status_label}
                                                </span>
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${PAYMENT_STATUS_COLORS[order.payment_status]}`}>
                                                    {order.payment_status_label}
                                                </span>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                                            <div className="flex items-center gap-2">
                                                <Calendar className="w-4 h-4" />
                                                <span>{formatDate(order.created_at)}</span>
                                            </div>
                                            <div>
                                                <span className="font-medium">Total: </span>
                                                <span className="text-gray-900 font-semibold">
                                                    {formatCurrency(parseFloat(order.total_amount))}
                                                </span>
                                            </div>
                                            <div>
                                                <span className="font-medium">Artículos: </span>
                                                <span className="text-gray-900">
                                                    {order.order_items?.length || 0}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="mt-4 lg:mt-0 lg:ml-6">
                                        <Link
                                            href={`/user/orders/${order.order_number}`}
                                            className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors gap-2"
                                        >
                                            <Eye className="w-4 h-4" />
                                            Ver Detalles
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Pagination */}
                {pagination && pagination.total > pagination.per_page && (
                    <div className="mt-8 flex justify-center">
                        <div className="flex items-center gap-2">
                            <button
                                onClick={() => handlePageChange(currentPage - 1)}
                                disabled={currentPage === 1 || loading}
                                className="px-3 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Anterior
                            </button>

                            <span className="px-4 py-2 text-sm text-gray-700">
                                Página {pagination.current_page} de {pagination.last_page}
                            </span>

                            <button
                                onClick={() => handlePageChange(currentPage + 1)}
                                disabled={currentPage === pagination.last_page || loading}
                                className="px-3 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Siguiente
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
