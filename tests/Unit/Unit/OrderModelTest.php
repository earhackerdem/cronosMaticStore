<?php

namespace Tests\Unit\Unit;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderModelTest extends TestCase
{
    use RefreshDatabase;



    #[Test]
    public function order_has_correct_fillable_attributes(): void
    {
        $expectedFillable = [
            'user_id',
            'guest_email',
            'order_number',
            'shipping_address_id',
            'billing_address_id',
            'status',
            'subtotal_amount',
            'shipping_cost',
            'total_amount',
            'payment_gateway',
            'payment_id',
            'payment_status',
            'shipping_method_name',
            'notes',
        ];

        $order = new Order();
        $this->assertEquals($expectedFillable, $order->getFillable());
    }

    #[Test]
    public function order_has_correct_casts(): void
    {
        $order = new Order();
        $casts = $order->getCasts();

        $this->assertEquals('decimal:2', $casts['subtotal_amount']);
        $this->assertEquals('decimal:2', $casts['shipping_cost']);
        $this->assertEquals('decimal:2', $casts['total_amount']);
    }

    #[Test]
    public function order_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
    }

    #[Test]
    public function order_belongs_to_shipping_address(): void
    {
        $user = User::factory()->create();
        $shippingAddress = Address::factory()->create([
            'user_id' => $user->id,
            'type' => Address::TYPE_SHIPPING,
        ]);
        $order = Order::factory()->create(['shipping_address_id' => $shippingAddress->id]);

        $this->assertInstanceOf(Address::class, $order->shippingAddress);
        $this->assertEquals($shippingAddress->id, $order->shippingAddress->id);
    }

    #[Test]
    public function order_belongs_to_billing_address(): void
    {
        $user = User::factory()->create();
        $billingAddress = Address::factory()->create([
            'user_id' => $user->id,
            'type' => Address::TYPE_BILLING,
        ]);
        $order = Order::factory()->create(['billing_address_id' => $billingAddress->id]);

        $this->assertInstanceOf(Address::class, $order->billingAddress);
        $this->assertEquals($billingAddress->id, $order->billingAddress->id);
    }

    #[Test]
    public function order_has_many_order_items(): void
    {
        $order = Order::factory()->create();
        $orderItems = OrderItem::factory()->count(3)->create(['order_id' => $order->id]);

        $this->assertCount(3, $order->orderItems);
        $this->assertInstanceOf(OrderItem::class, $order->orderItems->first());
    }

    #[Test]
    public function order_email_attribute_returns_user_email_when_user_exists(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->assertEquals('user@example.com', $order->email);
    }

    #[Test]
    public function order_email_attribute_returns_guest_email_when_no_user(): void
    {
        $order = Order::factory()->create([
            'user_id' => null,
            'guest_email' => 'guest@example.com',
        ]);

        $this->assertEquals('guest@example.com', $order->email);
    }

    #[Test]
    public function get_valid_statuses_returns_correct_array(): void
    {
        $expectedStatuses = [
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
        ];

        $this->assertEquals($expectedStatuses, Order::getValidStatuses());
    }

    #[Test]
    public function get_valid_payment_statuses_returns_correct_array(): void
    {
        $expectedPaymentStatuses = [
            Order::PAYMENT_STATUS_PENDING,
            Order::PAYMENT_STATUS_PAID,
            Order::PAYMENT_STATUS_FAILED,
            Order::PAYMENT_STATUS_REFUNDED,
        ];

        $this->assertEquals($expectedPaymentStatuses, Order::getValidPaymentStatuses());
    }

    #[Test]
    public function can_be_cancelled_returns_true_for_pending_payment(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING_PAYMENT]);

        $this->assertTrue($order->canBeCancelled());
    }

    #[Test]
    public function can_be_cancelled_returns_true_for_processing(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_PROCESSING]);

        $this->assertTrue($order->canBeCancelled());
    }

    #[Test]
    public function can_be_cancelled_returns_false_for_shipped(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_SHIPPED]);

        $this->assertFalse($order->canBeCancelled());
    }

    #[Test]
    public function can_be_cancelled_returns_false_for_delivered(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_DELIVERED]);

        $this->assertFalse($order->canBeCancelled());
    }

    #[Test]
    public function can_be_cancelled_returns_false_for_cancelled(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_CANCELLED]);

        $this->assertFalse($order->canBeCancelled());
    }

    #[Test]
    public function is_paid_returns_true_when_payment_status_is_paid(): void
    {
        $order = Order::factory()->create(['payment_status' => Order::PAYMENT_STATUS_PAID]);

        $this->assertTrue($order->isPaid());
    }

    #[Test]
    public function is_paid_returns_false_when_payment_status_is_pending(): void
    {
        $order = Order::factory()->create(['payment_status' => Order::PAYMENT_STATUS_PENDING]);

        $this->assertFalse($order->isPaid());
    }

    #[Test]
    public function is_paid_returns_false_when_payment_status_is_failed(): void
    {
        $order = Order::factory()->create(['payment_status' => Order::PAYMENT_STATUS_FAILED]);

        $this->assertFalse($order->isPaid());
    }

    #[Test]
    public function is_paid_returns_false_when_payment_status_is_refunded(): void
    {
        $order = Order::factory()->create(['payment_status' => Order::PAYMENT_STATUS_REFUNDED]);

        $this->assertFalse($order->isPaid());
    }

    #[Test]
    public function order_constants_have_correct_values(): void
    {
        $this->assertEquals('pendiente_pago', Order::STATUS_PENDING_PAYMENT);
        $this->assertEquals('procesando', Order::STATUS_PROCESSING);
        $this->assertEquals('enviado', Order::STATUS_SHIPPED);
        $this->assertEquals('entregado', Order::STATUS_DELIVERED);
        $this->assertEquals('cancelado', Order::STATUS_CANCELLED);

        $this->assertEquals('pendiente', Order::PAYMENT_STATUS_PENDING);
        $this->assertEquals('pagado', Order::PAYMENT_STATUS_PAID);
        $this->assertEquals('fallido', Order::PAYMENT_STATUS_FAILED);
        $this->assertEquals('reembolsado', Order::PAYMENT_STATUS_REFUNDED);
    }
}
