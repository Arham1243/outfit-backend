<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** @deprecated Plan menu access removed; kept so legacy route middleware aliases still resolve. */
class BypassMenuAccessMiddleware
{
    public function handle(Request $request, Closure $next, ...$parameters)
    {
        return $next($request);
    }
}
