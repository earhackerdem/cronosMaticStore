<?php

namespace Tests\Unit\Unit;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = new OrderService();
    }

    #[Test]
    public function can_create_order_from_cart_for_registered_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100.00,
            'stock_quantity' => 10,
        ]);

        $cart = Cart::factory()->create(['user_id' => $user->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $cart->load('items.product');

        $shippingAddress = Address::factory()->create([
            'user_id' => $user->id,
            'type' => Address::TYPE_SHIPPING,
        ]);

        // Act
        $order = $this->orderService->createOrderFromCart(
            $cart,
            $shippingAddress->id,
            null,
            null,
            ['shipping_cost' => 15.00]
        );

        // Assert
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals($shippingAddress->id, $order->shipping_address_id);
        $this->assertEquals(200.00, $order->subtotal_amount);
        $this->assertEquals(15.00, $order->shipping_cost);
        $this->assertEquals(215.00, $order->total_amount);
        $this->assertEquals(Order::STATUS_PENDING_PAYMENT, $order->status);
        $this->assertEquals(Order::PAYMENT_STATUS_PENDING, $order->payment_status);
        $this->assertNotEmpty($order->order_number);
        $this->assertTrue(str_starts_with($order->order_number, 'CM-'));

        // Check order items
        $this->assertCount(1, $order->orderItems);
        $orderItem = $order->orderItems->first();
        $this->assertEquals($product->id, $orderItem->product_id);
        $this->assertEquals($product->name, $orderItem->product_name);
        $this->assertEquals(2, $orderItem->quantity);
        $this->assertEquals(100.00, $orderItem->price_per_unit);
        $this->assertEquals(200.00, $orderItem->total_price);

        // Check stock reduction
        $product->refresh();
        $this->assertEquals(8, $product->stock_quantity);
    }

    #[Test]
    public function can_create_order_from_cart_for_guest_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 50.00,
            'stock_quantity' => 5,
        ]);

        $cart = Cart::factory()->create(['user_id' => null, 'session_id' => 'guest-session-123']);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $cart->load('items.product');

        $shippingAddress = Address::factory()->create([
            'user_id' => $user->id,
            'type' => Address::TYPE_SHIPPING,
        ]);

        // Act
        $order = $this->orderService->createOrderFromCart(
            $cart,
            $shippingAddress->id,
            null,
            'guest@example.com'
        );

        // Assert
        $this->assertNull($order->user_id);
        $this->assertEquals('guest@example.com', $order->guest_email);
        $this->assertEquals(50.00, $order->subtotal_amount);
        $this->assertEquals(0.00, $order->shipping_cost);
        $this->assertEquals(50.00, $order->total_amount);
    }

    #[Test]
    public function cannot_create_order_from_empty_cart(): void
    {
        // Arrange
        $user = User::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $shippingAddress = Address::factory()->create([
            'user_id' => $user->id,
            'type' => Address::TYPE_SHIPPING,
        ]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create order from empty cart');

        $this->orderService->createOrderFromCart($cart, $shippingAddress->id);
    }

    #[Test]
    public function cannot_create_order_with_insufficient_stock(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100.00,
            'stock_quantity' => 1,
        ]);

        $cart = Cart::factory()->create(['user_id' => $user->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 5, // More than available stock
        ]);
        $cart->load('items.product');

        $shippingAddress = Address::factory()->create([
            'user_id' => $user->id,
            'type' => Address::TYPE_SHIPPING,
        ]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient stock for product');

        $this->orderService->createOrderFromCart($cart, $shippingAddress->id);
    }

    #[Test]
    public function can_get_user_orders_with_pagination(): void
    {
        // Arrange
        $user = User::factory()->create();
        $orders = Order::factory()->count(25)->create(['user_id' => $user->id]);

        // Act
        $paginatedOrders = $this->orderService->getUserOrders($user->id, 10);

        // Assert
        $this->assertEquals(10, $paginatedOrders->count());
        $this->assertEquals(25, $paginatedOrders->total());
        $this->assertEquals(3, $paginatedOrders->lastPage());
    }

    #[Test]
    public function can_get_specific_user_order(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        // Act
        $retrievedOrder = $this->orderService->getUserOrder($order->id, $user->id);

        // Assert
        $this->assertEquals($order->id, $retrievedOrder->id);
        $this->assertEquals($user->id, $retrievedOrder->user_id);
    }

    #[Test]
    public function cannot_get_order_from_different_user(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user1->id]);

        // Act & Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->orderService->getUserOrder($order->id, $user2->id);
    }

    #[Test]
    public function can_get_order_by_number(): void
    {
        // Arrange
        $order = Order::factory()->create(['order_number' => 'CM-2025-TEST123']);

        // Act
        $retrievedOrder = $this->orderService->getOrderByNumber('CM-2025-TEST123');

        // Assert
        $this->assertEquals($order->id, $retrievedOrder->id);
        $this->assertEquals('CM-2025-TEST123', $retrievedOrder->order_number);
    }

    #[Test]
    public function can_update_order_status(): void
    {
        // Arrange
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING_PAYMENT]);

        // Act
        $updatedOrder = $this->orderService->updateOrderStatus($order->id, Order::STATUS_PROCESSING);

        // Assert
        $this->assertEquals(Order::STATUS_PROCESSING, $updatedOrder->status);
    }

    #[Test]
    public function cannot_update_order_with_invalid_status(): void
    {
        // Arrange
        $order = Order::factory()->create();

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid order status: invalid_status');

        $this->orderService->updateOrderStatus($order->id, 'invalid_status');
    }

    #[Test]
    public function can_update_payment_status(): void
    {
        // Arrange
        $order = Order::factory()->create([
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
        ]);

        // Act
        $updatedOrder = $this->orderService->updatePaymentStatus(
            $order->id,
            Order::PAYMENT_STATUS_PAID,
            'payment_123',
            'paypal'
        );

        // Assert
        $this->assertEquals(Order::PAYMENT_STATUS_PAID, $updatedOrder->payment_status);
        $this->assertEquals('payment_123', $updatedOrder->payment_id);
        $this->assertEquals('paypal', $updatedOrder->payment_gateway);
        $this->assertEquals(Order::STATUS_PROCESSING, $updatedOrder->status); // Should auto-update
    }

    #[Test]
    public function can_cancel_order(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 5,
        ]);

        $order = Order::factory()->create(['status' => Order::STATUS_PENDING_PAYMENT]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Act
        $cancelledOrder = $this->orderService->cancelOrder($order->id, 'Customer request');

        // Assert
        $this->assertEquals(Order::STATUS_CANCELLED, $cancelledOrder->status);
        $this->assertStringContainsString('Customer request', $cancelledOrder->notes);

        // Check stock restoration
        $product->refresh();
        $this->assertEquals(7, $product->stock_quantity);
    }

    #[Test]
    public function cannot_cancel_delivered_order(): void
    {
        // Arrange
        $order = Order::factory()->create(['status' => Order::STATUS_DELIVERED]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order cannot be cancelled in its current state');

        $this->orderService->cancelOrder($order->id);
    }

    #[Test]
    public function can_get_user_order_statistics(): void
    {
        // Arrange
        $user = User::factory()->create();

        Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'total_amount' => 100.00,
        ]);

        Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_DELIVERED,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'total_amount' => 200.00,
        ]);

        Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_CANCELLED,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'total_amount' => 50.00,
        ]);

        // Act
        $stats = $this->orderService->getUserOrderStats($user->id);

        // Assert
        $this->assertEquals(3, $stats['total_orders']);
        $this->assertEquals(200.00, $stats['total_spent']);
        $this->assertEquals(1, $stats['pending_orders']);
        $this->assertEquals(0, $stats['processing_orders']);
        $this->assertEquals(0, $stats['shipped_orders']);
        $this->assertEquals(1, $stats['delivered_orders']);
        $this->assertEquals(1, $stats['cancelled_orders']);
    }

    #[Test]
    public function can_calculate_order_summary(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product1 = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100.00,
        ]);
        $product2 = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 50.00,
        ]);

        $cart = Cart::factory()->create(['user_id' => $user->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product2->id,
            'quantity' => 1,
        ]);
        $cart->load('items.product');

        // Act
        $summary = $this->orderService->calculateOrderSummary($cart, 25.00);

        // Assert
        $this->assertEquals(250.00, $summary['subtotal']); // (100*2) + (50*1)
        $this->assertEquals(25.00, $summary['shipping_cost']);
        $this->assertEquals(275.00, $summary['total_amount']);
        $this->assertEquals(3, $summary['items_count']); // 2 + 1
    }

    #[Test]
    public function can_search_orders_with_filters(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Order::factory()->create([
            'user_id' => $user1->id,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'order_number' => 'CM-2025-ABC123',
        ]);

        Order::factory()->create([
            'user_id' => $user2->id,
            'status' => Order::STATUS_PROCESSING,
            'order_number' => 'CM-2025-XYZ789',
        ]);

        // Act
        $results = $this->orderService->searchOrders([
            'user_id' => $user1->id,
            'status' => Order::STATUS_PENDING_PAYMENT,
        ]);

        // Assert
        $this->assertEquals(1, $results->count());
        $this->assertEquals($user1->id, $results->first()->user_id);
        $this->assertEquals(Order::STATUS_PENDING_PAYMENT, $results->first()->status);
    }
}
