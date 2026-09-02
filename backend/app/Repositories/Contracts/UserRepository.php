<?php

namespace App\Repositories\Contracts;

use App\Models\Role;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepository
{
    public function paginate(array $filters): LengthAwarePaginator;

    public function create(array $attributes): User;

    public function update(User $user, array $attributes): User;

    public function withRoles(User $user): User;

    public function withAuthenticationContext(User $user): User;

    public function findForUpdate(int $userId): User;

    public function syncRole(User $user, Role $role): void;

    public function activeAdminCountForUpdate(): int;
}
