<?php

namespace App\Services;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\User;
use App\Repositories\Contracts\RoleRepository;
use App\Repositories\Contracts\TransactionManager;
use App\Repositories\Contracts\UserAuthenticationRepository;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Pagination\LengthAwarePaginator;

final class UserService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly RoleRepository $roles,
        private readonly UserAuthenticationRepository $authentication,
        private readonly TransactionManager $transactions,
    ) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->users->paginate($filters);
    }

    public function details(User $user): User
    {
        return $this->users->withRoles($user);
    }

    public function authenticationDetails(User $user): User
    {
        return $this->users->withAuthenticationContext($user);
    }

    public function create(array $data): User
    {
        $role = $this->roles->findWebRole($data['role_id']);
        unset($data['role_id']);

        $user = $this->transactions->run(function () use ($data, $role): User {
            $user = $this->users->create($data);
            $this->users->syncRole($user, $role);

            return $user;
        });

        return $this->users->withRoles($user);
    }

    public function update(User $user, array $data): ?User
    {
        $role = isset($data['role_id']) ? $this->roles->findWebRole($data['role_id']) : null;
        unset($data['role_id']);

        $updatedUser = $this->transactions->run(function () use ($user, $data, $role): ?User {
            $user = $this->users->findForUpdate($user->id);
            $nextType = isset($data['type'])
                ? UserType::from($data['type'])
                : $user->type;

            if ($user->type === UserType::Admin
                && $user->status === Status::Active
                && $nextType !== UserType::Admin
                && $this->isFinalActiveAdmin()) {
                return null;
            }

            $this->users->update($user, $data);

            if ($role) {
                $this->users->syncRole($user, $role);
            }

            return $user;
        });

        return $updatedUser ? $this->users->withRoles($updatedUser) : null;
    }

    public function updateStatus(User $user, Status $status): ?User
    {
        $updatedUser = $this->transactions->run(function () use ($user, $status): ?User {
            $user = $this->users->findForUpdate($user->id);

            if ($user->status === $status) {
                return $user;
            }

            if ($user->type === UserType::Admin
                && $user->status === Status::Active
                && $status === Status::Inactive
                && $this->isFinalActiveAdmin()) {
                return null;
            }

            $this->users->update($user, ['status' => $status]);

            if ($status === Status::Inactive) {
                $this->authentication->invalidate($user);
            }

            return $user;
        });

        return $updatedUser ? $this->users->withRoles($updatedUser) : null;
    }

    private function isFinalActiveAdmin(): bool
    {
        return $this->users->activeAdminCountForUpdate() === 1;
    }
}
