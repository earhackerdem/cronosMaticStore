<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display a listing of products for the frontend
     */
    public function index(Request $request)
    {
        // Validar parámetros de entrada
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|string|exists:categories,slug',
            'sortBy' => 'nullable|string|in:name,price,created_at',
            'sortDirection' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:50'
        ]);

        // Construir query de productos
        $productsQuery = Product::query()
            ->with('category')
            ->where('is_active', true);

        // Aplicar filtro de categoría
        if (!empty($validated['category'])) {
            $productsQuery->whereHas('category', function ($query) use ($validated) {
                $query->where('slug', $validated['category'])->where('is_active', true);
            });
        }

        // Aplicar búsqueda
        if (!empty($validated['search'])) {
            $searchTerm = $validated['search'];
            $productsQuery->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%")
                      ->orWhere('brand', 'like', "%{$searchTerm}%");
            });
        }

        // Aplicar ordenamiento
        $sortBy = $validated['sortBy'] ?? 'created_at';
        $sortDirection = $validated['sortDirection'] ?? 'desc';
        $productsQuery->orderBy($sortBy, $sortDirection);

        // Paginar resultados
        $perPage = $validated['per_page'] ?? 12;
        $products = $productsQuery->paginate($perPage);

        // Obtener categorías activas para el filtro
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Preparar filtros actuales para el frontend
        $filters = [
            'search' => $validated['search'] ?? null,
            'category' => $validated['category'] ?? null,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
        ];

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    /**
     * Display the specified product
     */
    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with('category')
            ->firstOrFail();

        return Inertia::render('Products/Show', [
            'product' => $product,
        ]);
    }
}
