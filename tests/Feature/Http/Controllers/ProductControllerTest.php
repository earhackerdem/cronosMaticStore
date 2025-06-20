<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_show_active_product_detail_with_all_data()
    {
        // Arrange: Crear categoría y producto con todos los datos
        $category = Category::factory()->create([
            'name' => 'Relojes Deportivos',
            'slug' => 'relojes-deportivos',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'name' => 'Reloj Deportivo Casio G-Shock',
            'slug' => 'reloj-deportivo-casio-g-shock',
            'description' => 'Reloj resistente al agua y a los golpes, perfecto para deportes extremos.',
            'sku' => 'CASIO-GSHOCK-001',
            'price' => 2500.00,
            'stock_quantity' => 10,
            'brand' => 'Casio',
            'movement_type' => 'Quartz',
            'image_path' => '/storage/products/casio-gshock.jpg',
            'is_active' => true,
            'category_id' => $category->id,
        ]);

        // Act: Hacer petición GET al endpoint
        $response = $this->get(route('web.products.show', $product->slug));

        // Assert: Verificar respuesta exitosa y datos
        $response->assertStatus(200);

                 $response->assertInertia(fn (Assert $page) => $page
            ->component('Products/Show')
            ->has('product', fn (Assert $productAssert) => $productAssert
                ->where('id', $product->id)
                ->where('name', 'Reloj Deportivo Casio G-Shock')
                ->where('slug', 'reloj-deportivo-casio-g-shock')
                ->where('description', 'Reloj resistente al agua y a los golpes, perfecto para deportes extremos.')
                ->where('sku', 'CASIO-GSHOCK-001')
                ->where('price', '2500.00')
                ->where('stock_quantity', 10)
                ->where('brand', 'Casio')
                ->where('movement_type', 'Quartz')
                ->where('image_path', '/storage/products/casio-gshock.jpg')
                ->where('is_active', true)
                ->has('category', fn (Assert $categoryAssert) => $categoryAssert
                    ->where('name', 'Relojes Deportivos')
                    ->where('slug', 'relojes-deportivos')
                    ->where('is_active', true)
                    ->etc()
                )
                ->has('image_url') // Verificar que se incluye la URL de la imagen
                ->etc()
            )
        );
    }

    #[Test]
    public function can_show_product_with_minimal_data()
    {
        // Arrange: Crear producto con datos mínimos
        $product = Product::factory()->create([
            'name' => 'Reloj Básico',
            'slug' => 'reloj-basico',
            'description' => null,
            'sku' => null,
            'price' => 1000.00,
            'stock_quantity' => 5,
            'brand' => null,
            'movement_type' => null,
            'image_path' => null,
            'is_active' => true,
            'category_id' => null,
        ]);

        // Act
        $response = $this->get(route('web.products.show', $product->slug));

        // Assert
        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Products/Show')
            ->has('product', fn (Assert $productAssert) => $productAssert
                ->where('name', 'Reloj Básico')
                ->where('slug', 'reloj-basico')
                ->where('description', null)
                ->where('sku', null)
                ->where('price', '1000.00')
                ->where('stock_quantity', 5)
                ->where('brand', null)
                ->where('movement_type', null)
                ->where('image_path', null)
                ->where('is_active', true)
                ->where('category', null)
                ->etc()
            )
        );
    }

    #[Test]
    public function can_show_product_without_stock()
    {
        // Arrange: Crear producto agotado
        $product = Product::factory()->create([
            'name' => 'Reloj Agotado',
            'slug' => 'reloj-agotado',
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        // Act
        $response = $this->get(route('web.products.show', $product->slug));

        // Assert
        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Products/Show')
            ->has('product', fn (Assert $productAssert) => $productAssert
                ->where('stock_quantity', 0)
                ->where('is_active', true)
                ->etc()
            )
        );
    }

    #[Test]
    public function returns_404_when_product_does_not_exist()
    {
        // Act: Intentar acceder a un producto que no existe
        $response = $this->get(route('web.products.show', 'producto-inexistente'));

        // Assert: Debe retornar 404
        $response->assertStatus(404);
    }

    #[Test]
    public function returns_404_when_product_is_inactive()
    {
        // Arrange: Crear producto inactivo
        $product = Product::factory()->create([
            'name' => 'Producto Inactivo',
            'slug' => 'producto-inactivo',
            'is_active' => false,
        ]);

        // Act: Intentar acceder al producto inactivo
        $response = $this->get(route('web.products.show', $product->slug));

        // Assert: Debe retornar 404 (criterio AC3 de HU1.2)
        $response->assertStatus(404);
    }

    #[Test]
    public function includes_category_relation_when_exists()
    {
        // Arrange
        $category = Category::factory()->create([
            'name' => 'Relojes de Lujo',
            'slug' => 'relojes-de-lujo',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'name' => 'Reloj de Lujo',
            'slug' => 'reloj-de-lujo',
            'is_active' => true,
            'category_id' => $category->id,
        ]);

        // Act
        $response = $this->get(route('web.products.show', $product->slug));

        // Assert: Verificar que se incluye la categoría
        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Products/Show')
            ->has('product.category', fn (Assert $categoryAssert) => $categoryAssert
                ->where('name', 'Relojes de Lujo')
                ->where('slug', 'relojes-de-lujo')
                ->where('is_active', true)
                ->etc()
            )
        );
    }

    #[Test]
    public function does_not_include_category_when_not_exists()
    {
        // Arrange: Producto sin categoría
        $product = Product::factory()->create([
            'name' => 'Reloj Sin Categoría',
            'slug' => 'reloj-sin-categoria',
            'is_active' => true,
            'category_id' => null,
        ]);

        // Act
        $response = $this->get(route('web.products.show', $product->slug));

        // Assert: Verificar que category es null
        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Products/Show')
            ->has('product', fn (Assert $productAssert) => $productAssert
                ->where('category', null)
                ->etc()
            )
        );
    }

    #[Test]
    public function generates_image_url_correctly()
    {
        // Arrange: Producto con imagen
        $product = Product::factory()->create([
            'name' => 'Reloj Con Imagen',
            'slug' => 'reloj-con-imagen',
            'image_path' => '/storage/products/reloj-test.jpg',
            'is_active' => true,
        ]);

        // Act
        $response = $this->get(route('web.products.show', $product->slug));

        // Assert: Verificar que se genera la URL de la imagen
        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Products/Show')
            ->has('product', fn (Assert $productAssert) => $productAssert
                ->where('image_path', '/storage/products/reloj-test.jpg')
                ->has('image_url') // Debe tener la URL generada
                ->etc()
            )
        );
    }

    #[Test]
    public function handles_product_without_image_correctly()
    {
        // Arrange: Producto sin imagen
        $product = Product::factory()->create([
            'name' => 'Reloj Sin Imagen',
            'slug' => 'reloj-sin-imagen',
            'image_path' => null,
            'is_active' => true,
        ]);

        // Act
        $response = $this->get(route('web.products.show', $product->slug));

        // Assert: Verificar que image_url devuelve una imagen por defecto cuando no hay imagen
        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Products/Show')
            ->has('product', fn (Assert $productAssert) => $productAssert
                ->where('image_path', null)
                ->where('image_url', function ($imageUrl) {
                    // Verificar que devuelve una de las imágenes por defecto de Chrono24
                    $defaultImages = [
                        'https://img.chrono24.com/images/uhren/39539607-m9too3m1kpkrqpnnxklsvnxp-Zoom.jpg',
                        'https://img.chrono24.com/images/uhren/40851974-em5oh9xyb3j849bffkxv8rls-Zoom.jpg',
                        'https://img.chrono24.com/images/uhren/26900830-3i5ennqwbi0zcqufcqyxjs5v-Zoom.jpg',
                    ];
                    return in_array($imageUrl, $defaultImages);
                })
                ->etc()
            )
        );
    }

    #[Test]
    public function response_includes_all_required_fields()
    {
        // Arrange
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $category->id,
        ]);

        // Act
        $response = $this->get(route('web.products.show', $product->slug));

        // Assert: Verificar que todos los campos requeridos están presentes
        $response->assertStatus(200);

                $response->assertInertia(fn (Assert $page) => $page
            ->component('Products/Show')
            ->has('product', fn (Assert $productAssert) => $productAssert
                                ->hasAll([
                    'id',
                    'name',
                    'slug',
                    'description',
                    'sku',
                    'price',
                    'stock_quantity',
                    'brand',
                    'movement_type',
                    'image_path',
                    'image_url',
                    'is_active',
                    'category',
                    'created_at',
                    'updated_at'
                ])
                ->etc()
            )
        );
    }

    #[Test]
    public function slug_is_case_sensitive()
    {
        // Arrange
        $product = Product::factory()->create([
            'name' => 'Reloj Test',
            'slug' => 'reloj-test',
            'is_active' => true,
        ]);

        // Act & Assert: Slug en mayúsculas debe retornar 404
        $response = $this->get(route('web.products.show', 'RELOJ-TEST'));
        $response->assertStatus(404);

        // Act & Assert: Slug correcto debe funcionar
        $response = $this->get(route('web.products.show', 'reloj-test'));
        $response->assertStatus(200);
    }
}
