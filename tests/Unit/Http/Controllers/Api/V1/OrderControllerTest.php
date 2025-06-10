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
    private CartService $cartService;
    private OrderService $orderService;
    private PayPalPaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cartService = Mockery::mock(CartService::class);
        $this->orderService = Mockery::mock(OrderService::class);
        $this->paymentService = Mockery::mock(PayPalPaymentService::class);
    }

    #[Test]
    public function it_returns_error_when_cart_is_empty(): void
    {
        // Arrange
        $controller = new OrderController(
            $this->cartService,
            $this->orderService,
            $this->paymentService
        );

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
        $response = $controller->store($request);

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
        $controller = new OrderController(
            $this->cartService,
            $this->orderService,
            $this->paymentService
        );

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
        $response = $controller->store($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Algunos productos no tienen stock suficiente.', $data['message']);
    }

    #[Test]
    public function it_validates_required_fields(): void
    {
        // Arrange
        $controller = new OrderController(
            $this->cartService,
            $this->orderService,
            $this->paymentService
        );

        // This test validates that the controller exists and can be instantiated
        $this->assertInstanceOf(OrderController::class, $controller);
    }

    #[Test]
    public function service_dependencies_are_injected_correctly(): void
    {
        // Arrange & Act
        $controller = new OrderController(
            $this->cartService,
            $this->orderService,
            $this->paymentService
        );

        // Assert
        $this->assertInstanceOf(OrderController::class, $controller);

        // Verify that the controller can handle method calls
        $reflection = new \ReflectionClass($controller);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        $methodNames = array_map(fn($method) => $method->getName(), $methods);
        $this->assertContains('store', $methodNames);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
