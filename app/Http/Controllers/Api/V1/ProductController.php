<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Http\Requests\Api\V1\ListProductsRequest;
use App\Http\Requests\Api\V1\ShowProductRequest;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ListProductsRequest $request)
    {
        $validated = $request->validated();

        $products = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->when(isset($validated['category']), function ($query) use ($validated) {
                $query->whereHas('category', function ($q) use ($validated) {
                    $q->where('slug', $validated['category'])->where('is_active', true);
                });
            })
            ->when(isset($validated['search']), function ($query) use ($validated) {
                $searchTerm = $validated['search'];
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%")
                      ->orWhere('sku', 'like', "%{$searchTerm}%");
                });
            })
            ->when(isset($validated['sortBy']), function ($query) use ($validated) {
                $sortBy = $validated['sortBy'];
                $sortDirection = $validated['sortDirection'] ?? 'asc';
                $query->orderBy($sortBy, $sortDirection);
            }, function ($query) {
                $query->orderBy('created_at', 'desc');
            })
            ->paginate($validated['per_page'] ?? 15);

        return ProductResource::collection($products);
    }

    /**
     * Display the specified resource.
     */
    public function show(ShowProductRequest $request, string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with('category')
            ->firstOrFail();
        return new ProductResource($product);
    }
}
