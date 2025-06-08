<?php

namespace Tests\Unit\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderItemModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function order_item_has_correct_fillable_attributes(): void
    {
        $expectedFillable = [
            'order_id',
            'product_id',
            'product_name',
            'quantity',
            'price_per_unit',
            'total_price',
        ];

        $orderItem = new OrderItem();
        $this->assertEquals($expectedFillable, $orderItem->getFillable());
    }

    #[Test]
    public function order_item_has_correct_casts(): void
    {
        $orderItem = new OrderItem();
        $casts = $orderItem->getCasts();

        $this->assertEquals('integer', $casts['quantity']);
        $this->assertEquals('decimal:2', $casts['price_per_unit']);
        $this->assertEquals('decimal:2', $casts['total_price']);
    }

    #[Test]
    public function order_item_belongs_to_order(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Order::class, $orderItem->order);
        $this->assertEquals($order->id, $orderItem->order->id);
    }

    #[Test]
    public function order_item_belongs_to_product(): void
    {
        $product = Product::factory()->create();
        $orderItem = OrderItem::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $orderItem->product);
        $this->assertEquals($product->id, $orderItem->product->id);
    }

    #[Test]
    public function calculate_total_price_returns_correct_amount(): void
    {
        $orderItem = new OrderItem([
            'quantity' => 3,
            'price_per_unit' => 25.50,
        ]);

        $totalPrice = $orderItem->calculateTotalPrice();

        $this->assertEquals(76.50, $totalPrice);
    }

    #[Test]
    public function total_price_is_automatically_calculated_when_saving(): void
    {
        $order = Order::factory()->create();

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Test Product',
            'quantity' => 2,
            'price_per_unit' => 15.75,
        ]);

        $this->assertEquals(31.50, $orderItem->total_price);
    }

    #[Test]
    public function total_price_is_recalculated_when_updating(): void
    {
        $order = Order::factory()->create();

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Test Product',
            'quantity' => 2,
            'price_per_unit' => 10.00,
        ]);

        $this->assertEquals(20.00, $orderItem->total_price);

        // Update quantity
        $orderItem->update(['quantity' => 5]);

        $this->assertEquals(50.00, $orderItem->fresh()->total_price);
    }

    #[Test]
    public function total_price_is_recalculated_when_price_per_unit_changes(): void
    {
        $order = Order::factory()->create();

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Test Product',
            'quantity' => 3,
            'price_per_unit' => 20.00,
        ]);

        $this->assertEquals(60.00, $orderItem->total_price);

        // Update price per unit
        $orderItem->update(['price_per_unit' => 25.00]);

        $this->assertEquals(75.00, $orderItem->fresh()->total_price);
    }

    #[Test]
    public function can_handle_decimal_quantities_and_prices(): void
    {
        $order = Order::factory()->create();

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Test Product',
            'quantity' => 2,
            'price_per_unit' => 19.99,
        ]);

        $this->assertEquals(39.98, $orderItem->total_price);
    }

    #[Test]
    public function can_handle_zero_quantity(): void
    {
        $order = Order::factory()->create();

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Test Product',
            'quantity' => 0,
            'price_per_unit' => 50.00,
        ]);

        $this->assertEquals(0.00, $orderItem->total_price);
    }

    #[Test]
    public function can_handle_zero_price(): void
    {
        $order = Order::factory()->create();

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Free Product',
            'quantity' => 5,
            'price_per_unit' => 0.00,
        ]);

        $this->assertEquals(0.00, $orderItem->total_price);
    }
}
