<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Facades\DB;

final class RoleService
{
    public function create(array $data): Role
    {
        $role = Role::query()->create([
            ...$data,
            'guard_name' => 'web',
        ]);

        return $this->load($role);
    }

    public function update(Role $role, array $data): Role
    {
        $role->update($data);

        return $this->load($role);
    }

    public function setDefault(Role $role): Role
    {
        DB::transaction(function () use ($role): void {
            Role::query()
                ->where('guard_name', 'web')
                ->whereKeyNot($role->getKey())
                ->update(['is_default' => false]);

            $role->update(['is_default' => true]);
        });

        return $this->load($role->refresh());
    }

    public function syncPermissions(Role $role, array $permissions): Role
    {
        $role->syncPermissions($permissions);

        return $this->load($role);
    }

    private function load(Role $role): Role
    {
        return $role->load('permissions')->loadCount('users');
    }
}
