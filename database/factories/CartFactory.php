<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cart>
 */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'session_id' => $this->faker->uuid(),
            'total_amount' => $this->faker->randomFloat(2, 0, 1000),
            'total_items' => $this->faker->numberBetween(0, 10),
            'expires_at' => $this->faker->optional(0.7)->dateTimeBetween('now', '+30 days'),
        ];
    }

    /**
     * Indicate that the cart belongs to a user.
     */
    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
            'session_id' => null,
            'expires_at' => null,
        ]);
    }

    /**
     * Indicate that the cart is for a guest session.
     */
    public function forGuest(?string $sessionId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'session_id' => $sessionId ?? $this->faker->uuid(),
            'expires_at' => $this->faker->dateTimeBetween('now', '+7 days'),
        ]);
    }

    /**
     * Indicate that the cart is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Indicate that the cart is empty.
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_amount' => 0,
            'total_items' => 0,
        ]);
    }
}
