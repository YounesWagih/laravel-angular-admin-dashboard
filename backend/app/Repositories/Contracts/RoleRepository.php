<?php

namespace App\Repositories\Contracts;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Collection;

interface RoleRepository
{
    /** @return Collection<int, Role> */
    public function allWithDetails(): Collection;

    /** @return Collection<int, Permission> */
    public function allPermissions(): Collection;

    public function findWebRole(int $roleId): Role;

    public function findDefaultWebRole(): ?Role;

    public function createWebRole(array $attributes): Role;

    public function update(Role $role, array $attributes): Role;

    public function withDetails(Role $role): Role;

    public function findWebRoleForUpdate(int $roleId): Role;

    public function clearDefaultExcept(Role $role): void;

    public function syncPermissions(Role $role, array $permissions): void;

    public function hasUsers(Role $role): bool;

    /** @return Collection<int, string> */
    public function assignedPermissionNames(Role $role): Collection;

    public function delete(Role $role): void;
}
