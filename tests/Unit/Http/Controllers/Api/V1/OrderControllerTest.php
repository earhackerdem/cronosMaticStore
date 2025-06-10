<?php

namespace Tests\Unit\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PayPalPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    private OrderController $controller;
    private CartService $cartService;
    private OrderService $orderService;
    private PayPalPaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cartService = Mockery::mock(CartService::class);
        $this->orderService = Mockery::mock(OrderService::class);
        $this->paymentService = Mockery::mock(PayPalPaymentService::class);

        $this->controller = new OrderController(
            $this->cartService,
            $this->orderService,
            $this->paymentService
        );
    }

    #[Test]
    public function it_creates_order_successfully_for_authenticated_user(): void
    {
        // Arrange
        $user = User::factory()->make(['id' => 1]);
        $cart = Cart::factory()->make(['id' => 1, 'user_id' => 1]);
        $cartItem = CartItem::factory()->make(['cart_id' => 1]);
        $cart->setRelation('items', collect([$cartItem]));

        $order = Mockery::mock(Order::class);
        $order->id = 1;
        $order->user_id = 1;
        $order->total_amount = 100.00;

        $request = Mockery::mock(StoreOrderRequest::class);
        $request->shouldReceive('validated')
            ->with('shipping_address_id')
            ->andReturn(1);
        $request->shouldReceive('validated')
            ->with('billing_address_id')
            ->andReturn(null);
        $request->shouldReceive('validated')
            ->with('guest_email')
            ->andReturn(null);
        $request->shouldReceive('validated')
            ->with('shipping_cost', 0.00)
            ->andReturn(0.00);
        $request->shouldReceive('validated')
            ->with('shipping_method_name')
            ->andReturn('Standard');
        $request->shouldReceive('validated')
            ->with('notes')
            ->andReturn(null);
        $request->shouldReceive('validated')
            ->with('payment_method')
            ->andReturn('paypal');

        Auth::shouldReceive('guard')
            ->with('sanctum')
            ->andReturnSelf();
        Auth::shouldReceive('user')
            ->andReturn($user);
        Auth::shouldReceive('id')
            ->andReturn(1);

        $this->cartService->shouldReceive('getOrCreateCartForUser')
            ->with(1)
            ->andReturn($cart);

        $this->cartService->shouldReceive('validateCartStock')
            ->with($cart)
            ->andReturn([]);

        $this->orderService->shouldReceive('createOrderFromCart')
            ->andReturn($order);

        $this->paymentService->shouldReceive('simulateSuccessfulPayment')
            ->with($order)
            ->andReturn([
                'success' => true,
                'payment_id' => 'test_payment_123'
            ]);

        $this->orderService->shouldReceive('updatePaymentStatus')
            ->andReturn($order);

        $this->cartService->shouldReceive('clearCart')
            ->with($cart)
            ->andReturn(true);

        $order->shouldReceive('load')
            ->with(['orderItems.product', 'shippingAddress', 'billingAddress', 'user'])
            ->andReturnSelf();

        // Mock the config call
        config(['app.env' => 'testing']);

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Pedido creado exitosamente.', $data['message']);
        $this->assertArrayHasKey('order', $data['data']);
        $this->assertArrayHasKey('payment', $data['data']);
    }

    #[Test]
    public function it_returns_error_when_cart_is_empty(): void
    {
        // Arrange
        $user = User::factory()->make(['id' => 1]);
        $cart = Cart::factory()->make(['id' => 1, 'user_id' => 1]);
        $cart->setRelation('items', collect([])); // Empty cart

        $request = Mockery::mock(StoreOrderRequest::class);

        Auth::shouldReceive('guard')
            ->with('sanctum')
            ->andReturnSelf();
        Auth::shouldReceive('user')
            ->andReturn($user);

        $this->cartService->shouldReceive('getOrCreateCartForUser')
            ->with(1)
            ->andReturn($cart);

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('No se puede crear un pedido con un carrito vacío.', $data['message']);
    }

    #[Test]
    public function it_returns_error_when_stock_is_insufficient(): void
    {
        // Arrange
        $user = User::factory()->make(['id' => 1]);
        $cart = Cart::factory()->make(['id' => 1, 'user_id' => 1]);
        $cartItem = CartItem::factory()->make(['cart_id' => 1]);
        $cart->setRelation('items', collect([$cartItem]));

        $request = Mockery::mock(StoreOrderRequest::class);

        Auth::shouldReceive('guard')
            ->with('sanctum')
            ->andReturnSelf();
        Auth::shouldReceive('user')
            ->andReturn($user);

        $this->cartService->shouldReceive('getOrCreateCartForUser')
            ->with(1)
            ->andReturn($cart);

        $this->cartService->shouldReceive('validateCartStock')
            ->with($cart)
            ->andReturn(['Product 1 has insufficient stock']);

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Algunos productos no tienen stock suficiente.', $data['message']);
    }

    #[Test]
    public function it_handles_payment_failure(): void
    {
        // Arrange
        $user = User::factory()->make(['id' => 1]);
        $cart = Cart::factory()->make(['id' => 1, 'user_id' => 1]);
        $cartItem = CartItem::factory()->make(['cart_id' => 1]);
        $cart->setRelation('items', collect([$cartItem]));

        $order = Mockery::mock(Order::class);
        $order->id = 1;
        $order->user_id = 1;
        $order->total_amount = 100.00;

        $request = Mockery::mock(StoreOrderRequest::class);
        $request->shouldReceive('validated')
            ->with('shipping_address_id')
            ->andReturn(1);
        $request->shouldReceive('validated')
            ->with('billing_address_id')
            ->andReturn(null);
        $request->shouldReceive('validated')
            ->with('guest_email')
            ->andReturn(null);
        $request->shouldReceive('validated')
            ->with('shipping_cost', 0.00)
            ->andReturn(0.00);
        $request->shouldReceive('validated')
            ->with('shipping_method_name')
            ->andReturn('Standard');
        $request->shouldReceive('validated')
            ->with('notes')
            ->andReturn(null);
        $request->shouldReceive('validated')
            ->with('payment_method')
            ->andReturn('paypal');

        Auth::shouldReceive('guard')
            ->with('sanctum')
            ->andReturnSelf();
        Auth::shouldReceive('user')
            ->andReturn($user);
        Auth::shouldReceive('id')
            ->andReturn(1);

        $this->cartService->shouldReceive('getOrCreateCartForUser')
            ->with(1)
            ->andReturn($cart);

        $this->cartService->shouldReceive('validateCartStock')
            ->with($cart)
            ->andReturn([]);

        $this->orderService->shouldReceive('createOrderFromCart')
            ->andReturn($order);

        $this->paymentService->shouldReceive('simulateSuccessfulPayment')
            ->with($order)
            ->andReturn([
                'success' => false,
                'error' => 'Payment failed'
            ]);

        $this->orderService->shouldReceive('cancelOrder')
            ->with(1, 'Pago fallido: Payment failed')
            ->andReturn($order);

        config(['app.env' => 'testing']);

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Error al procesar el pago.', $data['message']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
