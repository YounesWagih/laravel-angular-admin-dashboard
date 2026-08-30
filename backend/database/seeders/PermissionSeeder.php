<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionRegistrar = app(PermissionRegistrar::class);
        $permissionRegistrar->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $permissionRegistrar->forgetCachedPermissions();
    }
}
