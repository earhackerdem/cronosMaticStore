<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_has_is_admin_attribute(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->assertTrue($user->is_admin);

        $userWithoutAdmin = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->assertFalse($userWithoutAdmin->is_admin);
    }

    #[Test]
    public function is_admin_is_false_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->is_admin);
    }

    #[Test]
    public function is_admin_is_cast_to_boolean(): void
    {
        $user = User::factory()->create(['is_admin' => 1]);
        $this->assertTrue($user->is_admin);
        $this->assertIsBool($user->is_admin);

        $user = User::factory()->create(['is_admin' => 0]);
        $this->assertFalse($user->is_admin);
        $this->assertIsBool($user->is_admin);
    }
}
