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

        // Ejemplo de creación de productos específicos si es necesario
        // Product::factory()->create([
        //     'name' => 'Producto de Ejemplo Específico',
        //     'category_id' => Category::first()->id, // Asignar a la primera categoría, por ejemplo
        //     // ... otros atributos
        // ]);
    }
}
