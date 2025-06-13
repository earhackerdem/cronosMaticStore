import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { CheckCircle, Package, Truck, ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

import AppLayout from '@/layouts/app-layout';

interface ConfirmationProps {
    orderNumber: string;
}

export default function Confirmation({ orderNumber }: ConfirmationProps) {
    return (
        <AppLayout>
            <Head title={`Confirmación de Orden ${orderNumber} - CronosMatic`} />

            <div className="min-h-screen bg-gray-50">
                <div className="container mx-auto px-4 py-8">
                    {/* Success Message */}
                    <div className="text-center mb-8">
                        <div className="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                            <CheckCircle className="w-8 h-8 text-green-600" />
                        </div>
                        <h1 className="text-3xl font-bold text-gray-900 mb-2">
                            ¡Pedido confirmado!
                        </h1>
                        <p className="text-lg text-gray-600 mb-4">
                            Gracias por tu compra. Tu pedido ha sido procesado exitosamente.
                        </p>
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 inline-block">
                            <p className="text-sm text-blue-800">
                                <strong>Número de pedido:</strong> {orderNumber}
                            </p>
                        </div>
                    </div>

                    <div className="max-w-2xl mx-auto">
                        {/* What's Next */}
                        <Card className="mb-8">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Package className="w-5 h-5" />
                                    ¿Qué sigue?
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    <div className="flex items-start gap-3">
                                        <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                            <span className="text-sm font-semibold text-blue-600">1</span>
                                        </div>
                                        <div>
                                            <h3 className="font-semibold">Confirmación por email</h3>
                                            <p className="text-sm text-gray-600">
                                                Te enviaremos un email de confirmación con los detalles de tu pedido en los próximos minutos.
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-start gap-3">
                                        <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                            <span className="text-sm font-semibold text-blue-600">2</span>
                                        </div>
                                        <div>
                                            <h3 className="font-semibold">Procesamiento</h3>
                                            <p className="text-sm text-gray-600">
                                                Prepararemos tu pedido y lo enviaremos dentro de 24-48 horas hábiles.
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-start gap-3">
                                        <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                            <Truck className="w-4 h-4 text-blue-600" />
                                        </div>
                                        <div>
                                            <h3 className="font-semibold">Envío</h3>
                                            <p className="text-sm text-gray-600">
                                                Te notificaremos cuando tu pedido esté en camino con un número de seguimiento.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Order Information */}
                        <Card className="mb-8">
                            <CardHeader>
                                <CardTitle>Información del pedido</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid md:grid-cols-2 gap-6">
                                    <div>
                                        <h3 className="font-semibold mb-2">Método de pago</h3>
                                        <p className="text-sm text-gray-600">PayPal</p>
                                        <p className="text-sm text-green-600">✓ Pago confirmado</p>
                                    </div>
                                    <div>
                                        <h3 className="font-semibold mb-2">Método de envío</h3>
                                        <p className="text-sm text-gray-600">Envío estándar</p>
                                        <p className="text-sm text-gray-600">5-7 días hábiles</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Actions */}
                        <div className="flex flex-col sm:flex-row gap-4 justify-center">
                            <Link href="/user/orders">
                                <Button variant="outline" className="flex items-center gap-2 w-full sm:w-auto">
                                    <Package className="w-4 h-4" />
                                    Mis pedidos
                                </Button>
                            </Link>
                            <Link href="/productos">
                                <Button variant="outline" className="flex items-center gap-2 w-full sm:w-auto">
                                    Seguir comprando
                                </Button>
                            </Link>
                        </div>

                        {/* Back to Home */}
                        <div className="text-center mt-8">
                            <Link
                                href="/"
                                className="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors"
                            >
                                <ArrowLeft className="w-4 h-4 mr-2" />
                                Volver al inicio
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
