<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImageUploadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create(['is_admin' => true]);
        $this->regularUser = User::factory()->create(['is_admin' => false]);
        Storage::fake('public'); // Usar un disco de almacenamiento falso para las pruebas
        $this->withHeaders(['Accept' => 'application/json']); // Asegurar que se aceptan respuestas JSON
    }

    public function test_admin_can_upload_product_image(): void
    {
        Sanctum::actingAs($this->adminUser);

        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->postJson(route('admin.images.upload'), [
            'image' => $file,
            'type' => 'products'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'path',
                'url',
                'relative_url'
            ]);

        $responseData = $response->json();
        Storage::disk('public')->assertExists($responseData['relative_url']);
        $this->assertStringContainsString('products/', $responseData['relative_url']); // Comprobar que la ruta relativa contiene 'products/'
    }

    public function test_admin_can_upload_category_image(): void
    {
        Sanctum::actingAs($this->adminUser);
        $file = UploadedFile::fake()->image('category.png');
        $response = $this->postJson(route('admin.images.upload'), [
            'image' => $file,
            'type' => 'categories'
        ]);
        $response->assertStatus(201);
        $responseData = $response->json();
        Storage::disk('public')->assertExists($responseData['relative_url']);
        $this->assertStringContainsString('categories/', $responseData['relative_url']);
    }

    public function test_upload_fails_if_file_is_not_image(): void
    {
        Sanctum::actingAs($this->adminUser);
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $response = $this->postJson(route('admin.images.upload'), [
            'image' => $file,
            'type' => 'products'
        ]);
        $response->assertStatus(422)
            ->assertJsonStructure(['image']);
    }

    public function test_upload_fails_if_file_is_too_large(): void
    {
        Sanctum::actingAs($this->adminUser);
        $file = UploadedFile::fake()->image('large_image.jpg')->size(3000); // 3MB, excede 2048KB
        $response = $this->postJson(route('admin.images.upload'), [
            'image' => $file,
            'type' => 'products'
        ]);
        $response->assertStatus(422)
            ->assertJsonStructure(['image']);
    }

    public function test_upload_fails_if_type_is_invalid(): void
    {
        Sanctum::actingAs($this->adminUser);
        $file = UploadedFile::fake()->image('image.jpg');
        $response = $this->postJson(route('admin.images.upload'), [
            'image' => $file,
            'type' => 'invalid_type'
        ]);
        $response->assertStatus(422)
            ->assertJsonStructure(['type']);
    }

    public function test_non_admin_cannot_upload_image(): void
    {
        Sanctum::actingAs($this->regularUser);
        $file = UploadedFile::fake()->image('image.jpg');
        $response = $this->postJson(route('admin.images.upload'), [
            'image' => $file,
            'type' => 'products'
        ]);
        $response->assertStatus(403); // Forbidden
    }

    public function test_guest_cannot_upload_image(): void
    {
        $file = UploadedFile::fake()->image('image.jpg');
        $response = $this->postJson(route('admin.images.upload'), [
            'image' => $file,
            'type' => 'products'
        ]);
        $response->assertStatus(401); // Unauthorized
    }
}
