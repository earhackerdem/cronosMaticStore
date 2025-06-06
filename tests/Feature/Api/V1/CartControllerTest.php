<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;
    private Product $product;
    private Product $inactiveProduct;
    private Product $outOfStockProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();

        $this->product = Product::factory()->create([
            'category_id' => $this->category->id,
            'price' => 10.99,
            'stock_quantity' => 50,
            'is_active' => true,
        ]);

        $this->inactiveProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'price' => 15.99,
            'stock_quantity' => 20,
            'is_active' => false,
        ]);

        $this->outOfStockProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'price' => 20.99,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function obtener_carrito_vacio_para_usuario_nuevo(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/cart');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'user_id',
                        'total_items',
                        'total_amount',
                        'items',
                        'summary',
                        'created_at',
                        'updated_at',
                    ],
                ])
                ->assertJsonPath('data.total_items', 0)
                ->assertJsonPath('data.total_amount', '0.00')
                ->assertJsonPath('data.items', []);
    }

        #[Test]
    public function obtener_carrito_vacio_para_invitado(): void
    {
        $response = $this->withHeaders([
            'X-Session-ID' => 'test-session-123',
        ])->getJson('/api/v1/cart');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'session_id',
                        'total_items',
                        'total_amount',
                        'items',
                        'summary',
                        'expires_at',
                        'created_at',
                        'updated_at',
                    ],
                ])
                ->assertJsonPath('data.total_items', 0)
                ->assertJsonPath('data.total_amount', '0.00')
                ->assertJsonPath('data.items', []);
    }

    #[Test]
    public function anadir_producto_valido_al_carrito(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(201)
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.total_items', 2)
                ->assertJsonPath('data.total_amount', '21.98')
                ->assertJsonCount(1, 'data.items');

        $this->assertDatabaseHas('carts', [
            'user_id' => $this->user->id,
            'total_items' => 2,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => $this->product->price,
        ]);
    }

    #[Test]
    public function anadir_producto_con_stock_insuficiente(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => $this->outOfStockProduct->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(422)
                ->assertJsonPath('success', false)
                ->assertSee('Stock insuficiente');
    }

    #[Test]
    public function anadir_producto_inactivo(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => $this->inactiveProduct->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(422)
                ->assertJsonPath('success', false)
                ->assertJsonPath('message', 'El producto no está disponible');
    }

    #[Test]
    public function anadir_producto_inexistente(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => 99999,
            'quantity' => 1,
        ]);

        $response->assertStatus(422)
                ->assertJsonPath('success', false)
                ->assertJsonStructure(['errors']);
    }

    #[Test]
    public function anadir_producto_con_cantidad_invalida(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => $this->product->id,
            'quantity' => 0,
        ]);

        $response->assertStatus(422)
                ->assertJsonPath('success', false)
                ->assertJsonStructure(['errors']);
    }

    #[Test]
    public function actualizar_cantidad_de_item_existente(): void
    {
        Sanctum::actingAs($this->user);

        $cart = Cart::factory()->create(['user_id' => $this->user->id]);
        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => $this->product->price,
            'total_price' => $this->product->price,
        ]);

        $response = $this->putJson("/api/v1/cart/items/{$cartItem->id}", [
            'quantity' => 3,
        ]);

        $response->assertStatus(200)
                ->assertJsonPath('success', true);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 3,
            'total_price' => $this->product->price * 3,
        ]);
    }

    #[Test]
    public function actualizar_item_inexistente(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/cart/items/99999', [
            'quantity' => 2,
        ]);

        $response->assertStatus(500);
    }

    #[Test]
    public function actualizar_item_de_otro_usuario(): void
    {
        $otherUser = User::factory()->create();
        $otherCart = Cart::factory()->create(['user_id' => $otherUser->id]);
        $otherCartItem = CartItem::factory()->create([
            'cart_id' => $otherCart->id,
            'product_id' => $this->product->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/cart/items/{$otherCartItem->id}", [
            'quantity' => 2,
        ]);

        $response->assertStatus(403)
                ->assertJsonPath('success', false)
                ->assertSee('No tienes permisos');
    }

    #[Test]
    public function eliminar_item_del_carrito(): void
    {
        Sanctum::actingAs($this->user);

        $cart = Cart::factory()->create(['user_id' => $this->user->id]);
        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->deleteJson("/api/v1/cart/items/{$cartItem->id}");

        $response->assertStatus(200)
                ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    #[Test]
    public function eliminar_item_de_otro_usuario(): void
    {
        $otherUser = User::factory()->create();
        $otherCart = Cart::factory()->create(['user_id' => $otherUser->id]);
        $otherCartItem = CartItem::factory()->create([
            'cart_id' => $otherCart->id,
            'product_id' => $this->product->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/v1/cart/items/{$otherCartItem->id}");

        $response->assertStatus(403)
                ->assertJsonPath('success', false)
                ->assertSee('No tienes permisos');
    }

    #[Test]
    public function vaciar_carrito_completamente(): void
    {
        Sanctum::actingAs($this->user);

        $cart = Cart::factory()->create(['user_id' => $this->user->id]);

        // Crear productos diferentes para evitar constraint violation
        $product1 = Product::factory()->create(['category_id' => $this->category->id]);
        $product2 = Product::factory()->create(['category_id' => $this->category->id]);
        $product3 = Product::factory()->create(['category_id' => $this->category->id]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product1->id,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product2->id,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product3->id,
        ]);

        $response = $this->deleteJson('/api/v1/cart');

        $response->assertStatus(200)
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.total_items', 0)
                ->assertJsonPath('data.items', []);

        $this->assertDatabaseCount('cart_items', 0);
    }

    #[Test]
    public function anadir_producto_como_invitado_con_session_id(): void
    {
        $response = $this->withHeaders([
            'X-Session-ID' => 'guest-session-456',
        ])->postJson('/api/v1/cart/items', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(201)
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.total_items', 1);

        $this->assertDatabaseHas('carts', [
            'session_id' => 'guest-session-456',
            'user_id' => null,
        ]);
    }

    #[Test]
    public function incrementar_cantidad_cuando_producto_ya_existe_en_carrito(): void
    {
        Sanctum::actingAs($this->user);

        // Añadir producto inicial
        $this->postJson('/api/v1/cart/items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        // Añadir el mismo producto otra vez
        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(201)
                ->assertJsonPath('data.total_items', 3);

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity' => 3,
        ]);
    }

    #[Test]
    public function validar_datos_requeridos_para_anadir_item(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/cart/items', []);

        $response->assertStatus(422)
                ->assertJsonStructure(['errors'])
                ->assertJsonValidationErrors(['product_id', 'quantity']);
    }

    #[Test]
    public function validar_datos_requeridos_para_actualizar_item(): void
    {
        Sanctum::actingAs($this->user);

        $cart = Cart::factory()->create(['user_id' => $this->user->id]);
        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->putJson("/api/v1/cart/items/{$cartItem->id}", []);

        $response->assertStatus(422)
                ->assertJsonStructure(['errors'])
                ->assertJsonValidationErrors(['quantity']);
    }
}
