<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use App\Http\Requests\Api\V1\ListCategoriesRequest;
use App\Http\Requests\Api\V1\ShowCategoryRequest;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ListCategoriesRequest $request)
    {
        $validated = $request->validated();
        $perPage = $validated['per_page'] ?? 15;

        return CategoryResource::collection(Category::where('is_active', true)->paginate($perPage));
    }

    /**
     * Display the specified resource.
     */
    public function show(ShowCategoryRequest $request, string $slug)
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->firstOrFail();
        // Cargar productos activos asociados a la categoría, paginados.
        $products = $category->products()->where('is_active', true)->paginate(10); // Ajusta el número de productos por página según necesites

        // Clonamos la categoría para no modificar la original al añadir los productos.
        // Esto es una forma de hacerlo; otra sería crear un Resource específico que combine Category y sus Products.
        $categoryData = new CategoryResource($category);

        // Devolvemos la categoría junto con sus productos paginados.
        // Puedes ajustar la estructura de esta respuesta según tus necesidades.
        // Por ejemplo, podrías añadir los productos directamente dentro del CategoryResource
        // o tener un wrapper en la respuesta.
        return response()->json([
            'category' => $categoryData,
            'products' => $products
        ]);
    }
}
