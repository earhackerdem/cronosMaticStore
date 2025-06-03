<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $products = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->when($request->query('category'), function ($query, $categorySlug) {
                $query->whereHas('category', function ($q) use ($categorySlug) {
                    $q->where('slug', $categorySlug)->where('is_active', true);
                });
            })
            ->when($request->query('search'), function ($query, $searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%")
                      ->orWhere('sku', 'like', "%{$searchTerm}%");
                });
            })
            ->when($request->query('sortBy') && $request->query('sortDirection'), function ($query) use ($request) {
                $sortBy = $request->query('sortBy');
                $sortDirection = $request->query('sortDirection', 'asc'); // asc por defecto
                // Validar que sortBy sea una columna permitida para evitar inyección SQL si se usa directamente
                $allowedSorts = ['name', 'price', 'created_at']; // Añadir más campos si es necesario
                if (in_array($sortBy, $allowedSorts)) {
                    $query->orderBy($sortBy, $sortDirection);
                }
            }, function ($query) {
                // Orden por defecto si no se especifica sortBy
                $query->orderBy('created_at', 'desc');
            })
            ->paginate($request->query('per_page', 15)); // 15 por página por defecto

        return ProductResource::collection($products);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with('category')
            ->firstOrFail();
        return new ProductResource($product);
    }
}
