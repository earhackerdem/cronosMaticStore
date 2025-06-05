<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_active_public_categories(): void
    {
        Category::factory()->count(3)->create(['is_active' => true]);
        Category::factory()->count(2)->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_a_single_active_public_category_with_its_active_products(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        Product::factory()->count(5)->create([
            'category_id' => $category->id,
            'is_active' => true
        ]);
        Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'is_active' => false
        ]);

        $response = $this->getJson("/api/v1/categories/{$category->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('category.slug', $category->slug)
            ->assertJsonCount(5, 'products.data');
    }

    public function test_cannot_get_inactive_public_category_by_slug(): void
    {
        $category = Category::factory()->create(['is_active' => false]);

        $response = $this->getJson("/api/v1/categories/{$category->slug}");

        $response->assertStatus(422)
                 ->assertJsonValidationErrorFor('slug');
    }

    public function test_get_single_active_public_category_does_not_show_inactive_products(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        Product::factory()->count(2)->create([
            'category_id' => $category->id,
            'is_active' => true
        ]);
        Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'is_active' => false // Estos no deberían aparecer
        ]);

        $response = $this->getJson("/api/v1/categories/{$category->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('category.slug', $category->slug)
            ->assertJsonCount(2, 'products.data');
    }

    public function test_category_product_list_is_paginated(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        Product::factory()->count(15)->create([
            'category_id' => $category->id,
            'is_active' => true
        ]);

        $response = $this->getJson("/api/v1/categories/{$category->slug}"); // Default pagination is 10 for products in CategoryController

        $response->assertStatus(200)
            ->assertJsonCount(10, 'products.data')
            ->assertJsonPath('products.total', 15)
            ->assertJsonPath('products.current_page', 1);
    }
}
