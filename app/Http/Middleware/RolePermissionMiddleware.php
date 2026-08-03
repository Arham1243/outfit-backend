<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RolePermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        if (! $user) {
            throw new HttpException(403, 'User not authenticated');
        }

        // Permissions are resolved from users.role_id → role_has_permissions (see User model).
        if (! method_exists($user, 'hasPermissionTo')) {
            throw new HttpException(403, 'User does not have permission support.');
        }

        if (! $user->hasPermissionTo($permission)) {
            throw new HttpException(403, 'User does not have the right role permission.');
        }

        return $next($request);
    }
}
