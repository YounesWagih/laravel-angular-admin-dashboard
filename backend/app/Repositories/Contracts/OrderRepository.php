<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface OrderRepository
{
    public function paginateVisibleTo(User $user, array $filters, bool $mayViewAll): LengthAwarePaginator;

    public function create(array $attributes): Order;

    public function createItem(Order $order, array $attributes): OrderItem;

    public function createHistory(Order $order, array $attributes): void;

    public function update(Order $order, array $attributes): Order;

    public function findByIdempotency(User $user, string $key): ?Order;

    public function findForUpdate(int $orderId): Order;

    public function withDetails(Order $order): Order;

    /** @return Collection<int, Product> */
    public function lockProducts(array $productIds): Collection;
}
