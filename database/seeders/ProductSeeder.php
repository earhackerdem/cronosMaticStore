<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asegurarse de que existan categorías antes de crear productos
        if (Category::count() == 0) {
            $this->call(CategorySeeder::class);
        }

        Product::factory(50)->create(); // Crear 50 productos de ejemplo

        // Crear productos específicos para testing E2E
        Product::factory()->create([
            'name' => 'Reloj Automático Test',
            'slug' => 'reloj-automatico-test',
            'description' => 'Un reloj automático de prueba',
            'sku' => 'TEST-001',
            'price' => 1500,
            'stock_quantity' => 10,
            'brand' => 'Test Brand',
            'movement_type' => 'Automático',
            'category_id' => Category::first()->id,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'name' => 'Reloj Cuarzo Test',
            'slug' => 'reloj-cuarzo-test',
            'description' => 'Un reloj de cuarzo de prueba',
            'sku' => 'TEST-002',
            'price' => 1500,
            'stock_quantity' => 5,
            'brand' => 'Test Brand',
            'movement_type' => 'Cuarzo',
            'category_id' => Category::first()->id,
            'is_active' => true,
        ]);
    }
}
