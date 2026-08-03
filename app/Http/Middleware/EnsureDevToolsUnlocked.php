<?php

namespace App\Http\Middleware;

use App\Support\DevToolsGrant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates db-console, env-editor, terminal, and logs behind the same PIN as OTP fallback,
 * only when {@see config('auth.otp.fallback_enabled')} is true.
 *
 * Access uses an encrypted ?dt= token (and X-Dev-Tools-Token / form field on POST).
 * Nothing is stored server-side; reload clears client state so PIN is required again.
 */
class EnsureDevToolsUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('auth.otp.fallback_enabled')) {
            abort(403, 'This interface is disabled when OTP_FALLBACK_ENABLED is not true.');
        }

        $token = self::readToken($request);

        if ($token !== null && DevToolsGrant::valid($token)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Unlock required. Open this tool in the browser and enter the dev PIN first.',
            ], 403);
        }

        return redirect()->route('dev-tools.unlock.show', [
            'intended' => self::intendedToolPath($request),
        ]);
    }

    private static function readToken(Request $request): ?string
    {
        $q = $request->query('dt');
        if (is_string($q) && $q !== '') {
            return $q;
        }

        $h = $request->header('X-Dev-Tools-Token');
        if (is_string($h) && $h !== '') {
            return $h;
        }

        $i = $request->input('dt');
        if (is_string($i) && $i !== '') {
            return $i;
        }

        return null;
    }

    private static function intendedToolPath(Request $request): string
    {
        $path = '/'.trim($request->path(), '/');

        if (str_starts_with($path, '/backend/')) {
            $path = substr($path, strlen('/backend'));
        } elseif ($path === '/backend') {
            return '/terminal';
        }

        if ($path === '/terminal/run' || str_starts_with($path, '/terminal/')) {
            return '/terminal';
        }

        return match ($path) {
            '/db-console', '/env-editor', '/terminal', '/logs' => $path,
            '/logs/delete' => '/logs',
            default => '/terminal',
        };
    }
}
