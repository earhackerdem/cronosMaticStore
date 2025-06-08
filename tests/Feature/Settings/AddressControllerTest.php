<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AddressControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function unauthenticated_user_cannot_access_addresses_page(): void
    {
        $response = $this->get('/settings/addresses');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function authenticated_user_can_access_addresses_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/settings/addresses');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('settings/addresses')
        );
    }

    #[Test]
    public function addresses_page_has_correct_route_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('addresses.index'));

        $response->assertStatus(200);
    }
}
