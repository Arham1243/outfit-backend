<?php

namespace Database\Seeders\Core\Role;

use App\Models\Core\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        self::syncAll();
    }

    public static function syncAll(): void
    {
        $tenantRoles = config('permission_matrix.tenant_roles', ['Administrator']);
        $exclusions = config('permission_matrix.exclusions', []);

        $permissions = Permission::where('guard_name', 'sanctum')->get();

        foreach ($permissions as $perm) {
            $parts = explode('.', $perm->name);
            array_pop($parts);
            $entity = implode('.', $parts);

            $applicable = self::applicableRolesForEntity($entity, $tenantRoles, $exclusions);

            $perm->applicable_roles = json_encode($applicable);
            $perm->save();

            self::assignPermissionToRoles($perm, $applicable);
        }

        self::syncUserRoleAssignments();
    }

    /**
     * @param  array<int, string>  $roleNames
     */
    protected static function assignPermissionToRoles(Permission $perm, array $roleNames): void
    {
        foreach ($roleNames as $roleName) {
            $role = self::resolveRole($roleName);

            if (! $role->hasPermissionTo($perm)) {
                $role->givePermissionTo($perm);
            }
        }
    }

    protected static function resolveRole(string $roleName): Role
    {
        $existing = Role::query()
            ->where('guard_name', 'sanctum')
            ->where('name', $roleName)
            ->first();

        if ($existing) {
            if (! $existing->is_system) {
                $existing->forceFill(['is_system' => true])->save();
            }

            return $existing;
        }

        return Role::create([
            'uuid' => (string) Str::uuid(),
            'name' => $roleName,
            'guard_name' => 'sanctum',
            'is_system' => true,
            'status' => true,
        ]);
    }

    /**
     * @param  array<int, string>  $tenantRoles
     * @param  array<string, array<int, string>>  $exclusions
     * @return array<int, string>
     */
    protected static function applicableRolesForEntity(string $entity, array $tenantRoles, array $exclusions): array
    {
        $excluded = $exclusions[$entity] ?? [];

        if ($excluded === [] && (
            $entity === 'core'
            || str_starts_with($entity, 'core.')
        )) {
            $excluded = $exclusions['core'] ?? [];
        }

        return array_values(array_diff($tenantRoles, $excluded));
    }

    protected static function syncUserRoleAssignments(): void
    {
        User::query()
            ->whereNotNull('role_id')
            ->with('role')
            ->each(function (User $user): void {
                if ($user->role) {
                    $user->syncRoles([$user->role]);
                }
            });
    }
}
