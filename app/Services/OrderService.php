<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Create a new order from a cart.
     */
    public function createOrderFromCart(
        Cart $cart,
        int $shippingAddressId,
        ?int $billingAddressId = null,
        ?string $guestEmail = null,
        array $orderData = []
    ): Order {
        return DB::transaction(function () use ($cart, $shippingAddressId, $billingAddressId, $guestEmail, $orderData) {
                        // Load cart items if not already loaded
            $cart->load('items.product');

            // Validate cart has items
            if ($cart->items->isEmpty()) {
                throw new \InvalidArgumentException('Cannot create order from empty cart');
            }

            // Validate stock availability
            $this->validateCartStock($cart);

            // Validate addresses
            $shippingAddress = Address::findOrFail($shippingAddressId);
            $billingAddress = $billingAddressId ? Address::findOrFail($billingAddressId) : null;

            // Calculate totals
            $subtotal = $cart->items->sum(function ($item) {
                return $item->quantity * $item->product->price;
            });

            $shippingCost = $orderData['shipping_cost'] ?? 0.00;
            $totalAmount = $subtotal + $shippingCost;

            // Create order
            $order = Order::create([
                'user_id' => $cart->user_id,
                'guest_email' => $guestEmail,
                'order_number' => $this->generateOrderNumber(),
                'shipping_address_id' => $shippingAddressId,
                'billing_address_id' => $billingAddressId,
                'status' => Order::STATUS_PENDING_PAYMENT,
                'subtotal_amount' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
                'payment_gateway' => $orderData['payment_gateway'] ?? null,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                'shipping_method_name' => $orderData['shipping_method_name'] ?? null,
                'notes' => $orderData['notes'] ?? null,
            ]);

            // Create order items
            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name,
                    'quantity' => $cartItem->quantity,
                    'price_per_unit' => $cartItem->product->price,
                    'total_price' => $cartItem->quantity * $cartItem->product->price,
                ]);

                // Reduce stock
                $cartItem->product->decrement('stock_quantity', $cartItem->quantity);
            }

            return $order->load(['orderItems', 'shippingAddress', 'billingAddress', 'user']);
        });
    }

    /**
     * Get orders for a user with pagination.
     */
    public function getUserOrders(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Order::with(['orderItems.product', 'shippingAddress', 'billingAddress'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get a specific order by ID and user ID.
     */
    public function getUserOrder(int $orderId, int $userId): Order
    {
        return Order::with(['orderItems.product', 'shippingAddress', 'billingAddress', 'user'])
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    /**
     * Get a specific order by order number.
     */
    public function getOrderByNumber(string $orderNumber): Order
    {
        return Order::with(['orderItems.product', 'shippingAddress', 'billingAddress', 'user'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(int $orderId, string $status): Order
    {
        if (!in_array($status, Order::getValidStatuses())) {
            throw new \InvalidArgumentException("Invalid order status: {$status}");
        }

        $order = Order::findOrFail($orderId);
        $order->update(['status' => $status]);

        return $order->fresh();
    }

    /**
     * Update payment status.
     */
    public function updatePaymentStatus(
        int $orderId,
        string $paymentStatus,
        ?string $paymentId = null,
        ?string $paymentGateway = null
    ): Order {
        if (!in_array($paymentStatus, Order::getValidPaymentStatuses())) {
            throw new \InvalidArgumentException("Invalid payment status: {$paymentStatus}");
        }

        $order = Order::findOrFail($orderId);

        $updateData = ['payment_status' => $paymentStatus];

        if ($paymentId) {
            $updateData['payment_id'] = $paymentId;
        }

        if ($paymentGateway) {
            $updateData['payment_gateway'] = $paymentGateway;
        }

        // If payment is successful, update order status
        if ($paymentStatus === Order::PAYMENT_STATUS_PAID && $order->status === Order::STATUS_PENDING_PAYMENT) {
            $updateData['status'] = Order::STATUS_PROCESSING;
        }

        $order->update($updateData);

        return $order->fresh();
    }

    /**
     * Cancel an order.
     */
    public function cancelOrder(int $orderId, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($orderId, $reason) {
            $order = Order::with('orderItems.product')->findOrFail($orderId);

            if (!$order->canBeCancelled()) {
                throw new \InvalidArgumentException('Order cannot be cancelled in its current state');
            }

            // Restore stock
            foreach ($order->orderItems as $orderItem) {
                if ($orderItem->product) {
                    $orderItem->product->increment('stock_quantity', $orderItem->quantity);
                }
            }

            // Update order status
            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'notes' => $order->notes ? $order->notes . "\n\nCancellation reason: " . $reason : "Cancellation reason: " . $reason,
            ]);

            return $order->fresh();
        });
    }

    /**
     * Get order statistics for a user.
     */
    public function getUserOrderStats(int $userId): array
    {
        $orders = Order::where('user_id', $userId)->get();

        return [
            'total_orders' => $orders->count(),
            'total_spent' => $orders->where('payment_status', Order::PAYMENT_STATUS_PAID)->sum('total_amount'),
            'pending_orders' => $orders->where('status', Order::STATUS_PENDING_PAYMENT)->count(),
            'processing_orders' => $orders->where('status', Order::STATUS_PROCESSING)->count(),
            'shipped_orders' => $orders->where('status', Order::STATUS_SHIPPED)->count(),
            'delivered_orders' => $orders->where('status', Order::STATUS_DELIVERED)->count(),
            'cancelled_orders' => $orders->where('status', Order::STATUS_CANCELLED)->count(),
        ];
    }

    /**
     * Search orders by various criteria.
     */
    public function searchOrders(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::with(['orderItems.product', 'shippingAddress', 'billingAddress', 'user']);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['order_number'])) {
            $query->where('order_number', 'like', '%' . $filters['order_number'] . '%');
        }

        if (isset($filters['guest_email'])) {
            $query->where('guest_email', 'like', '%' . $filters['guest_email'] . '%');
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Generate a unique order number.
     */
    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'CM-' . date('Y') . '-' . strtoupper(Str::random(8));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Validate that all cart items have sufficient stock.
     */
    private function validateCartStock(Cart $cart): void
    {
        foreach ($cart->items as $cartItem) {
            if ($cartItem->product->stock_quantity < $cartItem->quantity) {
                throw new \InvalidArgumentException(
                    "Insufficient stock for product: {$cartItem->product->name}. " .
                    "Available: {$cartItem->product->stock_quantity}, Requested: {$cartItem->quantity}"
                );
            }
        }
    }

    /**
     * Calculate order summary from cart.
     */
    public function calculateOrderSummary(Cart $cart, float $shippingCost = 0.00): array
    {
                // Load cart items if not already loaded
        $cart->load('items.product');

        $subtotal = $cart->items->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        $totalAmount = $subtotal + $shippingCost;

        return [
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'total_amount' => $totalAmount,
            'items_count' => $cart->items->sum('quantity'),
        ];
    }
}
