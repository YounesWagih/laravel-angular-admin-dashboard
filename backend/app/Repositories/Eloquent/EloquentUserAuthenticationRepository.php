<?php

namespace App\Repositories\Eloquent;

use App\Enums\Status;
use App\Models\User;
use App\Repositories\Contracts\UserAuthenticationRepository;
use DateTimeInterface;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

final class EloquentUserAuthenticationRepository implements UserAuthenticationRepository
{
    public function authenticate(string $email, string $password): ?User
    {
        $user = User::query()
            ->where('email', $email)
            ->where('status', Status::Active)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    public function createToken(User $user, string $name, DateTimeInterface $expiresAt): string
    {
        return $user->createToken($name, ['*'], $expiresAt)->plainTextToken;
    }

    public function revokeCurrentToken(User $user): void
    {
        $accessToken = $user->currentAccessToken();

        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        }
    }

    public function invalidate(User $user): void
    {
        $user->tokens()->delete();
    }
}
