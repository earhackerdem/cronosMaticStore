<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_active_public_products_paginated(): void
    {
        // Clear any existing products first
        Product::query()->delete();

        $activeProducts = Product::factory()->count(20)->create(['is_active' => true]);
        Product::factory()->count(5)->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/products?per_page=10'); // Paginación explícita para el test

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.current_page', 1);
    }

    public function test_can_get_a_single_active_public_product_by_slug(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->getJson("/api/v1/products/{$product->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.slug', $product->slug);
    }

    public function test_cannot_get_inactive_public_product_by_slug(): void
    {
        $product = Product::factory()->create(['is_active' => false]);

        $response = $this->getJson("/api/v1/products/{$product->slug}");

        $response->assertStatus(422)
                 ->assertJsonValidationErrorFor('slug');
    }

    public function test_can_filter_products_by_active_category_slug(): void
    {
        $category1 = Category::factory()->create(['is_active' => true, 'name' => 'Electronics', 'slug' => 'electronics']);
        $category2 = Category::factory()->create(['is_active' => true, 'name' => 'Books', 'slug' => 'books']);
        Category::factory()->create(['is_active' => false, 'name' => 'Inactive Category', 'slug' => 'inactive-cat']); // Categoría inactiva

        Product::factory()->count(3)->create(['category_id' => $category1->id, 'is_active' => true]);
        Product::factory()->count(2)->create(['category_id' => $category2->id, 'is_active' => true]);
        Product::factory()->create(['category_id' => $category1->id, 'is_active' => false]); // Producto inactivo en categoría activa

        $response = $this->getJson("/api/v1/products?category={$category1->slug}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        foreach ($response->json('data') as $product) {
            $this->assertEquals($category1->id, $product['category']['id']);
        }
    }

    public function test_filter_products_by_inactive_category_slug_returns_validation_error(): void
    {
        $activeCategory = Category::factory()->create(['is_active' => true]);
        $inactiveCategory = Category::factory()->create(['is_active' => false]);

        // Productos en categoría activa (no deberían aparecer si filtramos por la inactiva)
        Product::factory()->count(2)->create(['category_id' => $activeCategory->id, 'is_active' => true]);
        // Productos en categoría inactiva (la categoría en sí es el problema del filtro)
        Product::factory()->count(3)->create(['category_id' => $inactiveCategory->id, 'is_active' => true]);

        $response = $this->getJson("/api/v1/products?category={$inactiveCategory->slug}");

        $response->assertStatus(422)
                 ->assertJsonValidationErrorFor('category');
    }

    public function test_can_search_active_products_by_name_description_or_sku(): void
    {
        Product::factory()->create(['name' => 'Amazing Laptop', 'description' => 'Powerful gaming laptop', 'sku' => 'LPTP123', 'is_active' => true]);
        Product::factory()->create(['name' => 'Cool Gadget', 'description' => 'Very useful gadget', 'sku' => 'GDGT456', 'is_active' => true]);
        Product::factory()->create(['name' => 'Office Chair', 'description' => 'Ergonomic chair', 'sku' => 'CHR789', 'is_active' => true]);
        Product::factory()->create(['name' => 'Inactive Product', 'description' => 'This is an amazing inactive item', 'sku' => 'INC000', 'is_active' => false]);

        $responseName = $this->getJson('/api/v1/products?search=Amazing');
        $responseName->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Amazing Laptop');

        $responseDesc = $this->getJson('/api/v1/products?search=useful gadget');
        $responseDesc->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Cool Gadget');

        $responseSku = $this->getJson('/api/v1/products?search=CHR789');
        $responseSku->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonPath('data.0.sku', 'CHR789');

        $responseInactive = $this->getJson('/api/v1/products?search=inactive item');
        $responseInactive->assertStatus(200)->assertJsonCount(0, 'data');
    }

    public function test_can_sort_active_products(): void
    {
        // Clear any existing products first
        Product::query()->delete();

        Product::factory()->create(['name' => 'Product C', 'price' => 10.00, 'is_active' => true, 'created_at' => now()->subDays(2)]);
        Product::factory()->create(['name' => 'Product A', 'price' => 30.00, 'is_active' => true, 'created_at' => now()->subDays(1)]);
        Product::factory()->create(['name' => 'Product B', 'price' => 20.00, 'is_active' => true, 'created_at' => now()]);
        Product::factory()->create(['name' => 'Product D (Inactive)', 'price' => 5.00, 'is_active' => false]);

        // Sort by name asc
        $response = $this->getJson('/api/v1/products?sortBy=name&sortDirection=asc');
        $response->assertStatus(200)->assertJsonPath('data.0.name', 'Product A');

        // Sort by name desc
        $response = $this->getJson('/api/v1/products?sortBy=name&sortDirection=desc');
        $response->assertStatus(200)->assertJsonPath('data.0.name', 'Product C');

        // Sort by price asc
        $response = $this->getJson('/api/v1/products?sortBy=price&sortDirection=asc');
        $response->assertStatus(200)->assertJsonPath('data.0.name', 'Product C'); // Price 10

        // Sort by price desc
        $response = $this->getJson('/api/v1/products?sortBy=price&sortDirection=desc');
        $response->assertStatus(200)->assertJsonPath('data.0.name', 'Product A'); // Price 30

        // Sort by created_at desc (default)
        $response = $this->getJson('/api/v1/products');
        $response->assertStatus(200)->assertJsonPath('data.0.name', 'Product B');
    }
}
