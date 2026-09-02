<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\DashboardRepository;

final class EloquentDashboardRepository implements DashboardRepository
{
    public function counts(): array
    {
        return [
            'users_count' => User::query()->count(),
            'products_count' => Product::query()->count(),
            'categories_count' => Category::query()->count(),
            'roles_count' => Role::query()->count(),
        ];
    }
}
