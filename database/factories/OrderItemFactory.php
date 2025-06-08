<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = Product::factory()->create();
        $quantity = $this->faker->numberBetween(1, 5);
        $pricePerUnit = $this->faker->randomFloat(2, 10, 500);

        return [
            'order_id' => Order::factory(),
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => $quantity,
            'price_per_unit' => $pricePerUnit,
            'total_price' => $quantity * $pricePerUnit,
        ];
    }

    /**
     * Indicate that the order item is for a specific product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(function (array $attributes) use ($product) {
            $quantity = $attributes['quantity'] ?? $this->faker->numberBetween(1, 5);
            $pricePerUnit = $product->price;

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price_per_unit' => $pricePerUnit,
                'total_price' => $quantity * $pricePerUnit,
            ];
        });
    }

    /**
     * Indicate that the order item is for a specific order.
     */
    public function forOrder(Order $order): static
    {
        return $this->state(function (array $attributes) use ($order) {
            return [
                'order_id' => $order->id,
            ];
        });
    }

    /**
     * Set a specific quantity for the order item.
     */
    public function quantity(int $quantity): static
    {
        return $this->state(function (array $attributes) use ($quantity) {
            $pricePerUnit = $attributes['price_per_unit'] ?? $this->faker->randomFloat(2, 10, 500);

            return [
                'quantity' => $quantity,
                'total_price' => $quantity * $pricePerUnit,
            ];
        });
    }

    /**
     * Set a specific price per unit for the order item.
     */
    public function pricePerUnit(float $price): static
    {
        return $this->state(function (array $attributes) use ($price) {
            $quantity = $attributes['quantity'] ?? $this->faker->numberBetween(1, 5);

            return [
                'price_per_unit' => $price,
                'total_price' => $quantity * $price,
            ];
        });
    }
}
