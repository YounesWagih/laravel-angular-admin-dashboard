<?php

namespace App\Services;

use App\Repositories\Contracts\DashboardRepository;

final class DashboardService
{
    public function __construct(private readonly DashboardRepository $dashboard) {}

    public function counts(): array
    {
        return $this->dashboard->counts();
    }
}
