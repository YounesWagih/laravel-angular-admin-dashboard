<?php

namespace App\Services;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class UserService
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

    public function create(array $data): User
    {
        $role = $this->role($data['role_id']);
        unset($data['role_id']);

        $user = DB::transaction(function () use ($data, $role): User {
            $user = User::query()->create($data);
            $user->syncRoles([$role]);

            return $user;
        });

        return $user->load('roles');
    }

    public function update(User $user, array $data): ?User
    {
        $role = isset($data['role_id']) ? $this->role($data['role_id']) : null;
        unset($data['role_id']);

        $updatedUser = DB::transaction(function () use ($user, $data, $role): ?User {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $nextType = isset($data['type'])
                ? UserType::from($data['type'])
                : $user->type;

            if ($user->type === UserType::Admin
                && $user->status === Status::Active
                && $nextType !== UserType::Admin
                && $this->isFinalActiveAdmin()) {
                return null;
            }

            $user->update($data);

            if ($role) {
                $user->syncRoles([$role]);
            }

            return $user;
        });

        return $updatedUser?->load('roles');
    }

    public function updateStatus(User $user, Status $status): ?User
    {
        $updatedUser = DB::transaction(function () use ($user, $status): ?User {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($user->status === $status) {
                return $user;
            }

            if ($user->type === UserType::Admin
                && $user->status === Status::Active
                && $status === Status::Inactive
                && $this->isFinalActiveAdmin()) {
                return null;
            }

            $user->update(['status' => $status]);

            if ($status === Status::Inactive) {
                $this->invalidateAuthentication($user);
            }

            return $user;
        });

        return $updatedUser?->load('roles');
    }

    private function role(int $roleId): Role
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->findOrFail($roleId);
    }

    private function isFinalActiveAdmin(): bool
    {
        return User::query()
            ->where('type', UserType::Admin)
            ->where('status', Status::Active)
            ->lockForUpdate()
            ->count() === 1;
    }

    private function invalidateAuthentication(User $user): void
    {
        DB::connection(config('session.connection'))
            ->table(config('session.table'))
            ->where('user_id', $user->id)
            ->delete();

        $user->tokens()->delete();
    }
}
