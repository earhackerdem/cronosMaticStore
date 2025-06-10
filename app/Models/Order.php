<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING_PAYMENT = 'pendiente_pago';
    public const STATUS_PROCESSING = 'procesando';
    public const STATUS_SHIPPED = 'enviado';
    public const STATUS_DELIVERED = 'entregado';
    public const STATUS_CANCELLED = 'cancelado';

    /**
     * Payment status constants
     */
    public const PAYMENT_STATUS_PENDING = 'pendiente';
    public const PAYMENT_STATUS_PAID = 'pagado';
    public const PAYMENT_STATUS_FAILED = 'fallido';
    public const PAYMENT_STATUS_REFUNDED = 'reembolsado';

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shipping address for the order.
     */
    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    /**
     * Get the billing address for the order.
     */
    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    /**
     * Get the order items for the order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the email address for the order (either from user or guest email).
     */
    public function getEmailAttribute(): string
    {
        return $this->user ? $this->user->email : $this->guest_email;
    }

    /**
     * Get all valid statuses.
     */
    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_PENDING_PAYMENT,
            self::STATUS_PROCESSING,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Get all valid payment statuses.
     */
    public static function getValidPaymentStatuses(): array
    {
        return [
            self::PAYMENT_STATUS_PENDING,
            self::PAYMENT_STATUS_PAID,
            self::PAYMENT_STATUS_FAILED,
            self::PAYMENT_STATUS_REFUNDED,
        ];
    }

    /**
     * Check if the order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING_PAYMENT,
            self::STATUS_PROCESSING,
        ]);
    }

    /**
     * Check if the order is paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID;
    }

    /**
     * Get the status label in Spanish.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_PAYMENT => 'Pendiente de pago',
            self::STATUS_PROCESSING => 'Procesando',
            self::STATUS_SHIPPED => 'Enviado',
            self::STATUS_DELIVERED => 'Entregado',
            self::STATUS_CANCELLED => 'Cancelado',
            default => 'Desconocido',
        };
    }

    /**
     * Get the payment status label in Spanish.
     */
    public function getPaymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_STATUS_PENDING => 'Pendiente',
            self::PAYMENT_STATUS_PAID => 'Pagado',
            self::PAYMENT_STATUS_FAILED => 'Fallido',
            self::PAYMENT_STATUS_REFUNDED => 'Reembolsado',
            default => 'Desconocido',
        };
    }
}
