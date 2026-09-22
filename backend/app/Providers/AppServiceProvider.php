<?php

namespace App\Providers;

use App\Enums\UserType;
use App\Models\User;
use App\Repositories\Contracts\CategoryRepository;
use App\Repositories\Contracts\DashboardRepository;
use App\Repositories\Contracts\InventoryRepository;
use App\Repositories\Contracts\OrderRepository;
use App\Repositories\Contracts\ProductRepository;
use App\Repositories\Contracts\RoleRepository;
use App\Repositories\Contracts\TransactionManager;
use App\Repositories\Contracts\UserAuthenticationRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\WarehouseRepository;
use App\Repositories\Eloquent\EloquentCategoryRepository;
use App\Repositories\Eloquent\EloquentDashboardRepository;
use App\Repositories\Eloquent\EloquentInventoryRepository;
use App\Repositories\Eloquent\EloquentOrderRepository;
use App\Repositories\Eloquent\EloquentProductRepository;
use App\Repositories\Eloquent\EloquentRoleRepository;
use App\Repositories\Eloquent\EloquentUserAuthenticationRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\Eloquent\EloquentWarehouseRepository;
use App\Repositories\Eloquent\LaravelTransactionManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CategoryRepository::class, EloquentCategoryRepository::class);
        $this->app->bind(ProductRepository::class, EloquentProductRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(RoleRepository::class, EloquentRoleRepository::class);
        $this->app->bind(UserAuthenticationRepository::class, EloquentUserAuthenticationRepository::class);
        $this->app->bind(DashboardRepository::class, EloquentDashboardRepository::class);
        $this->app->bind(WarehouseRepository::class, EloquentWarehouseRepository::class);
        $this->app->bind(InventoryRepository::class, EloquentInventoryRepository::class);
        $this->app->bind(OrderRepository::class, EloquentOrderRepository::class);
        $this->app->bind(TransactionManager::class, LaravelTransactionManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(
            static fn (User $user, string $ability): ?bool => $user->type === UserType::Admin ? true : null,
        );
    }
}
