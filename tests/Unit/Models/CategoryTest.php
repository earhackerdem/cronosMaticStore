<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Product; // Necesario si se prueba la relación
use Illuminate\Foundation\Testing\RefreshDatabase;
// use Illuminate\Foundation\Testing\WithFaker; // Si se usa faker directamente
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_category_can_be_created_using_factory()
    {
        $category = Category::factory()->create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);
        $this->assertInstanceOf(Category::class, $category);
    }

    /** @test */
    public function category_model_uses_fillable_attributes()
    {
        $category = new Category();
        $fillable = ['name', 'slug', 'description', 'image_path', 'is_active'];
        $this->assertEquals($fillable, $category->getFillable());
    }

    /** @test */
    public function category_model_casts_is_active_to_boolean()
    {
        $category = Category::factory()->create(['is_active' => 1]);
        $this->assertTrue($category->is_active);

        $category->update(['is_active' => 0]);
        $this->assertFalse($category->is_active);
    }

    // Descomentar y ajustar cuando el modelo Product esté disponible
    // /** @test */
    // public function a_category_can_have_many_products()
    // {
    //     $category = Category::factory()->create();
    //     Product::factory()->count(3)->create(['category_id' => $category->id]);

    //     $this->assertInstanceOf(Product::class, $category->products->first());
    //     $this->assertCount(3, $category->products);
    // }
}
