<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::query()->update(['is_default' => false]);

        $roles = [
            [
                'name' => 'Product Manager',
                'description' => 'Manages products and can view categories.',
                'is_default' => false,
                'permissions' => [
                    PermissionName::ProductsRead,
                    PermissionName::ProductsCreate,
                    PermissionName::ProductsUpdate,
                    PermissionName::ProductsDelete,
                    PermissionName::CategoriesRead,
                ],
            ],
            [
                'name' => 'Product Editor',
                'description' => 'Creates and edits products without delete access.',
                'is_default' => false,
                'permissions' => [
                    PermissionName::ProductsRead,
                    PermissionName::ProductsCreate,
                    PermissionName::ProductsUpdate,
                    PermissionName::CategoriesRead,
                ],
            ],
            [
                'name' => 'Viewer',
                'description' => 'Read-only access to products and categories.',
                'is_default' => true,
                'permissions' => [
                    PermissionName::ProductsRead,
                    PermissionName::CategoriesRead,
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissionNames = array_map(
                static fn (PermissionName $permission): string => $permission->value,
                $roleData['permissions'],
            );

            $role = Role::findOrCreate($roleData['name'], 'web');
            $role->update([
                'description' => $roleData['description'],
                'is_default' => $roleData['is_default'],
            ]);
            $role->syncPermissions($permissionNames);
        }
    }
}
