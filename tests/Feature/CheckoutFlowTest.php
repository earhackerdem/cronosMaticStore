<?php

namespace Tests\Feature;

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

class CheckoutFlowTest extends TestCase
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
    public function authenticated_user_can_complete_full_checkout_flow(): void
    {
        // Arrange: Create user with cart and addresses
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 1500.00,
            'stock_quantity' => 5,
            'is_active' => true
        ]);

        $shippingAddress = Address::factory()->create([
            'user_id' => $user->id,
            'type' => 'shipping',
            'is_default' => true
        ]);

        $billingAddress = Address::factory()->create([
            'user_id' => $user->id,
            'type' => 'billing',
            'is_default' => true
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 3000.00,
            'total_items' => 2
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 1500.00,
            'total_price' => 3000.00
        ]);

        // Act: Complete checkout process
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address_id' => $shippingAddress->id,
                'billing_address_id' => $billingAddress->id,
                'payment_method' => 'paypal',
                'shipping_cost' => 0.00,
                'shipping_method_name' => 'Envío Estándar',
                'notes' => 'Test checkout flow'
            ]);

        // Assert: Order created successfully
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
                    'total_amount'
                ],
                'payment' => [
                    'status',
                    'gateway'
                ]
            ]
        ]);

        $responseData = $response->json();
        $this->assertTrue($responseData['success']);

        // Verify order was created with correct data
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'shipping_address_id' => $shippingAddress->id,
            'billing_address_id' => $billingAddress->id,
            'status' => Order::STATUS_PROCESSING,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'subtotal_amount' => 3000.00,
            'shipping_cost' => 0.00,
            'total_amount' => 3000.00,
            'payment_gateway' => 'paypal',
            'shipping_method_name' => 'Envío Estándar',
            'notes' => 'Test checkout flow'
        ]);

        // Verify order items were created
        $order = Order::where('user_id', $user->id)->first();
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price_per_unit' => 1500.00,
            'total_price' => 3000.00
        ]);

        // Verify product stock was reduced
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 3 // 5 - 2
        ]);

        // Verify cart was cleared
        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id
        ]);
    }

    #[Test]
    public function guest_user_can_complete_checkout_with_email(): void
    {
        // Arrange: Create product and cart for guest
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 800.00,
            'stock_quantity' => 10,
            'is_active' => true
        ]);

        // Create a user to create addresses (addresses require user_id)
        $user = User::factory()->create();
        $shippingAddress = Address::factory()->create([
            'user_id' => $user->id,
            'type' => 'shipping'
        ]);

        $cart = Cart::factory()->create([
            'user_id' => null,
            'session_id' => 'guest-session-123',
            'total_amount' => 1600.00,
            'total_items' => 2
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 800.00,
            'total_price' => 1600.00
        ]);

        // Act: Complete guest checkout
        $response = $this->withHeaders([
            'X-Session-ID' => 'guest-session-123'
        ])->postJson('/api/v1/orders', [
            'shipping_address_id' => $shippingAddress->id,
            'payment_method' => 'paypal',
            'guest_email' => 'guest@example.com',
            'shipping_cost' => 50.00,
            'shipping_method_name' => 'Envío Express'
        ]);

        // Assert: Order created successfully
        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Pedido creado exitosamente.'
        ]);

        // Verify guest order was created
        $this->assertDatabaseHas('orders', [
            'user_id' => null,
            'guest_email' => 'guest@example.com',
            'shipping_address_id' => $shippingAddress->id,
            'billing_address_id' => $shippingAddress->id, // Same as shipping for guest
            'status' => Order::STATUS_PROCESSING,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'subtotal_amount' => 1600.00,
            'shipping_cost' => 50.00,
            'total_amount' => 1650.00,
            'shipping_method_name' => 'Envío Express'
        ]);
    }

    #[Test]
    public function checkout_fails_when_cart_is_empty(): void
    {
        // Arrange: User with empty cart
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        Cart::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 0,
            'total_items' => 0
        ]);

        // Act: Attempt checkout with empty cart
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address_id' => $address->id,
                'payment_method' => 'paypal'
            ]);

        // Assert: Error response
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'No se puede crear un pedido con un carrito vacío.'
        ]);
    }

    #[Test]
    public function checkout_fails_when_insufficient_stock(): void
    {
        // Arrange: Product with limited stock
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 500.00,
            'stock_quantity' => 1, // Only 1 in stock
            'is_active' => true
        ]);

        $address = Address::factory()->create(['user_id' => $user->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 1000.00,
            'total_items' => 2
        ]);

        // Try to order 2 items when only 1 is available
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 500.00,
            'total_price' => 1000.00
        ]);

        // Act: Attempt checkout
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address_id' => $address->id,
                'payment_method' => 'paypal'
            ]);

        // Assert: Stock error
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Algunos productos no tienen stock suficiente.'
        ]);
    }

    #[Test]
    public function checkout_validates_required_fields(): void
    {
        $user = User::factory()->create();

        // Test missing shipping address
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'payment_method' => 'paypal'
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shipping_address_id']);

        // Test invalid payment method
        $address = Address::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address_id' => $address->id,
                'payment_method' => 'invalid_method'
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_method']);
    }

    #[Test]
    public function guest_checkout_requires_email(): void
    {
        // Arrange: Guest cart
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        // Act: Guest checkout without email
        $response = $this->postJson('/api/v1/orders', [
            'shipping_address_id' => $address->id,
            'payment_method' => 'paypal'
        ]);

        // Assert: Validation error for missing email
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['guest_email']);
    }

    #[Test]
    public function checkout_handles_same_billing_address(): void
    {
        // Arrange: User with single address
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 200.00,
            'stock_quantity' => 5,
            'is_active' => true
        ]);

        $address = Address::factory()->create([
            'user_id' => $user->id,
            'type' => 'shipping'
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 200.00,
            'total_items' => 1
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 200.00,
            'total_price' => 200.00
        ]);

        // Act: Checkout without specifying billing address (should use shipping address)
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address_id' => $address->id,
                'payment_method' => 'paypal'
            ]);

        // Assert: Order created with same address for billing
        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id // Same as shipping
        ]);
    }

    #[Test]
    public function checkout_calculates_total_correctly_with_shipping(): void
    {
        // Arrange: Cart with shipping cost
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 1000.00,
            'stock_quantity' => 3,
            'is_active' => true
        ]);

        $address = Address::factory()->create(['user_id' => $user->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 2000.00,
            'total_items' => 2
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 1000.00,
            'total_price' => 2000.00
        ]);

        // Act: Checkout with shipping cost
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address_id' => $address->id,
                'payment_method' => 'paypal',
                'shipping_cost' => 150.00,
                'shipping_method_name' => 'Envío Express'
            ]);

        // Assert: Total includes shipping
        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'subtotal_amount' => 2000.00,
            'shipping_cost' => 150.00,
            'total_amount' => 2150.00 // 2000 + 150
        ]);
    }
}
