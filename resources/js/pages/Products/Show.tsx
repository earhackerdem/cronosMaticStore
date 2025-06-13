import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Package, Star, Shield, Truck } from 'lucide-react';
import { Product } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { AddToCartButton } from '@/components/add-to-cart-button';
import AppLayout from '@/layouts/app-layout';

interface ProductShowProps {
    product: Product;
}

export default function ProductShow({ product }: ProductShowProps) {
    // Formatear precio en MXN
    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
        }).format(price);
    };

    // Determinar estado del stock
    const getStockStatus = () => {
        if (product.stock_quantity === 0) {
            return { text: 'Agotado', color: 'text-red-600', bgColor: 'bg-red-50' };
        } else if (product.stock_quantity <= 5) {
            return { text: `Pocas unidades (${product.stock_quantity})`, color: 'text-orange-600', bgColor: 'bg-orange-50' };
        } else {
            return { text: `En stock (${product.stock_quantity})`, color: 'text-green-600', bgColor: 'bg-green-50' };
        }
    };

    const stockStatus = getStockStatus();

    return (
        <AppLayout>
            <Head title={`${product.name} - CronosMatic`} />

            <div className="min-h-screen bg-gray-50">
                <div className="container mx-auto px-4 py-8">
                    {/* Breadcrumb */}
                    <div className="mb-6">
                        <Link
                            href="/productos"
                            className="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors"
                        >
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Volver al catálogo
                        </Link>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        {/* Imagen del producto */}
                        <div className="space-y-4">
                            <Card>
                                <CardContent className="p-0">
                                    <div className="aspect-square overflow-hidden rounded-lg bg-gray-100">
                                        <img
                                            src={product.image_url}
                                            alt={product.name}
                                            className="w-full h-full object-cover"
                                        />
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Información del producto */}
                        <div className="space-y-6">
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900 mb-2">
                                    {product.name}
                                </h1>
                                {product.brand && (
                                    <p className="text-lg text-gray-600 mb-4">
                                        Marca: <span className="font-semibold">{product.brand}</span>
                                    </p>
                                )}

                                <div className="flex items-center gap-3 mb-4">
                                    {product.category && (
                                        <Badge variant="secondary" className="text-sm">
                                            {product.category.name}
                                        </Badge>
                                    )}
                                    {product.movement_type && (
                                        <Badge variant="outline" className="text-sm">
                                            Movimiento: {product.movement_type}
                                        </Badge>
                                    )}
                                </div>

                                <div className="text-4xl font-bold text-green-600 mb-4">
                                    {formatPrice(product.price)}
                                </div>

                                {/* Estado del stock */}
                                <div className={`inline-flex items-center px-3 py-2 rounded-full text-sm font-medium ${stockStatus.color} ${stockStatus.bgColor}`}>
                                    <Package className="w-4 h-4 mr-2" />
                                    {stockStatus.text}
                                </div>
                            </div>

                            <Separator />

                            {/* Descripción */}
                            {product.description && (
                                <div>
                                    <h3 className="text-lg font-semibold mb-3">Descripción</h3>
                                    <p className="text-gray-700 leading-relaxed">
                                        {product.description}
                                    </p>
                                </div>
                            )}

                            <Separator />

                            {/* Información adicional */}
                            <div className="space-y-3">
                                <h3 className="text-lg font-semibold">Información del producto</h3>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                    {product.sku && (
                                        <div>
                                            <span className="font-medium text-gray-600">SKU:</span>
                                            <span className="ml-2">{product.sku}</span>
                                        </div>
                                    )}
                                    {product.brand && (
                                        <div>
                                            <span className="font-medium text-gray-600">Marca:</span>
                                            <span className="ml-2">{product.brand}</span>
                                        </div>
                                    )}
                                    {product.movement_type && (
                                        <div>
                                            <span className="font-medium text-gray-600">Tipo de movimiento:</span>
                                            <span className="ml-2">{product.movement_type}</span>
                                        </div>
                                    )}
                                    {product.category && (
                                        <div>
                                            <span className="font-medium text-gray-600">Categoría:</span>
                                            <span className="ml-2">{product.category.name}</span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            <Separator />

                            {/* Botón de compra */}
                            <div className="space-y-4">
                                <AddToCartButton
                                    product={product}
                                    className="w-full"
                                />

                                {product.stock_quantity > 0 && (
                                    <p className="text-sm text-gray-600 text-center">
                                        Disponible para entrega inmediata
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Información adicional en cards */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <Card>
                            <CardHeader className="text-center">
                                <Shield className="w-8 h-8 mx-auto text-blue-600 mb-2" />
                                <CardTitle className="text-lg">Garantía</CardTitle>
                            </CardHeader>
                            <CardContent className="text-center">
                                <p className="text-gray-600">
                                    Garantía de fábrica incluida en todos nuestros productos
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="text-center">
                                <Truck className="w-8 h-8 mx-auto text-green-600 mb-2" />
                                <CardTitle className="text-lg">Envío</CardTitle>
                            </CardHeader>
                            <CardContent className="text-center">
                                <p className="text-gray-600">
                                    Envío seguro a toda la República Mexicana
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="text-center">
                                <Star className="w-8 h-8 mx-auto text-yellow-600 mb-2" />
                                <CardTitle className="text-lg">Calidad</CardTitle>
                            </CardHeader>
                            <CardContent className="text-center">
                                <p className="text-gray-600">
                                    Productos de alta calidad seleccionados cuidadosamente
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Productos relacionados placeholder */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Productos relacionados</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-center py-8 text-gray-500">
                                <p>Próximamente: productos relacionados y recomendaciones</p>
                                <Link href="/productos" className="mt-4 inline-block">
                                    <Button variant="outline">
                                        Ver más productos
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
