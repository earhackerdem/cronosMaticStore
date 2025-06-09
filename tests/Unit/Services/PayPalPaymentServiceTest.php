<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;
use App\Models\User;
use App\Services\PayPalPaymentService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PayPalPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PayPalPaymentService $paypalService;
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

        $this->paypalService = new PayPalPaymentService();

        // Create test data
        $this->createTestOrder();
    }

    #[Test]
    public function can_simulate_successful_payment()
    {
        $result = $this->paypalService->simulateSuccessfulPayment($this->order);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['simulated']);
        $this->assertArrayHasKey('paypal_order_id', $result);
        $this->assertArrayHasKey('capture_id', $result);
        $this->assertStringStartsWith('SIMULATED_', $result['paypal_order_id']);
        $this->assertStringStartsWith('CAPTURE_', $result['capture_id']);

        // Verify order status was updated
        $this->order->refresh();
        $this->assertEquals(Order::PAYMENT_STATUS_PAID, $this->order->payment_status);
        $this->assertEquals('paypal', $this->order->payment_gateway);
    }

    #[Test]
    public function can_simulate_failed_payment()
    {
        $result = $this->paypalService->simulateFailedPayment($this->order);

        $this->assertFalse($result['success']);
        $this->assertTrue($result['simulated']);
        $this->assertArrayHasKey('paypal_order_id', $result);
        $this->assertStringStartsWith('FAILED_', $result['paypal_order_id']);
        $this->assertEquals('Payment declined - simulated failure', $result['error']);

        // Verify order status was updated
        $this->order->refresh();
        $this->assertEquals(Order::PAYMENT_STATUS_FAILED, $this->order->payment_status);
        $this->assertEquals('paypal', $this->order->payment_gateway);
    }

    #[Test]
    public function can_create_paypal_order_with_valid_response()
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

        $result = $this->paypalService->createOrder($this->order);

        $this->assertTrue($result['success']);
        $this->assertEquals('PAYPAL_ORDER_123', $result['paypal_order_id']);
        $this->assertStringContainsString('checkoutnow?token=PAYPAL_ORDER_123', $result['approval_url']);
    }

    #[Test]
    public function handles_paypal_order_creation_failure()
    {
        // Mock failed PayPal API responses
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

        $result = $this->paypalService->createOrder($this->order);

        $this->assertFalse($result['success']);
        $this->assertEquals('Error creating PayPal order', $result['error']);
        $this->assertArrayHasKey('details', $result);
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

        $result = $this->paypalService->captureOrder('PAYPAL_ORDER_123', $this->order);

        $this->assertTrue($result['success']);
        $this->assertEquals('COMPLETED', $result['status']);
        $this->assertEquals('CAPTURE_123', $result['capture_id']);
    }

    #[Test]
    public function handles_paypal_order_capture_failure()
    {
        // Mock failed PayPal API responses
        Http::fake([
            'https://api.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 32400
            ], 200),
            'https://api.sandbox.paypal.com/v2/checkout/orders/PAYPAL_ORDER_123/capture' => Http::response([
                'error' => 'ORDER_NOT_APPROVED',
                'error_description' => 'Order has not been approved by the payer.'
            ], 422)
        ]);

        $result = $this->paypalService->captureOrder('PAYPAL_ORDER_123', $this->order);

        $this->assertFalse($result['success']);
        $this->assertEquals('Error capturing PayPal order', $result['error']);
        $this->assertArrayHasKey('details', $result);
    }

    #[Test]
    public function can_get_paypal_order_details()
    {
        // Mock successful PayPal API responses
        Http::fake([
            'https://api.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 32400
            ], 200),
            'https://api.sandbox.paypal.com/v2/checkout/orders/PAYPAL_ORDER_123' => Http::response([
                'id' => 'PAYPAL_ORDER_123',
                'status' => 'APPROVED',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => 'MXN',
                            'value' => '1500.00'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->paypalService->getOrderDetails('PAYPAL_ORDER_123');

        $this->assertTrue($result['success']);
        $this->assertEquals('PAYPAL_ORDER_123', $result['response']['id']);
        $this->assertEquals('APPROVED', $result['response']['status']);
    }

    #[Test]
    public function handles_access_token_failure()
    {
        // Mock failed access token request
        Http::fake([
            'https://api.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'error' => 'invalid_client',
                'error_description' => 'Client Authentication failed'
            ], 401)
        ]);

        $result = $this->paypalService->createOrder($this->order);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Error obtaining PayPal access token', $result['error']);
    }

    #[Test]
    public function builds_correct_order_data_structure()
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->paypalService);
        $method = $reflection->getMethod('buildOrderData');
        $method->setAccessible(true);

        $orderData = $method->invoke($this->paypalService, $this->order);

        // Verify structure
        $this->assertEquals('CAPTURE', $orderData['intent']);
        $this->assertArrayHasKey('purchase_units', $orderData);
        $this->assertArrayHasKey('application_context', $orderData);

        $purchaseUnit = $orderData['purchase_units'][0];
        $this->assertEquals($this->order->order_number, $purchaseUnit['reference_id']);
        $this->assertEquals('MXN', $purchaseUnit['amount']['currency_code']);
        $this->assertEquals('1500.00', $purchaseUnit['amount']['value']);

        // Verify application context
        $appContext = $orderData['application_context'];
        $this->assertEquals('CronosMatic', $appContext['brand_name']);
        $this->assertEquals('PAY_NOW', $appContext['user_action']);
    }

    private function createTestOrder(): void
    {
        // Create user
        $user = User::factory()->create();

        // Create addresses using correct Address model structure
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
