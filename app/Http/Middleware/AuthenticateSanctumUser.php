<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Ensures the request is authenticated as a workspace user (Sanctum),
 * not as a customer-portal contact (sanctum_customer_contact).
 */
class AuthenticateSanctumUser
{
    public function handle($request, Closure $next)
    {
        if (! Auth::guard('sanctum')->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
