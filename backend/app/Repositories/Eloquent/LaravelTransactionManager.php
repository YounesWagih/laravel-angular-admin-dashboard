<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\TransactionManager;
use Closure;
use Illuminate\Support\Facades\DB;

final class LaravelTransactionManager implements TransactionManager
{
    public function run(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
