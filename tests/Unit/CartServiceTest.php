<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    private CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = new CartService();
    }

    #[Test]
    public function it_gets_existing_cart_for_user()
    {
        $user = User::factory()->create();
        $existingCart = Cart::factory()->create(['user_id' => $user->id]);

        $cart = $this->cartService->getOrCreateCartForUser($user->id);

        $this->assertEquals($existingCart->id, $cart->id);
    }

    #[Test]
    public function it_creates_new_cart_for_user_if_none_exists()
    {
        $user = User::factory()->create();

        $cart = $this->cartService->getOrCreateCartForUser($user->id);

        $this->assertInstanceOf(Cart::class, $cart);
        $this->assertEquals($user->id, $cart->user_id);
        $this->assertNull($cart->session_id);
        $this->assertNull($cart->expires_at);
    }

    #[Test]
    public function it_throws_exception_for_non_existent_user()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->cartService->getOrCreateCartForUser(999);
    }

    #[Test]
    public function it_gets_existing_cart_for_guest()
    {
        $sessionId = 'test-session-123';
        $existingCart = Cart::factory()->create(['session_id' => $sessionId]);

        $cart = $this->cartService->getOrCreateCartForGuest($sessionId);

        $this->assertEquals($existingCart->id, $cart->id);
    }

    #[Test]
    public function it_creates_new_cart_for_guest_if_none_exists()
    {
        $sessionId = 'test-session-123';

        $cart = $this->cartService->getOrCreateCartForGuest($sessionId);

        $this->assertInstanceOf(Cart::class, $cart);
        $this->assertEquals($sessionId, $cart->session_id);
        $this->assertNull($cart->user_id);
        $this->assertNotNull($cart->expires_at);
    }

    #[Test]
    public function it_adds_product_to_cart()
    {
        $cart = Cart::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 25.99, 'is_active' => true]);

        $cartItem = $this->cartService->addProductToCart($cart, $product->id, 2);

        $this->assertInstanceOf(CartItem::class, $cartItem);
        $this->assertEquals(2, $cartItem->quantity);
        $this->assertEquals('25.99', $cartItem->unit_price);
        $this->assertEquals('51.98', $cartItem->total_price);
    }

    #[Test]
    public function it_updates_existing_item_when_adding_same_product()
    {
        $cart = Cart::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 25.99, 'is_active' => true]);
        $existingItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 25.99,
            'total_price' => 51.98,
        ]);

        $cartItem = $this->cartService->addProductToCart($cart, $product->id, 1);

        $this->assertEquals($existingItem->id, $cartItem->id);
        $this->assertEquals(3, $cartItem->fresh()->quantity);
    }

    #[Test]
    public function it_throws_exception_for_insufficient_stock()
    {
        $cart = Cart::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 5, 'is_active' => true]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock insuficiente');

        $this->cartService->addProductToCart($cart, $product->id, 10);
    }

    #[Test]
    public function it_throws_exception_for_inactive_product()
    {
        $cart = Cart::factory()->create();
        $product = Product::factory()->create(['is_active' => false]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El producto no está disponible');

        $this->cartService->addProductToCart($cart, $product->id, 1);
    }

    #[Test]
    public function it_throws_exception_for_zero_or_negative_quantity()
    {
        $cart = Cart::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10, 'is_active' => true]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La cantidad debe ser mayor a 0');

        $this->cartService->addProductToCart($cart, $product->id, 0);
    }

    #[Test]
    public function it_updates_cart_item_quantity()
    {
        $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 25.99, 'is_active' => true]);
        $cartItem = CartItem::factory()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 25.99,
        ]);

        $updatedItem = $this->cartService->updateCartItemQuantity($cartItem->id, 4);

        $this->assertEquals(4, $updatedItem->quantity);
        $this->assertEquals('103.96', $updatedItem->total_price);
    }

    #[Test]
    public function it_removes_cart_item()
    {
        $cartItem = CartItem::factory()->create();
        $cartItemId = $cartItem->id;

        $result = $this->cartService->removeCartItem($cartItemId);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('cart_items', ['id' => $cartItemId]);
    }

    #[Test]
    public function it_updates_cart_totals()
    {
        $cart = Cart::factory()->create();
        CartItem::factory()->create(['cart_id' => $cart->id, 'quantity' => 2, 'total_price' => 50.00]);
        CartItem::factory()->create(['cart_id' => $cart->id, 'quantity' => 3, 'total_price' => 75.00]);

        $updatedCart = $this->cartService->updateCartTotals($cart);

        $this->assertEquals(5, $updatedCart->total_items);
        $this->assertEquals('125', $updatedCart->total_amount);
    }

    #[Test]
    public function it_clears_cart()
    {
        $cart = Cart::factory()->create();
        CartItem::factory()->count(3)->create(['cart_id' => $cart->id]);

        $result = $this->cartService->clearCart($cart);

        $this->assertTrue($result);
        $this->assertEquals(0, $cart->fresh()->items->count());
        $this->assertEquals(0, $cart->fresh()->total_items);
        $this->assertEquals('0', $cart->fresh()->total_amount);
    }

    #[Test]
    public function it_merges_guest_cart_to_user_cart()
    {
        $user = User::factory()->create();
        $sessionId = 'test-session-123';

        // Carrito de invitado con productos
        $guestCart = Cart::factory()->create(['session_id' => $sessionId]);
        $product1 = Product::factory()->create(['stock_quantity' => 10, 'price' => 25.99, 'is_active' => true]);
        $product2 = Product::factory()->create(['stock_quantity' => 5, 'price' => 15.50, 'is_active' => true]);

        CartItem::factory()->create([
            'cart_id' => $guestCart->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'unit_price' => 25.99,
            'total_price' => 51.98,
        ]);

        CartItem::factory()->create([
            'cart_id' => $guestCart->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'unit_price' => 15.50,
            'total_price' => 15.50,
        ]);

        $userCart = $this->cartService->mergeGuestCartToUser($sessionId, $user->id);

        $this->assertInstanceOf(Cart::class, $userCart);
        $this->assertEquals($user->id, $userCart->user_id);
        $this->assertCount(2, $userCart->items);
        $this->assertDatabaseMissing('carts', ['id' => $guestCart->id]);
    }

    #[Test]
    public function it_validates_cart_stock()
    {
        $cart = Cart::factory()->create();
        $product1 = Product::factory()->create(['stock_quantity' => 5, 'is_active' => true]);
        $product2 = Product::factory()->create(['stock_quantity' => 2, 'is_active' => true]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product1->id,
            'quantity' => 3, // OK
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product2->id,
            'quantity' => 5, // Excede stock
        ]);

        $errors = $this->cartService->validateCartStock($cart);

        $this->assertCount(1, $errors);
        $this->assertEquals($product2->id, $errors[0]['item_id']);
    }
}
