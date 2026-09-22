<?php

namespace App\Repositories\Eloquent;

use App\Models\Warehouse;
use App\Repositories\Contracts\WarehouseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentWarehouseRepository implements WarehouseRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Warehouse::query()
            ->withCount('inventories')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when(
                array_key_exists('is_active', $filters),
                fn (Builder $query): Builder => $query->where('is_active', $filters['is_active']),
            )
            ->orderBy('priority')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $attributes): Warehouse
    {
        return Warehouse::query()->create($attributes);
    }

    public function update(Warehouse $warehouse, array $attributes): Warehouse
    {
        $warehouse->update($attributes);

        return $warehouse->loadCount('inventories');
    }

    public function withDetails(Warehouse $warehouse): Warehouse
    {
        return $warehouse->loadCount('inventories');
    }

    public function findForUpdate(int $warehouseId): Warehouse
    {
        return Warehouse::query()->lockForUpdate()->findOrFail($warehouseId);
    }

    public function hasInventoryOrHistory(Warehouse $warehouse): bool
    {
        return $warehouse->inventories()->exists();
    }

    public function delete(Warehouse $warehouse): void
    {
        $warehouse->delete();
    }
}
