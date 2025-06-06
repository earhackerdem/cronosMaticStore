<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CartItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_cart()
    {
        $cart = Cart::factory()->create();
        $cartItem = CartItem::factory()->create(['cart_id' => $cart->id]);

        $this->assertInstanceOf(Cart::class, $cartItem->cart);
        $this->assertEquals($cart->id, $cartItem->cart->id);
    }

    #[Test]
    public function it_belongs_to_a_product()
    {
        $product = Product::factory()->create();
        $cartItem = CartItem::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $cartItem->product);
        $this->assertEquals($product->id, $cartItem->product->id);
    }

    #[Test]
    public function it_updates_total_price_correctly()
    {
        $cartItem = CartItem::factory()->create([
            'quantity' => 3,
            'unit_price' => 25.50,
            'total_price' => 0,
        ]);

        $cartItem->updateTotalPrice();

        $this->assertEquals('76.50', $cartItem->total_price);
    }

    #[Test]
    public function it_checks_available_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $cartItemWithStock = CartItem::factory()->create([
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $cartItemWithoutStock = CartItem::factory()->create([
            'product_id' => $product->id,
            'quantity' => 15,
        ]);

        $this->assertTrue($cartItemWithStock->hasAvailableStock());
        $this->assertFalse($cartItemWithoutStock->hasAvailableStock());
    }

    #[Test]
    public function it_returns_subtotal_as_alias_for_total_price()
    {
        $cartItem = CartItem::factory()->create(['total_price' => 150.75]);

        $this->assertEquals('150.75', $cartItem->subtotal);
        $this->assertEquals($cartItem->total_price, $cartItem->subtotal);
    }

    #[Test]
    public function it_has_proper_casts_for_numeric_fields()
    {
        $cartItem = CartItem::factory()->create([
            'quantity' => '5',
            'unit_price' => '25.99',
            'total_price' => '129.95',
        ]);

        $this->assertIsInt($cartItem->quantity);
        $this->assertIsString($cartItem->unit_price); // Decimal se castea a string
        $this->assertIsString($cartItem->total_price); // Decimal se castea a string
    }
}
