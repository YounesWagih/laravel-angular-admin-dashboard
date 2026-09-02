<?php

namespace App\Repositories\Contracts;

use Closure;

interface TransactionManager
{
    public function run(Closure $callback): mixed;
}
