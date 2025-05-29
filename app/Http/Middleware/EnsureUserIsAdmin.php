<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->is_admin) {
            // abort(403, 'USER_IS_NOT_ADMIN'); // Opción 1: Abortar con código de error
            return response()->json(['message' => 'Forbidden. User is not an administrator.'], 403); // Opción 2: Respuesta JSON
        }

        return $next($request);
    }
}
