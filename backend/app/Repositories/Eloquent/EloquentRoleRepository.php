<?php

namespace App\Repositories\Eloquent;

use App\Models\Permission;
use App\Models\Role;
use App\Repositories\Contracts\RoleRepository;
use Illuminate\Support\Collection;

final class EloquentRoleRepository implements RoleRepository
{
    public function allWithDetails(): Collection
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->withCount('users')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function allPermissions(): Collection
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();
    }

    public function findWebRole(int $roleId): Role
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->findOrFail($roleId);
    }

    public function findDefaultWebRole(): ?Role
    {
        return Role::query()
            ->where('is_default', true)
            ->where('guard_name', 'web')
            ->first();
    }

    public function createWebRole(array $attributes): Role
    {
        return Role::query()->create([
            ...$attributes,
            'guard_name' => 'web',
        ]);
    }

    public function update(Role $role, array $attributes): Role
    {
        $role->update($attributes);

        return $role;
    }

    public function withDetails(Role $role): Role
    {
        return $role->load('permissions')->loadCount('users');
    }

    public function findWebRoleForUpdate(int $roleId): Role
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->lockForUpdate()
            ->findOrFail($roleId);
    }

    public function clearDefaultExcept(Role $role): void
    {
        Role::query()
            ->where('guard_name', 'web')
            ->whereKeyNot($role->getKey())
            ->update(['is_default' => false]);
    }

    public function syncPermissions(Role $role, array $permissions): void
    {
        $role->syncPermissions($permissions);
    }

    public function hasUsers(Role $role): bool
    {
        return $role->users()->exists();
    }

    public function assignedPermissionNames(Role $role): Collection
    {
        return $role->permissions()->pluck('name');
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }
}
