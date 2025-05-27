<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    /**
     * Check the API status
     *
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'API is running',
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Check the authentication status
     *
     * @return JsonResponse
     */
    public function authStatus(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'Authentication is working',
            'user' => auth()->user(),
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
