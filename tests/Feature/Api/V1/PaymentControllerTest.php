<?php

namespace Tests\Feature\Api\V1;

use App\Models\Order;
use App\Models\User;
use App\Models\Address;
use App\Models\OrderItem;
use App\Services\PayPalPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure PayPal for testing
        Config::set('services.paypal', [
            'mode' => 'sandbox',
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret'
        ]);

        $this->createTestOrder();
    }

    #[Test]
    public function can_create_paypal_order_successfully()
    {
        // Mock successful PayPal API responses
        Http::fake([
            'https://api.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 32400
            ], 200),
            'https://api.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'PAYPAL_ORDER_123',
                'status' => 'CREATED',
                'links' => [
                    [
                        'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL_ORDER_123',
                        'rel' => 'approve',
                        'method' => 'GET'
                    ]
                ]
            ], 201)
        ]);

        $response = $this->postJson('/api/v1/payments/paypal/create-order', [
            'order_id' => $this->order->id
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'PayPal order created successfully',
                'data' => [
                    'paypal_order_id' => 'PAYPAL_ORDER_123',
                    'order_number' => $this->order->order_number
                ]
            ]);

        $this->assertStringContainsString('checkoutnow?token=PAYPAL_ORDER_123', $response->json('data.approval_url'));
    }

    #[Test]
    public function create_paypal_order_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/payments/paypal/create-order', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'order_id' => ['The order id field is required.']
                ]
            ]);
    }

    #[Test]
    public function create_paypal_order_validates_existing_order()
    {
        $response = $this->postJson('/api/v1/payments/paypal/create-order', [
            'order_id' => 99999
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'order_id' => ['The selected order id is invalid.']
                ]
            ]);
    }

    #[Test]
    public function create_paypal_order_validates_order_payment_status()
    {
        // Update order to paid status
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
    public function can_capture_paypal_order_successfully()
    {
        // Mock successful PayPal API responses
        Http::fake([
            'https://api.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 32400
            ], 200),
            'https://api.sandbox.paypal.com/v2/checkout/orders/PAYPAL_ORDER_123/capture' => Http::response([
                'id' => 'PAYPAL_ORDER_123',
                'status' => 'COMPLETED',
                'purchase_units' => [
                    [
                        'payments' => [
                            'captures' => [
                                [
                                    'id' => 'CAPTURE_123',
                                    'status' => 'COMPLETED',
                                    'amount' => [
                                        'currency_code' => 'MXN',
                                        'value' => '1500.00'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $response = $this->postJson('/api/v1/payments/paypal/capture-order', [
            'order_id' => $this->order->id,
            'paypal_order_id' => 'PAYPAL_ORDER_123'
        ]);

                 $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'message' => 'Payment captured successfully',
                 'data' => [
                     'capture_id' => 'CAPTURE_123',
                     'status' => 'COMPLETED',
                     'order_number' => $this->order->order_number,
                     'payment_status' => 'pagado'
                 ]
             ]);
    }

    #[Test]
    public function capture_paypal_order_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/payments/paypal/capture-order', [
            'order_id' => $this->order->id
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'paypal_order_id' => ['The paypal order id field is required.']
                ]
            ]);
    }

    #[Test]
    public function can_simulate_successful_payment()
    {
        $response = $this->postJson('/api/v1/payments/paypal/simulate-success', [
            'order_id' => $this->order->id
        ]);

                 $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'message' => 'Payment simulated successfully',
                 'data' => [
                     'simulated' => true,
                     'order_number' => $this->order->order_number,
                     'payment_status' => 'pagado'
                 ]
             ]);

         // Verify order was updated
         $this->order->refresh();
         $this->assertEquals(Order::PAYMENT_STATUS_PAID, $this->order->payment_status);
         $this->assertEquals('paypal', $this->order->payment_gateway);
    }

    #[Test]
    public function can_simulate_failed_payment()
    {
        $response = $this->postJson('/api/v1/payments/paypal/simulate-failure', [
            'order_id' => $this->order->id
        ]);

                 $response->assertStatus(200)
             ->assertJson([
                 'success' => false,
                 'message' => 'Payment simulation failed as expected',
                 'data' => [
                     'simulated' => true,
                     'order_number' => $this->order->order_number,
                     'payment_status' => 'fallido',
                     'error' => 'Payment declined - simulated failure'
                 ]
             ]);

         // Verify order was updated
         $this->order->refresh();
         $this->assertEquals(Order::PAYMENT_STATUS_FAILED, $this->order->payment_status);
         $this->assertEquals('paypal', $this->order->payment_gateway);
    }

    #[Test]
    public function can_verify_paypal_configuration()
    {
        // Mock successful access token request
        Http::fake([
            'https://api.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'test_access_token_verification',
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
        $this->assertGreaterThan(0, $response->json('config.access_token_length'));
    }

    #[Test]
    public function verify_paypal_configuration_handles_auth_failure()
    {
        // Mock failed access token request
        Http::fake([
            'https://api.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'error' => 'invalid_client',
                'error_description' => 'Client Authentication failed'
            ], 401)
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
                    'access_token_test' => 'failed'
                ]
            ]);

        $this->assertArrayHasKey('access_token_error', $response->json('config'));
    }

    #[Test]
    public function handles_paypal_api_errors_gracefully()
    {
        // Mock PayPal API error
        Http::fake([
            'https://api.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 32400
            ], 200),
            'https://api.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'error' => 'INVALID_REQUEST',
                'error_description' => 'Request is not well-formed, syntactically incorrect, or violates schema.'
            ], 400)
        ]);

        $response = $this->postJson('/api/v1/payments/paypal/create-order', [
            'order_id' => $this->order->id
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Error creating PayPal order'
            ]);

        $this->assertArrayHasKey('details', $response->json());
    }

    private function createTestOrder(): void
    {
        // Create user
        $user = User::factory()->create();

        // Create addresses
        $shippingAddress = Address::factory()->create([
            'user_id' => $user->id,
            'type' => Address::TYPE_SHIPPING,
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'address_line_1' => 'Av. Reforma 123',
            'city' => 'Ciudad de México',
            'state' => 'CDMX',
            'postal_code' => '06600',
            'country' => 'MX'
        ]);

        $billingAddress = Address::factory()->create([
            'user_id' => $user->id,
            'type' => Address::TYPE_BILLING
        ]);

        // Create order
        $this->order = Order::factory()->create([
            'user_id' => $user->id,
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'shipping_address_id' => $shippingAddress->id,
            'billing_address_id' => $billingAddress->id,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'subtotal_amount' => 1400.00,
            'shipping_cost' => 100.00,
            'total_amount' => 1500.00
        ]);

        // Create order items
        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_name' => 'Reloj Deportivo',
            'quantity' => 1,
            'price_per_unit' => 1400.00
        ]);
    }
}
