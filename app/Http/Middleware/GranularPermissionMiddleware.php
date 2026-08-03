<?php

namespace App\Http\Middleware;

use App\Support\PermissionMatrix;
use Closure;
use Illuminate\Http\Request;

/**
 * Checks legacy "{prefix}.view" (e.g. core.view, reports.view) OR granular "{entity}.{action}".
 * Entities under "reports.*" (except legacy entity "reports") are read-only: all HTTP methods require ".view".
 * Special entity "reports" (with legacy prefix "reports") allows reports.view OR any reports.*.view.
 */
class GranularPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $legacyPrefix, string $entity)
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'User not authenticated');
        }

        $role = $user->role;

        if (! $role) {
            abort(403, 'User has no role assigned.');
        }

        $names = $role->permissions->pluck('name')->toArray();

        if (in_array("{$legacyPrefix}.view", $names, true)) {
            return $next($request);
        }

        if ($legacyPrefix === 'reports' && $entity === 'reports') {
            if ($this->hasAnyReportsGranularView($names)) {
                return $next($request);
            }
            abort(403, 'User does not have the right role permission.');
        }

        $isReportsGranular = $entity !== 'reports' && str_starts_with($entity, 'reports.');

        if ($isReportsGranular) {
            if (in_array("{$entity}.view", $names, true)) {
                return $next($request);
            }
            abort(403, 'User does not have the right role permission.');
        }

        $method = $request->method();
        $path = $request->path();
        if (
            $method === 'POST'
            && (str_ends_with($path, '/make-default')
                || str_ends_with($path, '/change-status'))
        ) {
            $action = 'edit';
        } elseif (
            $method === 'POST'
            && (str_ends_with($path, '/search')
                || str_ends_with($path, '/list')
                || str_ends_with($path, '/tree'))
        ) {
            $action = 'view';
        } else {
            $action = match (true) {
                in_array($method, ['GET', 'HEAD', 'OPTIONS'], true) => 'view',
                $method === 'POST' => 'create',
                in_array($method, ['PUT', 'PATCH'], true) => 'edit',
                $method === 'DELETE' => 'delete',
                default => 'view',
            };

            $applicableActions = PermissionMatrix::applicableActionsFor($entity);
            if (
                in_array($action, ['create', 'delete'], true)
                && ! in_array($action, $applicableActions, true)
                && in_array('edit', $applicableActions, true)
            ) {
                $action = 'edit';
            }
        }

        $perm = "{$entity}.{$action}";
        if (in_array($perm, $names, true)) {
            return $next($request);
        }

        abort(403, 'User does not have the right role permission.');
    }

    /**
     * @param  array<int, string>  $names
     */
    protected function hasAnyReportsGranularView(array $names): bool
    {
        foreach ($names as $name) {
            if (preg_match('/^reports\.[a-z0-9.-]+\.view$/', (string) $name) === 1) {
                return true;
            }
        }

        return false;
    }
}
