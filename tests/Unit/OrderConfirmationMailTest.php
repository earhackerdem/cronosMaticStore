<?php

namespace Tests\Unit;

use App\Mail\OrderConfirmationMail;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderConfirmationMailTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_create_order_confirmation_mail_with_order()
    {
        // Arrange
        $user = User::factory()->create(['name' => 'John Doe']);
        $address = Address::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'order_number' => 'CM-2024-TEST123',
            'shipping_address_id' => $address->id,
        ]);

        // Act
        $mail = new OrderConfirmationMail($order);

        // Assert
        $this->assertInstanceOf(OrderConfirmationMail::class, $mail);
        $this->assertEquals($order->id, $mail->order->id);
    }

    #[Test]
    public function has_correct_subject_with_order_number()
    {
        // Arrange
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'order_number' => 'CM-2024-TEST123',
            'shipping_address_id' => $address->id,
        ]);

        // Act
        $mail = new OrderConfirmationMail($order);
        $envelope = $mail->envelope();

        // Assert
        $this->assertEquals('Confirmación de Pedido #CM-2024-TEST123', $envelope->subject);
    }

    #[Test]
    public function has_correct_markdown_template()
    {
        // Arrange
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'shipping_address_id' => $address->id,
        ]);

        // Act
        $mail = new OrderConfirmationMail($order);
        $content = $mail->content();

        // Assert
        $this->assertEquals('emails.orders.confirmation', $content->markdown);
    }

    #[Test]
    public function passes_order_data_to_template()
    {
        // Arrange
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'order_number' => 'CM-2024-TEST123',
            'shipping_address_id' => $address->id,
        ]);

        // Act
        $mail = new OrderConfirmationMail($order);
        $content = $mail->content();

        // Assert
        $this->assertArrayHasKey('order', $content->with);
        $this->assertArrayHasKey('orderUrl', $content->with);
        $this->assertEquals($order->id, $content->with['order']->id);
        $this->assertEquals(url('/orders/' . $order->order_number), $content->with['orderUrl']);
    }

    #[Test]
    public function implements_should_queue_interface()
    {
        // Arrange
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'shipping_address_id' => $address->id,
        ]);

        // Act
        $mail = new OrderConfirmationMail($order);

        // Assert
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $mail);
    }

    #[Test]
    public function can_render_email_content_with_order_details()
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $address = Address::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'Ciudad de México',
            'state' => 'CDMX',
            'postal_code' => '01000',
            'country' => 'México'
        ]);

        $product = Product::factory()->create([
            'name' => 'Reloj Elegante',
            'price' => 1500.00
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'order_number' => 'CM-2024-TEST123',
            'shipping_address_id' => $address->id,
            'subtotal_amount' => 1500.00,
            'shipping_cost' => 150.00,
            'total_amount' => 1650.00,
            'status' => Order::STATUS_PROCESSING,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'price_per_unit' => 1500.00,
            'total_price' => 1500.00,
        ]);

        // Load relationships
        $order->load(['orderItems.product', 'shippingAddress', 'user']);

        // Act
        $mail = new OrderConfirmationMail($order);

        // Assert - Test that mail can be built without errors
        $this->assertInstanceOf(OrderConfirmationMail::class, $mail);

        // Test envelope
        $envelope = $mail->envelope();
        $this->assertEquals('Confirmación de Pedido #CM-2024-TEST123', $envelope->subject);

        // Test content
        $content = $mail->content();
        $this->assertEquals('emails.orders.confirmation', $content->markdown);
        $this->assertArrayHasKey('order', $content->with);
        $this->assertEquals($order->order_number, $content->with['order']->order_number);
    }
}
