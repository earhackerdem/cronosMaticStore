<?php

namespace Tests\Feature\Api\V1\User;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
// Removed Laravel\Sanctum\Sanctum import - now using web authentication
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AddressControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function unauthenticated_user_cannot_access_addresses(): void
    {
        $response = $this->getJson('/api/v1/user/addresses');

        $response->assertStatus(401);
    }

    #[Test]
    public function authenticated_user_can_get_their_addresses(): void
    {
        $this->actingAs($this->user);

        Address::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/v1/user/addresses');

        $response->assertStatus(200)
                ->assertJsonCount(3, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'type',
                            'first_name',
                            'last_name',
                            'full_name',
                            'company',
                            'address_line_1',
                            'address_line_2',
                            'city',
                            'state',
                            'postal_code',
                            'country',
                            'phone',
                            'is_default',
                            'full_address',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]);
    }

    #[Test]
    public function user_can_filter_addresses_by_type(): void
    {
        $this->actingAs($this->user);

        Address::factory()->shipping()->count(2)->create(['user_id' => $this->user->id]);
        Address::factory()->billing()->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/v1/user/addresses?type=shipping');

        $response->assertStatus(200)
                ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $address) {
            $this->assertEquals('shipping', $address['type']);
        }
    }

    #[Test]
    public function addresses_are_ordered_by_default_first_then_created_at(): void
    {
        $this->actingAs($this->user);

        $oldAddress = Address::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
            'created_at' => now()->subDays(2)
        ]);

        $defaultAddress = Address::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true,
            'created_at' => now()->subDay()
        ]);

        $newAddress = Address::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
            'created_at' => now()
        ]);

        $response = $this->getJson('/api/v1/user/addresses');

        $response->assertStatus(200);

        $addresses = $response->json('data');
        $this->assertEquals($defaultAddress->id, $addresses[0]['id']);
        $this->assertEquals($newAddress->id, $addresses[1]['id']);
        $this->assertEquals($oldAddress->id, $addresses[2]['id']);
    }

    #[Test]
    public function user_can_create_new_address(): void
    {
        $this->actingAs($this->user);

        $addressData = [
            'type' => Address::TYPE_SHIPPING,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company' => 'Acme Corp',
            'address_line_1' => '123 Main St',
            'address_line_2' => 'Apt 4B',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
            'phone' => '+1234567890',
            'is_default' => true,
        ];

        $response = $this->postJson('/api/v1/user/addresses', $addressData);

        $response->assertStatus(201)
                ->assertJsonFragment([
                    'type' => 'shipping',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'full_name' => 'John Doe',
                    'is_default' => true,
                ]);

        $this->assertDatabaseHas('addresses', [
            'user_id' => $this->user->id,
            'type' => 'shipping',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    #[Test]
    public function address_creation_requires_valid_data(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/user/addresses', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'type',
                    'first_name',
                    'last_name',
                    'address_line_1',
                    'city',
                    'state',
                    'postal_code',
                    'country',
                ]);
    }

    #[Test]
    public function address_type_must_be_valid(): void
    {
        $this->actingAs($this->user);

        $addressData = [
            'type' => 'invalid_type',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
        ];

        $response = $this->postJson('/api/v1/user/addresses', $addressData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function user_can_view_specific_address(): void
    {
        $this->actingAs($this->user);

        $address = Address::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/v1/user/addresses/{$address->id}");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'id' => $address->id,
                    'first_name' => $address->first_name,
                    'last_name' => $address->last_name,
                ]);
    }

    #[Test]
    public function user_cannot_view_other_users_address(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/v1/user/addresses/{$address->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function user_can_update_their_address(): void
    {
        $this->actingAs($this->user);

        $address = Address::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'city' => 'Los Angeles',
        ];

        $response = $this->putJson("/api/v1/user/addresses/{$address->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                    'city' => 'Los Angeles',
                ]);

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'city' => 'Los Angeles',
        ]);
    }

    #[Test]
    public function user_cannot_update_other_users_address(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->putJson("/api/v1/user/addresses/{$address->id}", [
            'first_name' => 'Jane',
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function user_can_delete_their_address(): void
    {
        $this->actingAs($this->user);

        $address = Address::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/v1/user/addresses/{$address->id}");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'message' => 'Dirección eliminada exitosamente.'
                ]);

        $this->assertDatabaseMissing('addresses', [
            'id' => $address->id,
        ]);
    }

    #[Test]
    public function user_cannot_delete_other_users_address(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->deleteJson("/api/v1/user/addresses/{$address->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function user_can_set_address_as_default(): void
    {
        $this->actingAs($this->user);

        $defaultAddress = Address::factory()->shipping()->default()->create(['user_id' => $this->user->id]);
        $address = Address::factory()->shipping()->create(['user_id' => $this->user->id, 'is_default' => false]);

        $response = $this->patchJson("/api/v1/user/addresses/{$address->id}/set-default");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'is_default' => true,
                ]);

        // The new address should be default
        $this->assertTrue($address->fresh()->is_default);
        // The old default address should no longer be default
        $this->assertFalse($defaultAddress->fresh()->is_default);
    }

    #[Test]
    public function user_cannot_set_other_users_address_as_default(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->patchJson("/api/v1/user/addresses/{$address->id}/set-default");

        $response->assertStatus(403);
    }

    #[Test]
    public function user_only_sees_their_own_addresses(): void
    {
        $this->actingAs($this->user);

        // Create addresses for current user
        Address::factory()->count(2)->create(['user_id' => $this->user->id]);

        // Create addresses for other user
        $otherUser = User::factory()->create();
        Address::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/v1/user/addresses');

        $response->assertStatus(200)
                ->assertJsonCount(2, 'data');

        // All addresses should belong to the current user (we can't directly check user_id in response)
        // But we know we created 2 for current user and 3 for other user, and we only see 2
    }
}
