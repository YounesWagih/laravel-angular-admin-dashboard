<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserAuthenticationRepository
{
    public function invalidate(User $user): void;
}
