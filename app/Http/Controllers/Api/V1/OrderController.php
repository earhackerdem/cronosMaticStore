<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PayPalPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class OrderController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private OrderService $orderService,
        private PayPalPaymentService $paymentService
    ) {}

    /**
     * Create a new order from cart.
     *
     * @param StoreOrderRequest $request
     * @return JsonResponse
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                // Get current cart for user or guest
                $cart = $this->getCurrentCart($request);

                if (!$cart || $cart->items->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede crear un pedido con un carrito vacío.',
                        'errors' => ['cart' => ['El carrito está vacío.']]
                    ], 422);
                }

                // Validate cart stock
                $stockErrors = $this->cartService->validateCartStock($cart);
                if (!empty($stockErrors)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Algunos productos no tienen stock suficiente.',
                        'errors' => ['stock' => $stockErrors]
                    ], 422);
                }

                // Create order
                $user = Auth::guard('sanctum')->user();

                if ($user) {
                    // Authenticated user flow
                    $order = $this->orderService->createOrderFromCart(
                        cart: $cart,
                        shippingAddressId: $request->validated('shipping_address_id'),
                        billingAddressId: $request->validated('billing_address_id'),
                        guestEmail: null,
                        orderData: [
                            'shipping_cost' => $request->validated('shipping_cost', 0.00),
                            'shipping_method_name' => $request->validated('shipping_method_name'),
                            'payment_gateway' => 'paypal',
                            'notes' => $request->validated('notes'),
                        ]
                    );
                } else {
                    // Guest user flow - create temporary addresses
                    $shippingAddress = $this->createTemporaryAddress($request->validated('shipping_address'), 'shipping');
                    $billingAddress = $this->createTemporaryAddress($request->validated('billing_address'), 'billing');

                    $order = $this->orderService->createOrderFromCart(
                        cart: $cart,
                        shippingAddressId: $shippingAddress->id,
                        billingAddressId: $billingAddress->id,
                        guestEmail: $request->validated('guest_email'),
                        orderData: [
                            'shipping_cost' => $request->validated('shipping_cost', 0.00),
                            'shipping_method_name' => $request->validated('shipping_method_name'),
                            'payment_gateway' => 'paypal',
                            'notes' => $request->validated('notes'),
                        ]
                    );
                }

                // Process payment
                $paymentResult = $this->processPayment($order, $request->validated('payment_method'));

                if (!$paymentResult['success']) {
                    // If payment fails, cancel the order and restore stock
                    $this->orderService->cancelOrder($order->id, 'Pago fallido: ' . ($paymentResult['error'] ?? 'Error desconocido'));

                    return response()->json([
                        'success' => false,
                        'message' => 'Error al procesar el pago.',
                        'errors' => ['payment' => [$paymentResult['error'] ?? 'Error de pago desconocido']]
                    ], 422);
                }

                // Update order with payment information
                $this->orderService->updatePaymentStatus(
                    orderId: $order->id,
                    paymentStatus: \App\Models\Order::PAYMENT_STATUS_PAID,
                    paymentId: $paymentResult['payment_id'] ?? null,
                    paymentGateway: 'paypal'
                );

                // Clear cart after successful order
                Log::info('Clearing cart after successful order', [
                    'cart_id' => $cart->id,
                    'cart_items_before' => $cart->items->count(),
                    'order_id' => $order->id
                ]);

                $clearResult = $this->cartService->clearCart($cart);

                Log::info('Cart cleared result', [
                    'cart_id' => $cart->id,
                    'clear_result' => $clearResult,
                    'cart_items_after' => $cart->fresh()->items->count(),
                    'order_id' => $order->id
                ]);

                // Load relationships for response
                $order = $order->load(['orderItems.product', 'shippingAddress', 'billingAddress', 'user']);

                Log::info('Order created successfully', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->user_id,
                    'guest_email' => $order->guest_email,
                    'total_amount' => $order->total_amount
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Pedido creado exitosamente.',
                    'data' => [
                        'order' => new OrderResource($order),
                        'payment' => [
                            'status' => 'success',
                            'payment_id' => $paymentResult['payment_id'] ?? null,
                            'gateway' => 'paypal'
                        ]
                    ]
                ], 201);
            });

        } catch (Exception $e) {
            Log::error('Error creating order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::guard('sanctum')->id() ?? Auth::guard('web')->id(),
                'request_data' => $request->validated()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al crear el pedido.',
                'errors' => ['server' => ['Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo.']]
            ], 500);
        }
    }

        /**
     * Get current cart for the request (user or guest).
     */
    private function getCurrentCart(Request $request)
    {
        // Check both sanctum (API) and web (session) authentication
        $user = Auth::guard('sanctum')->user() ?? Auth::guard('web')->user();

        if ($user) {
            return $this->cartService->getOrCreateCartForUser($user->id);
        }

                // For guest users, try to get session ID from multiple sources
        $sessionId = null;

        // First priority: Use the session ID sent from frontend (X-Session-ID header)
        $sessionId = $request->header('X-Session-ID');

        // Second priority: Try to get session ID safely from Laravel session
        if (!$sessionId) {
            try {
                if ($request->hasSession()) {
                    $sessionId = $request->session()->getId();
                }
            } catch (Exception $e) {
                // Sessions might not be available in API routes
                Log::info('Session not available for API request, using fallback', ['error' => $e->getMessage()]);
            }
        }

        // Last resort: generate a new session ID
        if (!$sessionId) {
            $sessionId = 'guest_' . time() . '_' . Str::random(8);
            Log::info('Generated new session ID for guest user', ['session_id' => $sessionId]);
        } else {
            Log::info('Using existing session ID for guest user', ['session_id' => $sessionId]);
        }

        return $this->cartService->getOrCreateCartForGuest($sessionId);
    }

    /**
     * Process payment for the order.
     */
    private function processPayment($order, string $paymentMethod): array
    {
        try {
            switch ($paymentMethod) {
                case 'paypal':
                    // Check if we should simulate payments
                    if (config('app.env') === 'testing' || config('services.paypal.simulate_payments', false)) {
                        Log::info('Using PayPal payment simulation', [
                            'order_id' => $order->id,
                            'simulate_payments' => config('services.paypal.simulate_payments'),
                            'app_env' => config('app.env')
                        ]);
                        return $this->paymentService->simulateSuccessfulPayment($order);
                    }

                    // Real PayPal payment processing
                    Log::info('Using real PayPal payment processing', [
                        'order_id' => $order->id,
                        'simulate_payments' => config('services.paypal.simulate_payments'),
                        'app_env' => config('app.env')
                    ]);
                    return $this->paymentService->processPayment($order);

                default:
                    return [
                        'success' => false,
                        'error' => 'Método de pago no soportado: ' . $paymentMethod
                    ];
            }
        } catch (Exception $e) {
            Log::error('Payment processing error', [
                'order_id' => $order->id,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error al procesar el pago: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create a temporary address for guest users.
     */
    private function createTemporaryAddress(array $addressData, string $type): \App\Models\Address
    {
        return \App\Models\Address::create([
            'user_id' => null, // Guest address
            'type' => $type,
            'first_name' => $addressData['first_name'],
            'last_name' => $addressData['last_name'],
            'company' => $addressData['company'] ?? '',
            'address_line_1' => $addressData['address_line_1'],
            'address_line_2' => $addressData['address_line_2'] ?? '',
            'city' => $addressData['city'],
            'state' => $addressData['state'],
            'postal_code' => $addressData['postal_code'],
            'country' => $addressData['country'],
            'phone' => $addressData['phone'] ?? '',
            'is_default' => false,
        ]);
    }
}
