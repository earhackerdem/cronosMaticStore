<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();
        $shippingAddress = Address::factory()->create([
            'user_id' => $user->id,
            'type' => Address::TYPE_SHIPPING,
        ]);

        $subtotal = $this->faker->randomFloat(2, 50, 500);
        $shippingCost = $this->faker->randomFloat(2, 0, 50);

        return [
            'user_id' => $user->id,
            'guest_email' => null,
            'order_number' => 'CM-' . date('Y') . '-' . strtoupper($this->faker->bothify('????????')),
            'shipping_address_id' => $shippingAddress->id,
            'billing_address_id' => $shippingAddress->id, // Use same address for billing
            'status' => $this->faker->randomElement(Order::getValidStatuses()),
            'subtotal_amount' => $subtotal,
            'shipping_cost' => $shippingCost,
            'total_amount' => $subtotal + $shippingCost,
            'payment_gateway' => $this->faker->randomElement(['paypal', 'stripe', 'mercadopago', null]),
            'payment_id' => $this->faker->optional()->uuid(),
            'payment_status' => $this->faker->randomElement(Order::getValidPaymentStatuses()),
            'shipping_method_name' => $this->faker->randomElement(['Envío estándar', 'Envío express', 'Recogida en tienda']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the order is for a guest user.
     */
    public function guest(): static
    {
        return $this->state(function (array $attributes) {
            $shippingAddress = Address::factory()->create();

            return [
                'user_id' => null,
                'guest_email' => $this->faker->safeEmail(),
                'shipping_address_id' => $shippingAddress->id,
                'billing_address_id' => $shippingAddress->id,
            ];
        });
    }

    /**
     * Indicate that the order is pending payment.
     */
    public function pendingPayment(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Order::STATUS_PENDING_PAYMENT,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
            ];
        });
    }

    /**
     * Indicate that the order is paid.
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Order::STATUS_PROCESSING,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'payment_id' => $this->faker->uuid(),
                'payment_gateway' => 'paypal',
            ];
        });
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Order::STATUS_CANCELLED,
                'notes' => 'Order cancelled by customer',
            ];
        });
    }

    /**
     * Indicate that the order is delivered.
     */
    public function delivered(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Order::STATUS_DELIVERED,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'payment_id' => $this->faker->uuid(),
                'payment_gateway' => 'paypal',
            ];
        });
    }
}
