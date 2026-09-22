<?php

namespace App\Repositories\Contracts;

use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Models\WarehouseInventory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface InventoryRepository
{
    public function paginate(array $filters, string $locale): LengthAwarePaginator;

    public function lockOrCreate(int $warehouseId, int $productId): WarehouseInventory;

    /** @return Collection<int, WarehouseInventory> */
    public function lockPair(array $warehouseIds, int $productId): Collection;

    /** @return Collection<int, WarehouseInventory> */
    public function lockAllocatableForProducts(array $productIds): Collection;

    /** @return Collection<int, WarehouseInventory> */
    public function lockByIds(array $inventoryIds): Collection;

    /** @return Collection<int, InventoryReservation> */
    public function lockActiveReservations(Order $order): Collection;

    public function updateBalance(WarehouseInventory $inventory, int $onHand, int $reserved): void;

    public function createReservation(OrderItem $item, WarehouseInventory $inventory, int $quantity): InventoryReservation;

    public function updateReservationStatus(InventoryReservation $reservation, string $status): void;

    public function createMovement(array $attributes): StockMovement;

    public function withDetails(WarehouseInventory $inventory): WarehouseInventory;
}
