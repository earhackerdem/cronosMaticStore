<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::factory()->count(5)->create();

        // Ejemplo de categorías específicas:
        Category::factory()->create([
            'name' => 'Relojes de Pulsera',
            'slug' => 'relojes-de-pulsera',
            'description' => 'La mejor selección de relojes de pulsera para cada ocasión.',
            'is_active' => true,
        ]);

        Category::factory()->create([
            'name' => 'Relojes de Pared',
            'slug' => 'relojes-de-pared',
            'description' => 'Decora tu hogar con nuestros elegantes relojes de pared.',
            'is_active' => true,
        ]);
    }
}
