<?php

namespace Database\Seeders\Core\Role;

use App\Support\PermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Names must match routes (granular.permission), middleware regex, and the SPA (e.g. reports.timesheet.view).
        foreach (PermissionMatrix::entityKeys() as $module) {
            foreach (PermissionMatrix::applicableActionsFor($module) as $action) {
                Permission::firstOrCreate(['name' => "{$module}.{$action}", 'guard_name' => 'sanctum']);
            }
        }
    }
}
