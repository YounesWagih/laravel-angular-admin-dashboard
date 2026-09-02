<?php

namespace App\Services;

use App\Enums\RoleDeletionResult;
use App\Models\Permission;
use App\Models\Role;
use App\Repositories\Contracts\RoleRepository;
use App\Repositories\Contracts\TransactionManager;
use Illuminate\Support\Collection;

final class RoleService
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly TransactionManager $transactions,
    ) {}

    public function getAll(): Collection
    {
        return $this->roles->allWithDetails();
    }

    public function getAllPermissions(): Collection
    {
        return $this->roles->allPermissions();
    }

    public function create(array $data): Role
    {
        $role = $this->roles->createWebRole($data);

        return $this->details($role);
    }

    public function update(Role $role, array $data): Role
    {
        $role = $this->roles->update($role, $data);

        return $this->details($role);
    }

    public function setDefault(Role $role): Role
    {
        $role = $this->transactions->run(function () use ($role): Role {
            $role = $this->roles->findWebRoleForUpdate($role->id);

            $this->roles->clearDefaultExcept($role);

            $this->roles->update($role, ['is_default' => true]);

            return $role;
        });

        return $this->details($role);
    }

    public function syncPermissions(Role $role, array $permissions): Role
    {
        $role = $this->transactions->run(function () use ($role, $permissions): Role {
            $role = $this->roles->findWebRoleForUpdate($role->id);

            $this->roles->syncPermissions($role, $permissions);

            return $role;
        });

        return $this->details($role);
    }

    public function delete(Role $role): RoleDeletionResult
    {
        return $this->transactions->run(function () use ($role): RoleDeletionResult {
            $role = $this->roles->findWebRoleForUpdate($role->id);

            if ($role->is_default) {
                return RoleDeletionResult::DefaultRole;
            }

            if ($this->roles->hasUsers($role)) {
                return RoleDeletionResult::AssignedToUsers;
            }

            $this->roles->delete($role);

            return RoleDeletionResult::Deleted;
        });
    }

    public function availablePermissions(Role $role): Collection
    {
        $assignedEntities = $this->roles->assignedPermissionNames($role)
            ->map(fn (string $permission): string => $this->permissionEntity($permission))
            ->filter()
            ->unique();

        return $this->roles->allPermissions()
            ->reject(
                fn (Permission $permission): bool => $assignedEntities->contains(
                    $this->permissionEntity($permission->name),
                ),
            );
    }

    private function details(Role $role): Role
    {
        return $this->roles->withDetails($role);
    }

    private function permissionEntity(string $permission): string
    {
        return str_contains($permission, '.')
            ? explode('.', $permission, 2)[0]
            : '';
    }
}
