<?php

namespace Database\Seeders\Core;

use App\Models\Core\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(
            [
                'name' => 'Administrator',
                'guard_name' => 'sanctum',
            ],
            [
                'is_system' => true,
                'status' => true,
            ]
        );

        if (! $role->is_system) {
            Role::query()->whereKey($role->id)->update(['is_system' => true]);
            $role->is_system = true;
        }

        // Create default admin user
        $admin = User::firstOrCreate(
            [
                'email' => 'kay@intelygic.com',
            ],
            [
                'role_id' => $role->id,
                'name' => 'Administrator',
                'password' => Hash::make('12345678'),
                'status' => 'active',
            ]
        );

        // Keep Spatie role pivot in sync with users.role_id.
        $admin->syncRoles([$role]);
    }
}
