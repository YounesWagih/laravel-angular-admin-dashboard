<?php

namespace App\Services;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

final class RegistrationService
{
    public function register(array $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            $defaultRole = Role::query()
                ->where('is_default', true)
                ->where('guard_name', 'web')
                ->first();

            if (! $defaultRole) {
                throw new LogicException('A default role must be configured before users can register.');
            }

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'type' => UserType::User,
                'status' => Status::Active,
            ]);

            $user->syncRoles([$defaultRole]);

            return $user;
        });

        return $user->load('roles');
    }
}
