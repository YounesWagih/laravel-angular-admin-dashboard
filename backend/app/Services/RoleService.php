<?php

namespace App\Services;

use App\Enums\RoleDeletionResult;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class RoleService
{
    public function getAll(): Collection
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->withCount('users')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function getAllPermissions(): Collection
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();
    }

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
        $role = DB::transaction(function () use ($role): Role {
            $role = Role::query()
                ->where('guard_name', 'web')
                ->lockForUpdate()
                ->findOrFail($role->id);

            Role::query()
                ->where('guard_name', 'web')
                ->whereKeyNot($role->getKey())
                ->update(['is_default' => false]);

            $role->update(['is_default' => true]);

            return $role;
        });

        return $this->load($role);
    }

    public function syncPermissions(Role $role, array $permissions): Role
    {
        $role = DB::transaction(function () use ($role, $permissions): Role {
            $role = Role::query()
                ->where('guard_name', 'web')
                ->lockForUpdate()
                ->findOrFail($role->id);

            $role->syncPermissions($permissions);

            return $role;
        });

        return $this->load($role);
    }

    public function delete(Role $role): RoleDeletionResult
    {
        return DB::transaction(function () use ($role): RoleDeletionResult {
            $role = Role::query()
                ->where('guard_name', 'web')
                ->lockForUpdate()
                ->findOrFail($role->id);

            if ($role->is_default) {
                return RoleDeletionResult::DefaultRole;
            }

            if ($role->users()->exists()) {
                return RoleDeletionResult::AssignedToUsers;
            }

            $role->delete();

            return RoleDeletionResult::Deleted;
        });
    }

    public function availablePermissions(Role $role): Collection
    {
        $assignedEntities = $role->permissions()
            ->pluck('name')
            ->map(fn (string $permission): string => $this->permissionEntity($permission))
            ->filter()
            ->unique();

        return Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->reject(
                fn (Permission $permission): bool => $assignedEntities->contains(
                    $this->permissionEntity($permission->name),
                ),
            );
    }

    private function load(Role $role): Role
    {
        return $role->load('permissions')->loadCount('users');
    }

    private function permissionEntity(string $permission): string
    {
        return str_contains($permission, '.')
            ? explode('.', $permission, 2)[0]
            : '';
    }
}
