<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\Product>
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(),
            'sku' => $this->faker->unique()->ean8(), // Genera un SKU único
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'brand' => $this->faker->company(),
            'movement_type' => $this->faker->randomElement(['Automatic', 'Manual', 'Quartz']), // Ejemplo de tipos de movimiento
            'image_path' => null, // Se manejará por separado o se dejará nulo
            'is_active' => $this->faker->boolean(90), // 90% de probabilidad de ser activo
        ];
    }
}
