<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CartService
{
    /**
     * Obtiene o crea un carrito para un usuario autenticado.
     */
    public function getOrCreateCartForUser(int $userId): Cart
    {
        $user = User::findOrFail($userId);

        $cart = Cart::forUser($userId)->notExpired()->first();

        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $userId,
                'session_id' => null,
                'total_amount' => 0,
                'total_items' => 0,
                'expires_at' => null,
            ]);
        }

        return $cart;
    }

    /**
     * Obtiene o crea un carrito para un invitado basado en session_id.
     */
    public function getOrCreateCartForGuest(string $sessionId): Cart
    {
        $cart = Cart::forSession($sessionId)->notExpired()->first();

        if (!$cart) {
            $cart = Cart::create([
                'user_id' => null,
                'session_id' => $sessionId,
                'total_amount' => 0,
                'total_items' => 0,
                'expires_at' => now()->addDays(7), // 7 días de expiración para invitados
            ]);
        }

        return $cart;
    }

    /**
     * Añade un producto al carrito verificando el stock disponible.
     */
    public function addProductToCart(Cart $cart, int $productId, int $quantity = 1): CartItem
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a 0');
        }

        $product = Product::findOrFail($productId);

        if (!$product->is_active) {
            throw new InvalidArgumentException('El producto no está disponible');
        }

        if ($product->stock_quantity < $quantity) {
            throw new InvalidArgumentException('Stock insuficiente. Disponible: ' . $product->stock_quantity);
        }

        return DB::transaction(function () use ($cart, $product, $quantity) {
            $existingItem = CartItem::where('cart_id', $cart->id)
                                  ->where('product_id', $product->id)
                                  ->first();

            if ($existingItem) {
                $newQuantity = $existingItem->quantity + $quantity;

                if ($product->stock_quantity < $newQuantity) {
                    throw new InvalidArgumentException('Stock insuficiente para la cantidad total. Disponible: ' . $product->stock_quantity);
                }

                $existingItem->quantity = $newQuantity;
                $existingItem->total_price = $newQuantity * $existingItem->unit_price;
                $existingItem->save();

                $cartItem = $existingItem;
            } else {
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'total_price' => $quantity * $product->price,
                ]);
            }

            $this->updateCartTotals($cart);

            return $cartItem;
        });
    }

    /**
     * Actualiza la cantidad de un ítem del carrito.
     */
    public function updateCartItemQuantity(int $cartItemId, int $quantity): CartItem
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a 0');
        }

        return DB::transaction(function () use ($cartItemId, $quantity) {
            $cartItem = CartItem::findOrFail($cartItemId);
            $product = $cartItem->product;

            if ($product->stock_quantity < $quantity) {
                throw new InvalidArgumentException('Stock insuficiente. Disponible: ' . $product->stock_quantity);
            }

            $cartItem->quantity = $quantity;
            $cartItem->total_price = $quantity * $cartItem->unit_price;
            $cartItem->save();

            $this->updateCartTotals($cartItem->cart);

            return $cartItem;
        });
    }

    /**
     * Elimina un ítem del carrito.
     */
    public function removeCartItem(int $cartItemId): bool
    {
        return DB::transaction(function () use ($cartItemId) {
            $cartItem = CartItem::findOrFail($cartItemId);
            $cart = $cartItem->cart;

            $cartItem->delete();

            $this->updateCartTotals($cart);

            return true;
        });
    }

    /**
     * Calcula y actualiza los totales del carrito.
     */
    public function updateCartTotals(Cart $cart): Cart
    {
        $cart->load('items');

        $totalItems = $cart->items->sum('quantity');
        $totalAmount = $cart->items->sum('total_price');

        $cart->update([
            'total_items' => $totalItems,
            'total_amount' => $totalAmount,
        ]);

        return $cart->fresh();
    }

    /**
     * Fusiona el carrito de invitado al carrito del usuario al loguearse.
     */
    public function mergeGuestCartToUser(string $sessionId, int $userId): Cart
    {
        return DB::transaction(function () use ($sessionId, $userId) {
            $userCart = $this->getOrCreateCartForUser($userId);
            $guestCart = Cart::forSession($sessionId)->notExpired()->with('items')->first();

            // Si no hay carrito de invitado, retornar carrito de usuario
            if (!$guestCart) {
                return $userCart;
            }

            // Si el carrito de invitado está vacío, eliminarlo y retornar carrito de usuario
            if ($guestCart->items->isEmpty()) {
                $guestCart->delete();
                return $userCart;
            }

            foreach ($guestCart->items as $guestItem) {
                $existingItem = CartItem::where('cart_id', $userCart->id)
                                      ->where('product_id', $guestItem->product_id)
                                      ->first();

                if ($existingItem) {
                    $newQuantity = $existingItem->quantity + $guestItem->quantity;

                    // Verificar stock antes de fusionar
                    if ($guestItem->product->stock_quantity >= $newQuantity) {
                        $existingItem->quantity = $newQuantity;
                        $existingItem->total_price = $newQuantity * $existingItem->unit_price;
                        $existingItem->save();
                    }
                } else {
                    // Verificar stock antes de crear nuevo ítem
                    if ($guestItem->product->stock_quantity >= $guestItem->quantity) {
                        CartItem::create([
                            'cart_id' => $userCart->id,
                            'product_id' => $guestItem->product_id,
                            'quantity' => $guestItem->quantity,
                            'unit_price' => $guestItem->unit_price,
                            'total_price' => $guestItem->total_price,
                        ]);
                    }
                }
            }

            // Eliminar carrito de invitado después de fusionar
            $guestCart->delete();

            $this->updateCartTotals($userCart);

            return $userCart;
        });
    }

    /**
     * Vacía completamente el carrito.
     */
    public function clearCart(Cart $cart): bool
    {
        return DB::transaction(function () use ($cart) {
            $cart->items()->delete();

            $cart->update([
                'total_items' => 0,
                'total_amount' => 0,
            ]);

            return true;
        });
    }

    /**
     * Obtiene el carrito con sus ítems y productos relacionados.
     */
    public function getCartWithItems(Cart $cart): Cart
    {
        return $cart->load(['items.product']);
    }

    /**
     * Verifica si todos los ítems del carrito tienen stock disponible.
     */
    public function validateCartStock(Cart $cart): array
    {
        $cart->load(['items.product']);
        $errors = [];

        foreach ($cart->items as $item) {
            if (!$item->hasAvailableStock()) {
                $errors[] = [
                    'item_id' => $item->id,
                    'product_name' => $item->product->name,
                    'requested_quantity' => $item->quantity,
                    'available_stock' => $item->product->stock_quantity,
                ];
            }
        }

        return $errors;
    }
}
