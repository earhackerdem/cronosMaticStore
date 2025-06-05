import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Search, Filter, Grid, List, SortAsc, SortDesc } from 'lucide-react';
import { ProductsIndexProps, Product } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

export default function ProductsIndex({ products, categories, filters }: ProductsIndexProps) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.category || '');
    const [sortBy, setSortBy] = useState(filters.sortBy || 'created_at');
    const [sortDirection, setSortDirection] = useState(filters.sortDirection || 'desc');
    const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');

    // Función para aplicar filtros
    const applyFilters = () => {
        const params: Record<string, string> = {};

        if (searchTerm) params.search = searchTerm;
        if (selectedCategory) params.category = selectedCategory;
        if (sortBy) params.sortBy = sortBy;
        if (sortDirection) params.sortDirection = sortDirection;

        router.get('/productos', params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Función para limpiar filtros
    const clearFilters = () => {
        setSearchTerm('');
        setSelectedCategory('');
        setSortBy('created_at');
        setSortDirection('desc');

        router.get('/productos', {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Formatear precio en MXN
    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
        }).format(price);
    };

    // Componente de tarjeta de producto
    const ProductCard = ({ product }: { product: Product }) => (
        <Card className="group hover:shadow-lg transition-shadow duration-200">
            <CardHeader className="p-0">
                <div className="aspect-square overflow-hidden rounded-t-lg bg-gray-100">
                    {product.image_url ? (
                        <img
                            src={product.image_url}
                            alt={product.name}
                            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                        />
                    ) : (
                        <div className="w-full h-full flex items-center justify-center text-gray-400">
                            <div className="text-center">
                                <div className="w-16 h-16 mx-auto mb-2 bg-gray-200 rounded-full flex items-center justify-center">
                                    <Search className="w-8 h-8" />
                                </div>
                                <p className="text-sm">Sin imagen</p>
                            </div>
                        </div>
                    )}
                </div>
            </CardHeader>
            <CardContent className="p-4">
                <div className="space-y-2">
                    <h3 className="font-semibold text-lg line-clamp-2 group-hover:text-blue-600 transition-colors">
                        {product.name}
                    </h3>
                    {product.brand && (
                        <p className="text-sm text-gray-600">{product.brand}</p>
                    )}
                    {product.category && (
                        <Badge variant="secondary" className="text-xs">
                            {product.category.name}
                        </Badge>
                    )}
                    {product.movement_type && (
                        <p className="text-sm text-gray-500">
                            Movimiento: {product.movement_type}
                        </p>
                    )}
                    <p className="text-2xl font-bold text-green-600">
                        {formatPrice(product.price)}
                    </p>
                </div>
            </CardContent>
            <CardFooter className="p-4 pt-0">
                <div className="w-full space-y-2">
                    <div className="flex items-center justify-between text-sm">
                        <span className={`font-medium ${product.stock_quantity > 0 ? 'text-green-600' : 'text-red-600'}`}>
                            {product.stock_quantity > 0 ?
                                `En stock (${product.stock_quantity})` :
                                'Agotado'
                            }
                        </span>
                    </div>
                    <Link
                        href={`/productos/${product.slug}`}
                        className="w-full"
                    >
                        <Button className="w-full" variant="outline">
                            Ver detalles
                        </Button>
                    </Link>
                </div>
            </CardFooter>
        </Card>
    );

    // Componente de vista de lista
    const ProductListItem = ({ product }: { product: Product }) => (
        <Card className="group hover:shadow-md transition-shadow duration-200">
            <CardContent className="p-4">
                <div className="flex gap-4">
                    <div className="w-24 h-24 flex-shrink-0 overflow-hidden rounded-lg bg-gray-100">
                        {product.image_url ? (
                            <img
                                src={product.image_url}
                                alt={product.name}
                                className="w-full h-full object-cover"
                            />
                        ) : (
                            <div className="w-full h-full flex items-center justify-center text-gray-400">
                                <Search className="w-6 h-6" />
                            </div>
                        )}
                    </div>
                    <div className="flex-1 space-y-2">
                        <div className="flex items-start justify-between">
                            <div>
                                <h3 className="font-semibold text-lg group-hover:text-blue-600 transition-colors">
                                    {product.name}
                                </h3>
                                {product.brand && (
                                    <p className="text-sm text-gray-600">{product.brand}</p>
                                )}
                            </div>
                            <p className="text-xl font-bold text-green-600">
                                {formatPrice(product.price)}
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            {product.category && (
                                <Badge variant="secondary" className="text-xs">
                                    {product.category.name}
                                </Badge>
                            )}
                            {product.movement_type && (
                                <Badge variant="outline" className="text-xs">
                                    {product.movement_type}
                                </Badge>
                            )}
                        </div>
                        <div className="flex items-center justify-between">
                            <span className={`text-sm font-medium ${product.stock_quantity > 0 ? 'text-green-600' : 'text-red-600'}`}>
                                {product.stock_quantity > 0 ?
                                    `En stock (${product.stock_quantity})` :
                                    'Agotado'
                                }
                            </span>
                            <Link href={`/productos/${product.slug}`}>
                                <Button variant="outline" size="sm">
                                    Ver detalles
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );

    return (
        <>
            <Head title="Productos - CronosMatic" />

            <div className="min-h-screen bg-gray-50">
                <div className="container mx-auto px-4 py-8">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 mb-2">
                            Catálogo de Relojes
                        </h1>
                        <p className="text-gray-600">
                            Descubre nuestra colección de relojes de alta calidad
                        </p>
                    </div>

                    {/* Filtros y búsqueda */}
                    <Card className="mb-8">
                        <CardContent className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                                {/* Búsqueda */}
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Buscar</label>
                                    <div className="relative">
                                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                        <Input
                                            placeholder="Buscar productos..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            className="pl-10"
                                            onKeyPress={(e) => e.key === 'Enter' && applyFilters()}
                                        />
                                    </div>
                                </div>

                                {/* Categoría */}
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Categoría</label>
                                    <Select value={selectedCategory || 'all'} onValueChange={(value) => setSelectedCategory(value === 'all' ? '' : value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Todas las categorías" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todas las categorías</SelectItem>
                                            {categories.map((category) => (
                                                <SelectItem key={category.id} value={category.slug}>
                                                    {category.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Ordenar por */}
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Ordenar por</label>
                                    <Select value={sortBy} onValueChange={setSortBy}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="created_at">Más recientes</SelectItem>
                                            <SelectItem value="name">Nombre</SelectItem>
                                            <SelectItem value="price">Precio</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Dirección de ordenamiento */}
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Dirección</label>
                                    <Select value={sortDirection} onValueChange={(value) => setSortDirection(value as 'asc' | 'desc')}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="asc">
                                                <div className="flex items-center gap-2">
                                                    <SortAsc className="w-4 h-4" />
                                                    Ascendente
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="desc">
                                                <div className="flex items-center gap-2">
                                                    <SortDesc className="w-4 h-4" />
                                                    Descendente
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="flex items-center justify-between">
                                <div className="flex gap-2">
                                    <Button onClick={applyFilters}>
                                        <Filter className="w-4 h-4 mr-2" />
                                        Aplicar filtros
                                    </Button>
                                    <Button variant="outline" onClick={clearFilters}>
                                        Limpiar filtros
                                    </Button>
                                </div>

                                <div className="flex items-center gap-2">
                                    <span className="text-sm text-gray-600">Vista:</span>
                                    <Button
                                        variant={viewMode === 'grid' ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() => setViewMode('grid')}
                                    >
                                        <Grid className="w-4 h-4" />
                                    </Button>
                                    <Button
                                        variant={viewMode === 'list' ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() => setViewMode('list')}
                                    >
                                        <List className="w-4 h-4" />
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Resultados */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <p className="text-gray-600">
                                Mostrando {products.from || 0} - {products.to || 0} de {products.total} productos
                            </p>
                        </div>
                    </div>

                    {/* Lista de productos */}
                    {products.data.length > 0 ? (
                        <>
                            {viewMode === 'grid' ? (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                                    {products.data.map((product) => (
                                        <ProductCard key={product.id} product={product} />
                                    ))}
                                </div>
                            ) : (
                                <div className="space-y-4 mb-8">
                                    {products.data.map((product) => (
                                        <ProductListItem key={product.id} product={product} />
                                    ))}
                                </div>
                            )}

                            {/* Paginación */}
                            {products.last_page > 1 && (
                                <div className="flex items-center justify-center gap-2">
                                    {products.links.map((link, index) => (
                                        <div key={index}>
                                            {link.url ? (
                                                <Link
                                                    href={link.url}
                                                    preserveState
                                                    preserveScroll
                                                >
                                                    <Button
                                                        variant={link.active ? 'default' : 'outline'}
                                                        size="sm"
                                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                                    />
                                                </Link>
                                            ) : (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    disabled
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </>
                    ) : (
                        <Card className="text-center py-12">
                            <CardContent>
                                <div className="text-gray-400 mb-4">
                                    <Search className="w-16 h-16 mx-auto mb-4" />
                                    <h3 className="text-xl font-semibold mb-2">No se encontraron productos</h3>
                                    <p>
                                        {filters.search || filters.category ?
                                            'Intenta ajustar tus filtros de búsqueda' :
                                            'No hay productos disponibles en este momento'
                                        }
                                    </p>
                                </div>
                                {(filters.search || filters.category) && (
                                    <Button onClick={clearFilters}>
                                        Ver todos los productos
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </>
    );
}
