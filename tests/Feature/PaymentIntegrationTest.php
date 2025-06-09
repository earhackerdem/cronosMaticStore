<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\Address;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\OrderService;
use App\Services\PayPalPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PaymentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Order $order;
    private PayPalPaymentService $paypalService;
    private OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure PayPal for testing
        Config::set('services.paypal', [
            'mode' => 'sandbox',
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret'
        ]);

        $this->paypalService = app(PayPalPaymentService::class);
        $this->orderService = app(OrderService::class);

        $this->createTestData();
    }

    #[Test]
    public function complete_payment_flow_with_real_paypal_order_creation()
    {
        // Mock PayPal API responses for complete flow
        Http::fake([
            'https://api.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'test_access_token_integration',
                'token_type' => 'Bearer',
                'expires_in' => 32400
            ], 200),
            'https://api.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'INTEGRATION_ORDER_123',
                'status' => 'CREATED',
                'links' => [
                    [
                        'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=INTEGRATION_ORDER_123',
                        'rel' => 'approve',
                        'method' => 'GET'
                    ]
                ]
            ], 201),
            'https://api.sandbox.paypal.com/v2/checkout/orders/INTEGRATION_ORDER_123/capture' => Http::response([
                'id' => 'INTEGRATION_ORDER_123',
                'status' => 'COMPLETED',
                'purchase_units' => [
                    [
                        'payments' => [
                            'captures' => [
                                [
                                    'id' => 'INTEGRATION_CAPTURE_123',
                                    'status' => 'COMPLETED',
                                    'amount' => [
                                        'currency_code' => 'MXN',
                                        'value' => '2500.00'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Step 1: Create PayPal order
        $createResponse = $this->postJson('/api/v1/payments/paypal/create-order', [
            'order_id' => $this->order->id
        ]);

        $createResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'paypal_order_id' => 'INTEGRATION_ORDER_123'
                ]
            ]);

        // Step 2: Simulate user completing payment on PayPal
        // (In real scenario, user would be redirected to PayPal, approve payment, and return)

        // Step 3: Capture the payment
        $captureResponse = $this->postJson('/api/v1/payments/paypal/capture-order', [
            'order_id' => $this->order->id,
            'paypal_order_id' => 'INTEGRATION_ORDER_123'
        ]);

                 $captureResponse->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'data' => [
                     'capture_id' => 'INTEGRATION_CAPTURE_123',
                     'status' => 'COMPLETED',
                     'payment_status' => 'pagado'
                 ]
             ]);

                 // Verify order was updated correctly
         $this->order->refresh();
         $this->assertEquals(Order::PAYMENT_STATUS_PAID, $this->order->payment_status);
         $this->assertEquals('paypal', $this->order->payment_gateway);
         $this->assertNotNull($this->order->payment_id);
    }

    #[Test]
    public function complete_simulation_flow_for_successful_payment()
    {
        // Verify initial order state
        $this->assertEquals(Order::PAYMENT_STATUS_PENDING, $this->order->payment_status);
        $this->assertEquals(Order::STATUS_PENDING_PAYMENT, $this->order->status);

        // Simulate successful payment
        $response = $this->postJson('/api/v1/payments/paypal/simulate-success', [
            'order_id' => $this->order->id
        ]);

                 $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'message' => 'Payment simulated successfully',
                 'data' => [
                     'simulated' => true,
                     'payment_status' => 'pagado'
                 ]
             ]);

        // Verify order state transitions
        $this->order->refresh();
        $this->assertEquals(Order::PAYMENT_STATUS_PAID, $this->order->payment_status);
        $this->assertEquals('paypal', $this->order->payment_gateway);
        $this->assertNotNull($this->order->payment_id);
        $this->assertStringStartsWith('SIMULATED_', $this->order->payment_id);
    }

    #[Test]
    public function complete_simulation_flow_for_failed_payment()
    {
        // Verify initial order state
        $this->assertEquals(Order::PAYMENT_STATUS_PENDING, $this->order->payment_status);

        // Simulate failed payment
        $response = $this->postJson('/api/v1/payments/paypal/simulate-failure', [
            'order_id' => $this->order->id
        ]);

                 $response->assertStatus(200)
             ->assertJson([
                 'success' => false,
                 'message' => 'Payment simulation failed as expected',
                 'data' => [
                     'simulated' => true,
                     'payment_status' => 'fallido'
                 ]
             ]);

        // Verify order state transitions
        $this->order->refresh();
        $this->assertEquals(Order::PAYMENT_STATUS_FAILED, $this->order->payment_status);
        $this->assertEquals('paypal', $this->order->payment_gateway);
        $this->assertNotNull($this->order->payment_id);
        $this->assertStringStartsWith('FAILED_', $this->order->payment_id);
    }

    #[Test]
    public function payment_flow_from_cart_to_completion()
    {
        // Create a cart with items
        $product = Product::factory()->create([
            'price' => 1200.00,
            'stock_quantity' => 10,
            'is_active' => true
        ]);

        $cart = Cart::factory()->create(['user_id' => $this->user->id]);
                 CartItem::factory()->create([
             'cart_id' => $cart->id,
             'product_id' => $product->id,
             'quantity' => 2
         ]);

        // Create order from cart
        $shippingAddress = Address::factory()->create([
            'user_id' => $this->user->id,
            'type' => Address::TYPE_SHIPPING
        ]);
        $billingAddress = Address::factory()->create([
            'user_id' => $this->user->id,
            'type' => Address::TYPE_BILLING
        ]);

        $orderData = [
            'shipping_cost' => 150.00,
            'notes' => 'Test order for payment integration'
        ];

        $newOrder = $this->orderService->createOrderFromCart(
            $cart,
            $shippingAddress->id,
            $billingAddress->id,
            null,
            $orderData
        );

        // Simulate successful payment for the new order
        $response = $this->postJson('/api/v1/payments/paypal/simulate-success', [
            'order_id' => $newOrder->id
        ]);

        $response->assertStatus(200);

        // Verify complete flow
        $newOrder->refresh();
        $this->assertEquals(Order::PAYMENT_STATUS_PAID, $newOrder->payment_status);
        $this->assertEquals('paypal', $newOrder->payment_gateway);

        // Verify cart was not cleared automatically (this would be done by frontend)
        $this->assertTrue($cart->exists());
    }

    #[Test]
    public function payment_configuration_verification_works()
    {
        // Mock successful access token for verification
        Http::fake([
            'https://api.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'verification_token_test',
                'token_type' => 'Bearer',
                'expires_in' => 32400
            ], 200)
        ]);

        $response = $this->getJson('/api/v1/payments/paypal/verify-config');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'PayPal configuration verified',
                'config' => [
                    'mode' => 'sandbox',
                    'client_id_configured' => true,
                    'client_secret_configured' => true,
                    'access_token_test' => 'success'
                ]
            ]);

        $this->assertIsInt($response->json('config.access_token_length'));
    }

    #[Test]
    public function payment_return_routes_exist()
    {
        // Verify routes exist in the route list
        $routes = app('router')->getRoutes();
        $routeNames = [];

        foreach ($routes as $route) {
            if ($route->getName()) {
                $routeNames[] = $route->getName();
            }
        }

        $this->assertContains('orders.payment.success', $routeNames);
        $this->assertContains('orders.payment.cancel', $routeNames);
    }

    #[Test]
    public function payment_validation_prevents_invalid_operations()
    {
        // Try to process payment for already paid order
        $this->order->update(['payment_status' => Order::PAYMENT_STATUS_PAID]);

        $response = $this->postJson('/api/v1/payments/paypal/create-order', [
            'order_id' => $this->order->id
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Order is not in a valid state for payment processing'
            ]);
    }

    #[Test]
    public function payment_error_handling_works_properly()
    {
        // Mock PayPal API error
        Http::fake([
            'https://api.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'test_token',
                'token_type' => 'Bearer',
                'expires_in' => 32400
            ], 200),
            'https://api.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'error' => 'SERVER_ERROR',
                'error_description' => 'Internal server error'
            ], 500)
        ]);

        $response = $this->postJson('/api/v1/payments/paypal/create-order', [
            'order_id' => $this->order->id
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Error creating PayPal order'
            ]);
    }

    private function createTestData(): void
    {
        // Create user
        $this->user = User::factory()->create();

        // Create addresses
        $shippingAddress = Address::factory()->create([
            'user_id' => $this->user->id,
            'type' => Address::TYPE_SHIPPING,
            'first_name' => 'María',
            'last_name' => 'González',
            'address_line_1' => 'Av. Insurgentes 456',
            'city' => 'Ciudad de México',
            'state' => 'CDMX',
            'postal_code' => '03100',
            'country' => 'MX'
        ]);

        $billingAddress = Address::factory()->create([
            'user_id' => $this->user->id,
            'type' => Address::TYPE_BILLING
        ]);

        // Create order
        $this->order = Order::factory()->create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-INTEGRATION-' . strtoupper(uniqid()),
            'shipping_address_id' => $shippingAddress->id,
            'billing_address_id' => $billingAddress->id,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'subtotal_amount' => 2300.00,
            'shipping_cost' => 200.00,
            'total_amount' => 2500.00
        ]);

        // Create order items
        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_name' => 'Reloj Inteligente Premium',
            'quantity' => 1,
            'price_per_unit' => 2300.00
        ]);
    }
}
