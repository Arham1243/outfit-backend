<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** @deprecated Plan menu limits removed; kept so legacy route middleware aliases still resolve. */
class BypassMenuLimitMiddleware
{
    public function handle(Request $request, Closure $next, ...$parameters)
    {
        return $next($request);
    }
}
