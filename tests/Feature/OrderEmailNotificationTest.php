<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmationMail;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderEmailNotificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = app(OrderService::class);
    }

    #[Test]
    public function sends_confirmation_email_when_payment_status_updated_to_paid()
    {
        // Arrange
        Mail::fake();

        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $address = Address::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        $product = Product::factory()->create([
            'name' => 'Reloj de Lujo',
            'price' => 2500.00
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'shipping_address_id' => $address->id,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'total_amount' => 2500.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'price_per_unit' => 2500.00,
            'total_price' => 2500.00,
        ]);

        // Act
        $this->orderService->updatePaymentStatus(
            $order->id,
            Order::PAYMENT_STATUS_PAID,
            'pay_12345',
            'paypal'
        );

        // Assert
        Mail::assertQueued(OrderConfirmationMail::class, function ($mail) use ($order, $user) {
            return $mail->order->id === $order->id &&
                   $mail->hasTo($user->email);
        });
    }

    #[Test]
    public function sends_confirmation_email_to_guest_email_when_guest_order()
    {
        // Arrange
        Mail::fake();

        $guestEmail = 'guest@example.com';

        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        $product = Product::factory()->create([
            'name' => 'Reloj Clásico',
            'price' => 1800.00
        ]);

        $order = Order::factory()->create([
            'user_id' => null,
            'guest_email' => $guestEmail,
            'shipping_address_id' => $address->id,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'total_amount' => 1800.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'price_per_unit' => 1800.00,
            'total_price' => 1800.00,
        ]);

        // Act
        $this->orderService->updatePaymentStatus(
            $order->id,
            Order::PAYMENT_STATUS_PAID,
            'pay_67890',
            'stripe'
        );

        // Assert
        Mail::assertQueued(OrderConfirmationMail::class, function ($mail) use ($order, $guestEmail) {
            return $mail->order->id === $order->id &&
                   $mail->hasTo($guestEmail);
        });
    }

    #[Test]
    public function does_not_send_email_when_payment_status_is_not_paid()
    {
        // Arrange
        Mail::fake();

        $user = User::factory()->create(['email' => 'john@example.com']);
        $address = Address::factory()->create(['user_id' => $user->id]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'shipping_address_id' => $address->id,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
        ]);

        // Act - Update to failed payment
        $this->orderService->updatePaymentStatus(
            $order->id,
            Order::PAYMENT_STATUS_FAILED
        );

        // Assert
        Mail::assertNotQueued(OrderConfirmationMail::class);
    }

    #[Test]
    public function does_not_send_email_when_no_email_available()
    {
        // Arrange
        Mail::fake();

        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        $order = Order::factory()->create([
            'user_id' => null,
            'guest_email' => null, // No email available
            'shipping_address_id' => $address->id,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
        ]);

        // Act
        $this->orderService->updatePaymentStatus(
            $order->id,
            Order::PAYMENT_STATUS_PAID
        );

        // Assert
        Mail::assertNotQueued(OrderConfirmationMail::class);
    }

    #[Test]
    public function updates_order_status_to_processing_when_payment_is_paid()
    {
        // Arrange
        Mail::fake();

        $user = User::factory()->create(['email' => 'john@example.com']);
        $address = Address::factory()->create(['user_id' => $user->id]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'shipping_address_id' => $address->id,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
        ]);

        // Act
        $updatedOrder = $this->orderService->updatePaymentStatus(
            $order->id,
            Order::PAYMENT_STATUS_PAID,
            'pay_12345',
            'paypal'
        );

        // Assert
        $this->assertEquals(Order::STATUS_PROCESSING, $updatedOrder->status);
        $this->assertEquals(Order::PAYMENT_STATUS_PAID, $updatedOrder->payment_status);
        $this->assertEquals('pay_12345', $updatedOrder->payment_id);
        $this->assertEquals('paypal', $updatedOrder->payment_gateway);

        // Confirm email was sent
        Mail::assertQueued(OrderConfirmationMail::class);
    }

    #[Test]
    public function email_contains_correct_order_information()
    {
        // Arrange
        Mail::fake();

        $user = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com'
        ]);

        $address = Address::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'address_line_1' => '456 Oak Avenue',
            'city' => 'Guadalajara',
            'state' => 'Jalisco',
            'postal_code' => '44100',
            'country' => 'México'
        ]);

        $product1 = Product::factory()->create([
            'name' => 'Reloj Deportivo',
            'price' => 1200.00
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Reloj Elegante',
            'price' => 3500.00
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'order_number' => 'CM-2024-EMAIL001',
            'shipping_address_id' => $address->id,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'subtotal_amount' => 4700.00,
            'shipping_cost' => 200.00,
            'total_amount' => 4900.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'product_name' => $product1->name,
            'quantity' => 1,
            'price_per_unit' => 1200.00,
            'total_price' => 1200.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'product_name' => $product2->name,
            'quantity' => 1,
            'price_per_unit' => 3500.00,
            'total_price' => 3500.00,
        ]);

        // Act
        $this->orderService->updatePaymentStatus(
            $order->id,
            Order::PAYMENT_STATUS_PAID
        );

        // Assert
        Mail::assertQueued(OrderConfirmationMail::class, function ($mail) use ($order, $user, $address) {
            // Check the mail has correct order
            $this->assertEquals($order->order_number, $mail->order->order_number);

            // Check the mail envelope
            $envelope = $mail->envelope();
            $this->assertEquals('Confirmación de Pedido #CM-2024-EMAIL001', $envelope->subject);

            // Check the mail content has correct data
            $content = $mail->content();
            $this->assertArrayHasKey('order', $content->with);
            $this->assertArrayHasKey('orderUrl', $content->with);

            $mailOrder = $content->with['order'];
            $this->assertEquals('CM-2024-EMAIL001', $mailOrder->order_number);
            $this->assertEquals(4900.00, $mailOrder->total_amount);
            $this->assertEquals(2, $mailOrder->orderItems->count());

            return true;
        });
    }
}
