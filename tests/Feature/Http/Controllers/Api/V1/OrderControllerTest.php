<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable payment simulation for testing
        Config::set('services.paypal.simulate_payments', true);
        Config::set('app.env', 'testing');
    }

    #[Test]
    public function authenticated_user_can_create_order_successfully(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100.00,
            'stock_quantity' => 10,
            'is_active' => true
        ]);

        $address = Address::factory()->create([
            'user_id' => $user->id,
            'type' => 'shipping'
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 200.00,
            'total_items' => 2
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'total_price' => 200.00
        ]);

        $requestData = [
            'shipping_address_id' => $address->id,
            'payment_method' => 'paypal',
            'shipping_cost' => 15.00,
            'shipping_method_name' => 'Standard Shipping',
            'notes' => 'Test order notes'
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', $requestData);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'order' => [
                    'id',
                    'order_number',
                    'status',
                    'payment_status',
                    'subtotal_amount',
                    'shipping_cost',
                    'total_amount',
                    'created_at',
                    'updated_at'
                ],
                'payment' => [
                    'status',
                    'payment_id',
                    'gateway'
                ]
            ]
        ]);

        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Pedido creado exitosamente.', $responseData['message']);

        // Verify order was created in database
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'shipping_address_id' => $address->id,
            'status' => Order::STATUS_PROCESSING,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'subtotal_amount' => 200.00,
            'shipping_cost' => 15.00,
            'total_amount' => 215.00,
            'payment_gateway' => 'paypal'
        ]);

        // Verify order items were created
        $order = Order::where('user_id', $user->id)->first();
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price_per_unit' => 100.00,
        ]);

        // Verify stock was reduced
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 8 // 10 - 2
        ]);

        // Verify cart was cleared
        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id
        ]);
    }

    // TODO: Implement guest user test after resolving session handling
    // #[Test]
    // public function guest_user_can_create_order_with_email(): void
    // {
    //     $this->markTestSkipped('Guest user functionality needs session handling refinement');
    // }

    #[Test]
    public function it_returns_validation_error_when_shipping_address_is_missing(): void
    {
        // Arrange
        $user = User::factory()->create();

        $requestData = [
            'payment_method' => 'paypal'
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', $requestData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shipping_address_id']);
    }

    #[Test]
    public function it_returns_validation_error_when_guest_email_is_missing_for_guest(): void
    {
        // Arrange
        $tempUser = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $tempUser->id]);

        $requestData = [
            'shipping_address_id' => $address->id,
            'payment_method' => 'paypal'
        ];

        // Act
        $response = $this->postJson('/api/v1/orders', $requestData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['guest_email']);
    }

    #[Test]
    public function it_returns_error_when_cart_is_empty(): void
    {
        // Arrange
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        // Create empty cart
        Cart::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 0,
            'total_items' => 0
        ]);

        $requestData = [
            'shipping_address_id' => $address->id,
            'payment_method' => 'paypal'
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', $requestData);

        // Assert
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'No se puede crear un pedido con un carrito vacío.'
        ]);
    }

    #[Test]
    public function it_returns_error_when_product_stock_is_insufficient(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100.00,
            'stock_quantity' => 1, // Only 1 in stock
            'is_active' => true
        ]);

        $address = Address::factory()->create(['user_id' => $user->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 200.00,
            'total_items' => 2
        ]);

        // Try to order 2 items when only 1 is available
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'total_price' => 200.00
        ]);

        $requestData = [
            'shipping_address_id' => $address->id,
            'payment_method' => 'paypal'
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', $requestData);

        // Assert
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Algunos productos no tienen stock suficiente.'
        ]);
    }

    #[Test]
    public function it_returns_error_when_payment_method_is_invalid(): void
    {
        // Arrange
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        $requestData = [
            'shipping_address_id' => $address->id,
            'payment_method' => 'invalid_method'
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', $requestData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_method']);
    }

    #[Test]
    public function it_returns_error_when_shipping_address_does_not_exist(): void
    {
        // Arrange
        $user = User::factory()->create();

        $requestData = [
            'shipping_address_id' => 99999, // Non-existent address
            'payment_method' => 'paypal'
        ];

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', $requestData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shipping_address_id']);
    }
}
