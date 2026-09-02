<?php

namespace App\Repositories\Contracts;

interface DashboardRepository
{
    /**
     * @return array{
     *     users_count: int,
     *     products_count: int,
     *     categories_count: int,
     *     roles_count: int
     * }
     */
    public function counts(): array;
}
