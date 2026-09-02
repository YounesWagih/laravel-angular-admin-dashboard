<?php

namespace App\Repositories\Eloquent;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentUserRepository implements UserRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(
                $filters['type'] ?? null,
                fn (Builder $query, string $type): Builder => $query->where('type', $type),
            )
            ->when(
                $filters['role_id'] ?? null,
                fn (Builder $query, int $roleId): Builder => $query->whereHas(
                    'roles',
                    fn (Builder $query): Builder => $query->whereKey($roleId),
                ),
            )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status): Builder => $query->where('status', $status),
            )
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 10);
    }

    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user;
    }

    public function withRoles(User $user): User
    {
        return $user->load('roles');
    }

    public function withAuthenticationContext(User $user): User
    {
        $user->load('roles');
        $user->setRelation(
            'effectivePermissions',
            $user->type === UserType::Admin ? collect() : $user->getAllPermissions(),
        );

        return $user;
    }

    public function findForUpdate(int $userId): User
    {
        return User::query()->lockForUpdate()->findOrFail($userId);
    }

    public function syncRole(User $user, Role $role): void
    {
        $user->syncRoles([$role]);
    }

    public function activeAdminCountForUpdate(): int
    {
        return User::query()
            ->where('type', UserType::Admin)
            ->where('status', Status::Active)
            ->lockForUpdate()
            ->count();
    }
}
