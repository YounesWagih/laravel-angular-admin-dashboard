<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;

final class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'users_count' => User::query()->count(),
                'products_count' => Product::query()->count(),
                'categories_count' => Category::query()->count(),
                'roles_count' => Role::query()->count(),
            ],
        ]);
    }
}
