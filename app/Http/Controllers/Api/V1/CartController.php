<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AddCartItemRequest;
use App\Http\Requests\Api\V1\UpdateCartItemRequest;
use App\Http\Resources\Api\V1\CartResource;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService
    ) {}

    /**
     * Obtener carrito actual con ítems.
     * GET /api/v1/cart
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $cart = $this->getCartForCurrentUser($request);

            return response()->json([
                'success' => true,
                'message' => 'Carrito obtenido correctamente',
                'data' => new CartResource($cart),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el carrito',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Añadir producto al carrito.
     * POST /api/v1/cart/items
     */
    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        try {
            $cart = $this->getCartForCurrentUser($request);
            $validated = $request->validated();

            $cartItem = $this->cartService->addProductToCart(
                $cart,
                $validated['product_id'],
                $validated['quantity']
            );

            return response()->json([
                'success' => true,
                'message' => 'Producto añadido al carrito correctamente',
                'data' => new CartResource($cart->fresh(['items.product'])),
            ], Response::HTTP_CREATED);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al añadir producto al carrito',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar cantidad de ítem del carrito.
     * PUT /api/v1/cart/items/{cart_item_id}
     */
    public function updateItem(UpdateCartItemRequest $request, int $cartItemId): JsonResponse
    {
        try {
            // Verificar que el ítem pertenece al carrito del usuario actual
            $cartItem = CartItem::findOrFail($cartItemId);
            $userCart = $this->getCartForCurrentUser($request);

            if ($cartItem->cart_id !== $userCart->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para modificar este ítem',
                ], Response::HTTP_FORBIDDEN);
            }

            $validated = $request->validated();
            $updatedItem = $this->cartService->updateCartItemQuantity(
                $cartItemId,
                $validated['quantity']
            );

            return response()->json([
                'success' => true,
                'message' => 'Cantidad actualizada correctamente',
                'data' => new CartResource($userCart->fresh(['items.product'])),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el ítem del carrito',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar ítem del carrito.
     * DELETE /api/v1/cart/items/{cart_item_id}
     */
    public function removeItem(Request $request, int $cartItemId): JsonResponse
    {
        try {
            // Verificar que el ítem pertenece al carrito del usuario actual
            $cartItem = CartItem::findOrFail($cartItemId);
            $userCart = $this->getCartForCurrentUser($request);

            if ($cartItem->cart_id !== $userCart->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar este ítem',
                ], Response::HTTP_FORBIDDEN);
            }

            $this->cartService->removeCartItem($cartItemId);

            return response()->json([
                'success' => true,
                'message' => 'Ítem eliminado del carrito correctamente',
                'data' => new CartResource($userCart->fresh(['items.product'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar ítem del carrito',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Vaciar carrito completamente.
     * DELETE /api/v1/cart
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $cart = $this->getCartForCurrentUser($request);
            $this->cartService->clearCart($cart);

            return response()->json([
                'success' => true,
                'message' => 'Carrito vaciado correctamente',
                'data' => new CartResource($cart->fresh(['items.product'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al vaciar el carrito',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtiene el carrito para el usuario actual (autenticado o invitado).
     */
    private function getCartForCurrentUser(Request $request)
    {
        if (Auth::check()) {
            return $this->cartService->getOrCreateCartForUser(Auth::id());
        }

        // Para invitados, usar session_id del header o cookie
        $sessionId = $request->header('X-Session-ID') ?? $request->cookie('cart_session_id') ?? session()->getId();

        return $this->cartService->getOrCreateCartForGuest($sessionId);
    }
}
