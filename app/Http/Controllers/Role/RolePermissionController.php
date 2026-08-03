<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use App\Models\Core\Role;
use App\Support\PermissionMatrix;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{

    private array $adminModules = [
        'plans',
        'roles',
        'admins',
        'menu',
        'subscriptions',
        'payment-gateway-charges',
        'suggestions',
        'tickets',
        'tickets.categories',
        'ticket-categories',
        'scheduled-jobs',
        'languages',
    ];

    public function show(Role $role)
    {
        $allPerms = Permission::get();

        $allPerms = $allPerms->reject(function ($perm) {
            foreach ($this->adminModules as $module) {
                if (str_starts_with($perm->name, $module . '.')) {
                    return true;
                }
            }
            return false;
        });

        $rolePerms = $role->getAllPermissions()->pluck('name')->toArray();

        $matrix = [];

        foreach ($allPerms as $perm) {
            $parts = explode('.', $perm->name);
            $action = array_pop($parts);
            $entity = implode('.', $parts);

            if (PermissionMatrix::isExcludedEntity($entity)) {
                continue;
            }

            if (! in_array($entity, PermissionMatrix::entityKeys(), true)) {
                continue;
            }

            if (!isset($matrix[$entity])) {
                $matrix[$entity] = [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ];
            }

            $matrix[$entity][$action] = in_array($perm->name, $rolePerms);
        }

        return response()->json(PermissionMatrix::normalizeRoleMatrix($matrix));
    }

    public function sync(Request $r, Role $role)
    {
        if ($role->is_system) {
            throw ValidationException::withMessages([
                'role' => 'Permissions for system roles cannot be modified.',
            ]);
        }

        $permissions = PermissionMatrix::expandPermissionNames(array_values(array_filter(
            $r->input('permissions', []),
            fn ($name) => is_string($name) && PermissionMatrix::isApplicablePermissionName($name)
        )));

        $role->syncPermissions($permissions);

        return response()->json(['data' => $role->getPermissionNames()]);
    }
}
