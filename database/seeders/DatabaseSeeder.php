<?php

namespace Database\Seeders;

use Database\Seeders\Core\AdminLanguageSeeder;
use Database\Seeders\Core\DefaultAdminSeeder;
use Database\Seeders\Core\Role\PermissionSeeder;
use Database\Seeders\Core\Role\RolePermissionSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminLanguageSeeder::class,
            PermissionSeeder::class,
            DefaultAdminSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }
}
