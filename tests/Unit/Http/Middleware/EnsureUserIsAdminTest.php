<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnsureUserIsAdminTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function non_admin_user_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $request = Request::create('/admin/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new EnsureUserIsAdmin();
        $response = $middleware->handle($request, fn () => new JsonResponse());

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('{"message":"Forbidden. User is not an administrator."}', $response->getContent());
    }

    #[Test]
    public function admin_user_can_access(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $request = Request::create('/admin/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $nextResponse = new JsonResponse(['data' => 'ok']);
        $middleware = new EnsureUserIsAdmin();
        $response = $middleware->handle($request, fn () => $nextResponse);

        $this->assertSame($nextResponse, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function guest_user_is_forbidden(): void
    {
        $request = Request::create('/admin/test', 'GET');
        // No user is set on the request

        $middleware = new EnsureUserIsAdmin();
        $response = $middleware->handle($request, fn () => new JsonResponse());

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('{"message":"Forbidden. User is not an administrator."}', $response->getContent());
    }
}
