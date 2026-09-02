<?php

namespace App\Services;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\User;
use App\Repositories\Contracts\RoleRepository;
use App\Repositories\Contracts\TransactionManager;
use App\Repositories\Contracts\UserRepository;
use LogicException;

final class RegistrationService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly RoleRepository $roles,
        private readonly TransactionManager $transactions,
    ) {}

    public function register(array $data): User
    {
        $user = $this->transactions->run(function () use ($data): User {
            $defaultRole = $this->roles->findDefaultWebRole();

            if (! $defaultRole) {
                throw new LogicException(__('A default role must be configured before users can register.'));
            }

            $user = $this->users->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'type' => UserType::User,
                'status' => Status::Active,
            ]);

            $this->users->syncRole($user, $defaultRole);

            return $user;
        });

        return $this->users->withAuthenticationContext($user);
    }
}
