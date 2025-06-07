<?php

namespace Tests\Unit;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AddressModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_create_address_with_valid_data(): void
    {
        $user = User::factory()->create();

        $address = Address::factory()->create([
            'user_id' => $user->id,
            'type' => Address::TYPE_SHIPPING,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertInstanceOf(Address::class, $address);
        $this->assertEquals($user->id, $address->user_id);
        $this->assertEquals(Address::TYPE_SHIPPING, $address->type);
        $this->assertEquals('John', $address->first_name);
        $this->assertEquals('Doe', $address->last_name);
    }

    #[Test]
    public function belongs_to_user(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $address->user);
        $this->assertEquals($user->id, $address->user->id);
    }

    #[Test]
    public function can_get_full_name_attribute(): void
    {
        $address = Address::factory()->make([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $address->full_name);
    }

    #[Test]
    public function can_get_full_address_attribute(): void
    {
        $address = Address::factory()->make([
            'address_line_1' => '123 Main St',
            'address_line_2' => 'Apt 4B',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
        ]);

        $expected = '123 Main St, Apt 4B, New York, NY 10001, USA';
        $this->assertEquals($expected, $address->full_address);
    }

    #[Test]
    public function can_get_full_address_attribute_without_address_line_2(): void
    {
        $address = Address::factory()->make([
            'address_line_1' => '123 Main St',
            'address_line_2' => null,
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
        ]);

        $expected = '123 Main St, New York, NY 10001, USA';
        $this->assertEquals($expected, $address->full_address);
    }

    #[Test]
    public function can_scope_by_default(): void
    {
        $user = User::factory()->create();

        Address::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        Address::factory()->create(['user_id' => $user->id, 'is_default' => false]);

        $defaultAddresses = Address::default()->get();

        $this->assertCount(1, $defaultAddresses);
        $this->assertTrue($defaultAddresses->first()->is_default);
    }

    #[Test]
    public function can_scope_by_type(): void
    {
        $user = User::factory()->create();

        Address::factory()->shipping()->create(['user_id' => $user->id]);
        Address::factory()->billing()->create(['user_id' => $user->id]);

        $shippingAddresses = Address::byType(Address::TYPE_SHIPPING)->get();
        $billingAddresses = Address::byType(Address::TYPE_BILLING)->get();

        $this->assertCount(1, $shippingAddresses);
        $this->assertCount(1, $billingAddresses);
        $this->assertEquals(Address::TYPE_SHIPPING, $shippingAddresses->first()->type);
        $this->assertEquals(Address::TYPE_BILLING, $billingAddresses->first()->type);
    }

    #[Test]
    public function creating_default_address_unsets_other_default_addresses_of_same_type(): void
    {
        $user = User::factory()->create();

        // Create first default shipping address
        $firstAddress = Address::factory()->shipping()->default()->create(['user_id' => $user->id]);
        $this->assertTrue($firstAddress->fresh()->is_default);

        // Create second default shipping address
        $secondAddress = Address::factory()->shipping()->default()->create(['user_id' => $user->id]);

        // First address should no longer be default
        $this->assertFalse($firstAddress->fresh()->is_default);
        $this->assertTrue($secondAddress->fresh()->is_default);
    }

    #[Test]
    public function updating_address_to_default_unsets_other_default_addresses_of_same_type(): void
    {
        $user = User::factory()->create();

        // Create two shipping addresses, first one is default
        $firstAddress = Address::factory()->shipping()->default()->create(['user_id' => $user->id]);
        $secondAddress = Address::factory()->shipping()->create(['user_id' => $user->id, 'is_default' => false]);

        $this->assertTrue($firstAddress->fresh()->is_default);
        $this->assertFalse($secondAddress->fresh()->is_default);

        // Update second address to be default
        $secondAddress->update(['is_default' => true]);

        // First address should no longer be default
        $this->assertFalse($firstAddress->fresh()->is_default);
        $this->assertTrue($secondAddress->fresh()->is_default);
    }

    #[Test]
    public function default_addresses_of_different_types_can_coexist(): void
    {
        $user = User::factory()->create();

        // Create default shipping and billing addresses
        $shippingAddress = Address::factory()->shipping()->default()->create(['user_id' => $user->id]);
        $billingAddress = Address::factory()->billing()->default()->create(['user_id' => $user->id]);

        // Both should remain default since they are different types
        $this->assertTrue($shippingAddress->fresh()->is_default);
        $this->assertTrue($billingAddress->fresh()->is_default);
    }

    #[Test]
    public function is_default_is_cast_to_boolean(): void
    {
        $address = Address::factory()->create(['is_default' => 1]);

        $this->assertIsBool($address->is_default);
        $this->assertTrue($address->is_default);
    }
}
