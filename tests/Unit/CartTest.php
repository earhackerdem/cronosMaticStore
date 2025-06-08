<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $cart->user);
        $this->assertEquals($user->id, $cart->user->id);
    }

    #[Test]
    public function it_can_have_many_items()
    {
        $cart = Cart::factory()->create();
        $cartItems = CartItem::factory()->count(3)->create(['cart_id' => $cart->id]);

        $this->assertCount(3, $cart->items);
        $this->assertInstanceOf(CartItem::class, $cart->items->first());
    }

    #[Test]
    public function it_can_scope_for_user()
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        Cart::factory()->create(); // Otro carrito

        $userCarts = Cart::forUser($user->id)->get();

        $this->assertCount(1, $userCarts);
        $this->assertEquals($cart->id, $userCarts->first()->id);
    }

    #[Test]
    public function it_can_scope_for_session()
    {
        $sessionId = 'test-session-123';
        $cart = Cart::factory()->create(['session_id' => $sessionId]);
        Cart::factory()->create(['session_id' => 'other-session']);

        $sessionCarts = Cart::forSession($sessionId)->get();

        $this->assertCount(1, $sessionCarts);
        $this->assertEquals($cart->id, $sessionCarts->first()->id);
    }

    #[Test]
    public function it_can_scope_not_expired()
    {
        $expiredCart = Cart::factory()->create(['expires_at' => now()->subDay()]);
        $validCart = Cart::factory()->create(['expires_at' => now()->addDay()]);
        $noExpirationCart = Cart::factory()->create(['expires_at' => null]);

        $validCarts = Cart::notExpired()->get();

        $this->assertCount(2, $validCarts);
        $this->assertFalse($validCarts->contains($expiredCart));
        $this->assertTrue($validCarts->contains($validCart));
        $this->assertTrue($validCarts->contains($noExpirationCart));
    }

    #[Test]
    public function it_can_check_if_expired()
    {
        $expiredCart = Cart::factory()->create(['expires_at' => now()->subDay()]);
        $validCart = Cart::factory()->create(['expires_at' => now()->addDay()]);
        $noExpirationCart = Cart::factory()->create(['expires_at' => null]);

        $this->assertTrue($expiredCart->isExpired());
        $this->assertFalse($validCart->isExpired());
        $this->assertFalse($noExpirationCart->isExpired());
    }

    #[Test]
    public function it_calculates_total_items_from_cart_items()
    {
        $cart = Cart::factory()->create();
        CartItem::factory()->create(['cart_id' => $cart->id, 'quantity' => 2]);
        CartItem::factory()->create(['cart_id' => $cart->id, 'quantity' => 3]);

        $cart->load('items'); // Cargar la relación para el accessor

        $this->assertEquals(5, $cart->getTotalItemsAttribute());
    }

    #[Test]
    public function it_calculates_total_amount_from_cart_items()
    {
        $cart = Cart::factory()->create();
        CartItem::factory()->create(['cart_id' => $cart->id, 'total_price' => 100.50]);
        CartItem::factory()->create(['cart_id' => $cart->id, 'total_price' => 200.25]);

        $cart->load('items'); // Cargar la relación para el accessor

        $this->assertEquals('300.75', $cart->getTotalAmountAttribute());
    }
}
