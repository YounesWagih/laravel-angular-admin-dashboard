<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\OrderRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class EloquentOrderRepository implements OrderRepository
{
    public function paginateVisibleTo(User $user, array $filters, bool $mayViewAll): LengthAwarePaginator
    {
        return Order::query()
            ->with(['user', 'items'])
            ->when(! $mayViewAll, fn (Builder $query): Builder => $query->whereBelongsTo($user))
            ->when(
                $mayViewAll && isset($filters['user_id']),
                fn (Builder $query): Builder => $query->where('user_id', $filters['user_id']),
            )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status): Builder => $query->where('status', $status),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $attributes): Order
    {
        return Order::query()->create($attributes);
    }

    public function createItem(Order $order, array $attributes): OrderItem
    {
        return $order->items()->create($attributes);
    }

    public function createHistory(Order $order, array $attributes): void
    {
        $order->histories()->create($attributes);
    }

    public function update(Order $order, array $attributes): Order
    {
        $order->update($attributes);

        return $order;
    }

    public function findByIdempotency(User $user, string $key): ?Order
    {
        return Order::query()
            ->whereBelongsTo($user)
            ->where('idempotency_key', $key)
            ->first();
    }

    public function findForUpdate(int $orderId): Order
    {
        return Order::query()->lockForUpdate()->findOrFail($orderId);
    }

    public function withDetails(Order $order): Order
    {
        return $order->load([
            'user',
            'items.product',
            'items.reservations.inventory.warehouse',
            'histories.actor',
        ]);
    }

    public function lockProducts(array $productIds): Collection
    {
        return Product::query()
            ->whereKey($productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }
}
