<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use DateTimeInterface;

interface UserAuthenticationRepository
{
    public function authenticate(string $email, string $password): ?User;

    public function createToken(User $user, string $name, DateTimeInterface $expiresAt): string;

    public function revokeCurrentToken(User $user): void;

    public function invalidate(User $user): void;
}
