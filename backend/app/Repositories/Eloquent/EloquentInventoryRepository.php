<?php

namespace App\Repositories\Eloquent;

use App\Enums\ReservationStatus;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Models\WarehouseInventory;
use App\Repositories\Contracts\InventoryRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class EloquentInventoryRepository implements InventoryRepository
{
    public function paginate(array $filters, string $locale): LengthAwarePaginator
    {
        return WarehouseInventory::query()
            ->with(['warehouse', 'product'])
            ->when(
                $filters['warehouse_id'] ?? null,
                fn (Builder $query, int $warehouseId): Builder => $query->where('warehouse_id', $warehouseId),
            )
            ->when(
                $filters['product_id'] ?? null,
                fn (Builder $query, int $productId): Builder => $query->where('product_id', $productId),
            )
            ->when($filters['search'] ?? null, function (Builder $query, string $search) use ($locale): void {
                $query->whereHas('product', fn (Builder $productQuery): Builder => $productQuery
                    ->where("name->{$locale}", 'like', "%{$search}%"));
            })
            ->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function lockOrCreate(int $warehouseId, int $productId): WarehouseInventory
    {
        WarehouseInventory::query()->insertOrIgnore([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'on_hand' => 0,
            'reserved' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return WarehouseInventory::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function lockPair(array $warehouseIds, int $productId): Collection
    {
        foreach (array_values(array_unique($warehouseIds)) as $warehouseId) {
            WarehouseInventory::query()->insertOrIgnore([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'on_hand' => 0,
                'reserved' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return WarehouseInventory::query()
            ->where('product_id', $productId)
            ->whereIn('warehouse_id', $warehouseIds)
            ->orderBy('warehouse_id')
            ->lockForUpdate()
            ->get();
    }

    public function lockAllocatableForProducts(array $productIds): Collection
    {
        return WarehouseInventory::query()
            ->select('warehouse_inventories.*')
            ->join('warehouses', 'warehouses.id', '=', 'warehouse_inventories.warehouse_id')
            ->where('warehouses.is_active', true)
            ->whereIn('warehouse_inventories.product_id', $productIds)
            ->with('warehouse')
            ->orderBy('warehouse_inventories.product_id')
            ->orderBy('warehouses.priority')
            ->orderBy('warehouse_inventories.warehouse_id')
            ->lockForUpdate()
            ->get();
    }

    public function lockByIds(array $inventoryIds): Collection
    {
        return WarehouseInventory::query()
            ->whereKey($inventoryIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    public function lockActiveReservations(Order $order): Collection
    {
        return InventoryReservation::query()
            ->where('status', ReservationStatus::Active)
            ->whereHas('orderItem', fn (Builder $query): Builder => $query->where('order_id', $order->id))
            ->orderBy('warehouse_inventory_id')
            ->lockForUpdate()
            ->get();
    }

    public function updateBalance(WarehouseInventory $inventory, int $onHand, int $reserved): void
    {
        $inventory->update(['on_hand' => $onHand, 'reserved' => $reserved]);
    }

    public function createReservation(OrderItem $item, WarehouseInventory $inventory, int $quantity): InventoryReservation
    {
        return InventoryReservation::query()->create([
            'order_item_id' => $item->id,
            'warehouse_inventory_id' => $inventory->id,
            'quantity' => $quantity,
            'status' => ReservationStatus::Active,
        ]);
    }

    public function updateReservationStatus(InventoryReservation $reservation, string $status): void
    {
        $reservation->update(['status' => $status]);
    }

    public function createMovement(array $attributes): StockMovement
    {
        return StockMovement::query()->create($attributes);
    }

    public function withDetails(WarehouseInventory $inventory): WarehouseInventory
    {
        return $inventory->load(['warehouse', 'product']);
    }
}
