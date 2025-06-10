<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create(['is_admin' => true]);
        $this->regularUser = User::factory()->create(['is_admin' => false]);
        $this->category = Category::factory()->create();
    }

    // Test index
    public function test_admin_can_list_products(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Clear any existing products first
        Product::query()->delete();

        // Create exactly 3 products
        $products = Product::factory(3)->create(['category_id' => $this->category->id]);

        $response = $this->getJson(route('products.index'));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'sku', 'price', 'category_id']
                ],
                'links',
                'meta'
            ]);

        // Verify the products are the ones we created
        foreach ($products as $product) {
            $response->assertJsonFragment(['id' => $product->id]);
        }
    }

    public function test_non_admin_cannot_list_products(): void
    {
        Sanctum::actingAs($this->regularUser);
        $response = $this->getJson(route('products.index'));
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    // Test store
    public function test_admin_can_create_product(): void
    {
        Sanctum::actingAs($this->adminUser);
        $productData = [
            'category_id' => $this->category->id,
            'name' => 'New Awesome Watch',
            'sku' => 'NAW-001',
            'price' => 199.99,
            'stock_quantity' => 10,
            'description' => 'A very awesome watch.',
            'brand' => 'AwesomeBrand',
            'movement_type' => 'Automatic',
            'image_path' => 'products/new_watch.jpg', // Simula ruta de imagen ya subida
            'is_active' => true,
        ];

        $response = $this->postJson(route('products.store'), $productData);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonFragment(['name' => 'New Awesome Watch', 'sku' => 'NAW-001']);

        $this->assertDatabaseHas('products', ['sku' => 'NAW-001', 'image_path' => 'products/new_watch.jpg']);
    }

    public function test_admin_cannot_create_product_with_invalid_data(): void
    {
        Sanctum::actingAs($this->adminUser);
        $productData = [
            'name' => 'No SKU Watch', // Falta SKU, category_id, price, stock_quantity
        ];

        $response = $this->postJson(route('products.store'), $productData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('category_id')
            ->assertJsonValidationErrorFor('sku')
            ->assertJsonValidationErrorFor('price')
            ->assertJsonValidationErrorFor('stock_quantity');
    }

    // Test show
    public function test_admin_can_show_product(): void
    {
        Sanctum::actingAs($this->adminUser);
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->getJson(route('products.show', $product->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['id' => $product->id, 'name' => $product->name]);
    }

    // Test update
    public function test_admin_can_update_product(): void
    {
        Sanctum::actingAs($this->adminUser);
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $updateData = [
            'name' => 'Updated Watch Name',
            'price' => 249.99,
            'image_path' => 'products/updated_image.jpg'
        ];

        $response = $this->putJson(route('products.update', $product->id), $updateData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['name' => 'Updated Watch Name', 'price' => 249.99, 'image_path' => 'products/updated_image.jpg']);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Watch Name']);
    }

    public function test_admin_cannot_update_product_with_invalid_sku_unique(): void
    {
        Sanctum::actingAs($this->adminUser);
        $product1 = Product::factory()->create(['sku' => 'SKU001', 'category_id' => $this->category->id]);
        $product2 = Product::factory()->create(['sku' => 'SKU002', 'category_id' => $this->category->id]);

        $updateData = [
            'sku' => 'SKU001', // Intentando usar el SKU de product1 para product2
        ];

        $response = $this->putJson(route('products.update', $product2->id), $updateData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('sku');
    }

    // Test destroy
    public function test_admin_can_delete_product(): void
    {
        Sanctum::actingAs($this->adminUser);
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->deleteJson(route('products.destroy', $product->id));

        $response->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
