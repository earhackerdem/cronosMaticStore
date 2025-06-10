<?php

namespace Tests\Feature\Http\Controllers\Api\V1\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['is_admin' => true]);
        $this->regularUser = User::factory()->create(['is_admin' => false]);

        // Crear el middleware 'admin' si no existe para que las pruebas pasen
        // Esto es un placeholder y debería ser reemplazado por la lógica real del middleware
        // en app/Http/Kernel.php y la creación del archivo del middleware.
        app('router')->aliasMiddleware('admin', \App\Http\Middleware\EnsureUserIsAdmin::class);


    }

    private function createCategoryData(array $overrides = []): array
    {
        $name = $this->faker->unique()->words(2, true);
        return array_merge([
            'name' => $name,
            'description' => $this->faker->sentence,
            'is_active' => $this->faker->boolean,
        ], $overrides);
    }

    public function test_admin_can_get_all_categories()
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        // Clear any existing categories first
        Category::query()->delete();

        $categories = Category::factory()->count(3)->create();

        $response = $this->getJson(route('categories.index'));

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'slug', 'description', 'image_path', 'is_active', 'created_at', 'updated_at']
            ],
            'links',
            'meta'
        ]);

        // Verify the categories are the ones we created
        foreach ($categories as $category) {
            $response->assertJsonFragment(['id' => $category->id]);
        }
    }

    public function test_non_admin_cannot_get_all_categories()
    {
        Sanctum::actingAs($this->regularUser, ['*']);
        Category::factory()->count(3)->create();

        $response = $this->getJson(route('categories.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_create_a_category()
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        $categoryData = $this->createCategoryData();

        $response = $this->postJson(route('categories.store'), $categoryData);

        $response->assertCreated();
        $response->assertJsonFragment(['name' => $categoryData['name']]);
        $this->assertDatabaseHas('categories', [
            'name' => $categoryData['name'],
            'slug' => \Illuminate\Support\Str::slug($categoryData['name']),
        ]);
    }

    public function test_non_admin_cannot_create_a_category()
    {
        Sanctum::actingAs($this->regularUser, ['*']);
        $categoryData = $this->createCategoryData();

        $response = $this->postJson(route('categories.store'), $categoryData);

        $response->assertForbidden();
    }

    public function test_admin_can_get_a_specific_category()
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        $category = Category::factory()->create();

        $response = $this->getJson(route('categories.show', $category->id));

        $response->assertOk();
        $response->assertJsonFragment(['name' => $category->name]);
    }

    public function test_admin_can_update_a_category()
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        $category = Category::factory()->create();
        $updateData = $this->createCategoryData(['name' => 'Updated Name']);

        $response = $this->putJson(route('categories.update', $category->id), $updateData);

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Updated Name']);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Updated Name']);
    }

    public function test_non_admin_cannot_update_a_category()
    {
        Sanctum::actingAs($this->regularUser, ['*']);
        $category = Category::factory()->create();
        $updateData = $this->createCategoryData(['name' => 'Updated Name']);

        $response = $this->putJson(route('categories.update', $category->id), $updateData);
        $response->assertForbidden();
    }

    public function test_admin_can_delete_a_category()
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        $category = Category::factory()->create();

        $response = $this->deleteJson(route('categories.destroy', $category->id));

        $response->assertNoContent();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        // Si usas SoftDeletes, deberías usar assertSoftDeleted en su lugar.
    }

    public function test_non_admin_cannot_delete_a_category()
    {
        Sanctum::actingAs($this->regularUser, ['*']);
        $category = Category::factory()->create();

        $response = $this->deleteJson(route('categories.destroy', $category->id));
        $response->assertForbidden();
    }

    public function test_create_category_validation_requires_name()
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        $categoryData = $this->createCategoryData(['name' => '']); // Nombre vacío

        $response = $this->postJson(route('categories.store'), $categoryData);

        $response->assertStatus(422); // HTTP Unprocessable Entity
        $response->assertJsonValidationErrors('name');
    }

    public function test_update_category_validation_works_correctly()
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        $category = Category::factory()->create();
        // Intenta actualizar con un nombre vacío, lo cual debería fallar si es 'sometimes|required'
        // Para que falle la validación 'sometimes|required' el campo debe estar presente y vacío.
        $updateData = ['name' => ''];

        $response = $this->putJson(route('categories.update', $category->id), $updateData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }
}
