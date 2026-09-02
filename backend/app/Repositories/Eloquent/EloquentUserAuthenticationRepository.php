<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserAuthenticationRepository;
use Illuminate\Support\Facades\DB;

final class EloquentUserAuthenticationRepository implements UserAuthenticationRepository
{
    public function invalidate(User $user): void
    {
        DB::connection(config('session.connection'))
            ->table(config('session.table'))
            ->where('user_id', $user->id)
            ->delete();

        $user->tokens()->delete();
    }
}
