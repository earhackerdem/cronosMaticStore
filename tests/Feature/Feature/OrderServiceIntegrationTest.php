<?php

namespace Tests\Feature\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $orderService;
    private CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = new OrderService();
        $this->cartService = new CartService();
    }

    #[Test]
    public function complete_order_flow_for_registered_user(): void
    {
        // Arrange: Create user, products, and cart
        $user = User::factory()->create();
        $category = Category::factory()->create();

                $product1 = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Reloj Automático Premium',
            'price' => 299.99,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $product2 = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Reloj Deportivo',
            'price' => 149.99,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        // Create cart and add products
        $cart = $this->cartService->getOrCreateCartForUser($user->id);
        $this->cartService->addProductToCart($cart, $product1->id, 2);
        $this->cartService->addProductToCart($cart, $product2->id, 1);

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
            'country' => 'México',
        ]);

        $billingAddress = Address::factory()->create([
            'user_id' => $user->id,
            'type' => Address::TYPE_BILLING,
        ]);

        // Act: Create order from cart
        $order = $this->orderService->createOrderFromCart(
            $cart,
            $shippingAddress->id,
            $billingAddress->id,
            null,
            [
                'shipping_cost' => 25.00,
                'shipping_method_name' => 'Envío Express',
                'notes' => 'Entregar en horario de oficina',
            ]
        );

        // Assert: Verify order creation
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertNull($order->guest_email);
        $this->assertEquals($shippingAddress->id, $order->shipping_address_id);
        $this->assertEquals($billingAddress->id, $order->billing_address_id);
        $this->assertEquals(Order::STATUS_PENDING_PAYMENT, $order->status);
        $this->assertEquals(Order::PAYMENT_STATUS_PENDING, $order->payment_status);
        $this->assertEquals('Envío Express', $order->shipping_method_name);
        $this->assertEquals('Entregar en horario de oficina', $order->notes);

        // Verify order totals
        $expectedSubtotal = (299.99 * 2) + (149.99 * 1); // 749.97
        $this->assertEquals($expectedSubtotal, $order->subtotal_amount);
        $this->assertEquals(25.00, $order->shipping_cost);
        $this->assertEquals($expectedSubtotal + 25.00, $order->total_amount);

        // Verify order items
        $this->assertCount(2, $order->orderItems);

        $orderItem1 = $order->orderItems->where('product_id', $product1->id)->first();
        $this->assertEquals($product1->name, $orderItem1->product_name);
        $this->assertEquals(2, $orderItem1->quantity);
        $this->assertEquals(299.99, $orderItem1->price_per_unit);
        $this->assertEquals(599.98, $orderItem1->total_price);

        $orderItem2 = $order->orderItems->where('product_id', $product2->id)->first();
        $this->assertEquals($product2->name, $orderItem2->product_name);
        $this->assertEquals(1, $orderItem2->quantity);
        $this->assertEquals(149.99, $orderItem2->price_per_unit);
        $this->assertEquals(149.99, $orderItem2->total_price);

        // Verify stock reduction
        $product1->refresh();
        $product2->refresh();
        $this->assertEquals(8, $product1->stock_quantity); // 10 - 2
        $this->assertEquals(4, $product2->stock_quantity); // 5 - 1

        // Verify order number format
        $this->assertMatchesRegularExpression('/^CM-\d{4}-[A-Z0-9]{8}$/', $order->order_number);
    }

    #[Test]
    public function complete_order_flow_for_guest_user(): void
    {
        // Arrange: Create products and guest cart
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 199.99,
            'stock_quantity' => 3,
            'is_active' => true,
        ]);

        $sessionId = 'guest-session-' . uniqid();
        $cart = $this->cartService->getOrCreateCartForGuest($sessionId);
        $this->cartService->addProductToCart($cart, $product->id, 1);

        // Create address for guest
        $user = User::factory()->create(); // Temporary user for address creation
        $shippingAddress = Address::factory()->create([
            'user_id' => $user->id,
            'type' => Address::TYPE_SHIPPING,
        ]);

        // Act: Create order from cart
        $order = $this->orderService->createOrderFromCart(
            $cart,
            $shippingAddress->id,
            null,
            'guest@example.com'
        );

        // Assert: Verify guest order
        $this->assertNull($order->user_id);
        $this->assertEquals('guest@example.com', $order->guest_email);
        $this->assertEquals('guest@example.com', $order->email);
        $this->assertEquals(199.99, $order->subtotal_amount);
        $this->assertEquals(0.00, $order->shipping_cost);
        $this->assertEquals(199.99, $order->total_amount);
    }

    #[Test]
    public function order_payment_flow_updates_status_correctly(): void
    {
        // Arrange: Create order
        $order = Order::factory()->pendingPayment()->create();

        // Act: Update payment status to paid
        $updatedOrder = $this->orderService->updatePaymentStatus(
            $order->id,
            Order::PAYMENT_STATUS_PAID,
            'paypal_payment_123',
            'paypal'
        );

        // Assert: Verify payment and status update
        $this->assertEquals(Order::PAYMENT_STATUS_PAID, $updatedOrder->payment_status);
        $this->assertEquals('paypal_payment_123', $updatedOrder->payment_id);
        $this->assertEquals('paypal', $updatedOrder->payment_gateway);
        $this->assertEquals(Order::STATUS_PROCESSING, $updatedOrder->status); // Auto-updated
        $this->assertTrue($updatedOrder->isPaid());
    }

    #[Test]
    public function order_cancellation_restores_stock(): void
    {
        // Arrange: Create order with products
        $category = Category::factory()->create();
        $product1 = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 5,
        ]);
        $product2 = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 3,
        ]);

        $order = Order::factory()->pendingPayment()->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 1,
        ]);

        // Act: Cancel order
        $cancelledOrder = $this->orderService->cancelOrder($order->id, 'Customer changed mind');

        // Assert: Verify cancellation and stock restoration
        $this->assertEquals(Order::STATUS_CANCELLED, $cancelledOrder->status);
        $this->assertStringContainsString('Customer changed mind', $cancelledOrder->notes);

        $product1->refresh();
        $product2->refresh();
        $this->assertEquals(7, $product1->stock_quantity); // 5 + 2
        $this->assertEquals(4, $product2->stock_quantity); // 3 + 1
    }

    #[Test]
    public function user_order_history_and_statistics(): void
    {
        // Arrange: Create user with multiple orders
        $user = User::factory()->create();

        // Create orders with different statuses
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
            'total_amount' => 250.00,
        ]);

        Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_PROCESSING,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'total_amount' => 150.00,
        ]);

        // Act: Get user orders and statistics
        $orders = $this->orderService->getUserOrders($user->id);
        $stats = $this->orderService->getUserOrderStats($user->id);

        // Assert: Verify orders and statistics
        $this->assertEquals(3, $orders->total());

        $this->assertEquals(3, $stats['total_orders']);
        $this->assertEquals(400.00, $stats['total_spent']); // Only paid orders: 250 + 150
        $this->assertEquals(1, $stats['pending_orders']);
        $this->assertEquals(1, $stats['processing_orders']);
        $this->assertEquals(1, $stats['delivered_orders']);
        $this->assertEquals(0, $stats['cancelled_orders']);
    }

    #[Test]
    public function order_search_with_multiple_filters(): void
    {
        // Arrange: Create orders with different attributes
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $order1 = Order::factory()->create([
            'user_id' => $user1->id,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'order_number' => 'CM-2025-ABC12345',
            'created_at' => now()->subDays(5),
        ]);

                $order2 = Order::factory()->create([
            'user_id' => $user2->id,
            'status' => Order::STATUS_DELIVERED,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'order_number' => 'CM-2025-XYZ67890',
            'created_at' => now()->subDays(2),
        ]);

        $order3 = Order::factory()->guest()->create([
            'guest_email' => 'test@guest.com',
            'status' => Order::STATUS_PROCESSING,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'created_at' => now()->subDays(1),
        ]);

        // Act & Assert: Test different search filters

        // Search by user
        $userOrders = $this->orderService->searchOrders(['user_id' => $user1->id]);
        $this->assertEquals(1, $userOrders->count());
        $this->assertEquals($order1->id, $userOrders->first()->id);

        // Search by status
        $pendingOrders = $this->orderService->searchOrders(['status' => Order::STATUS_PENDING_PAYMENT]);
        $this->assertEquals(1, $pendingOrders->count());

        // Search by payment status
        $paidOrders = $this->orderService->searchOrders(['payment_status' => Order::PAYMENT_STATUS_PAID]);
        $this->assertEquals(1, $paidOrders->count());

        // Search by order number
        $orderByNumber = $this->orderService->searchOrders(['order_number' => 'ABC123']);
        $this->assertEquals(1, $orderByNumber->count());

        // Search by guest email
        $guestOrders = $this->orderService->searchOrders(['guest_email' => 'test@guest.com']);
        $this->assertEquals(1, $guestOrders->count());

        // Search by date range
        $recentOrders = $this->orderService->searchOrders([
            'date_from' => now()->subDays(3)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]);
        $this->assertEquals(2, $recentOrders->count()); // order2 and order3
    }

    #[Test]
    public function order_summary_calculation_is_accurate(): void
    {
        // Arrange: Create cart with multiple products
        $user = User::factory()->create();
        $category = Category::factory()->create();

                        $product1 = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 99.99,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $product2 = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 149.50,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $product3 = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 75.25,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $cart = $this->cartService->getOrCreateCartForUser($user->id);
        $this->cartService->addProductToCart($cart, $product1->id, 3);
        $this->cartService->addProductToCart($cart, $product2->id, 1);
        $this->cartService->addProductToCart($cart, $product3->id, 2);

        // Act: Calculate order summary
        $summary = $this->orderService->calculateOrderSummary($cart, 35.00);

        // Assert: Verify calculations
        $expectedSubtotal = (99.99 * 3) + (149.50 * 1) + (75.25 * 2); // 299.97 + 149.50 + 150.50 = 599.97
        $this->assertEquals($expectedSubtotal, $summary['subtotal']);
        $this->assertEquals(35.00, $summary['shipping_cost']);
        $this->assertEquals($expectedSubtotal + 35.00, $summary['total_amount']);
        $this->assertEquals(6, $summary['items_count']); // 3 + 1 + 2
    }

    #[Test]
    public function cannot_create_order_when_product_stock_insufficient(): void
    {
        // Arrange: Create product with limited stock
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 2,
            'is_active' => true,
        ]);

                $cart = $this->cartService->getOrCreateCartForUser($user->id);
        $this->cartService->addProductToCart($cart, $product->id, 2); // Add 2 items to cart

        // Simulate stock reduction (someone else bought the product)
        $product->update(['stock_quantity' => 1]); // Now only 1 available, but cart has 2

        $cart->load('items.product');

        $shippingAddress = Address::factory()->create([
            'user_id' => $user->id,
            'type' => Address::TYPE_SHIPPING,
        ]);

        // Act & Assert: Expect exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient stock for product');

        $this->orderService->createOrderFromCart($cart, $shippingAddress->id);
    }
}
