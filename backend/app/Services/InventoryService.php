<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\DomainConflictException;
use App\Models\User;
use App\Models\WarehouseInventory;
use App\Repositories\Contracts\InventoryRepository;
use App\Repositories\Contracts\TransactionManager;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

final class InventoryService
{
    public function __construct(
        private readonly InventoryRepository $inventories,
        private readonly TransactionManager $transactions,
    ) {}

    public function paginate(array $filters, string $locale): LengthAwarePaginator
    {
        return $this->inventories->paginate($filters, $locale);
    }

    public function adjust(User $actor, array $data): WarehouseInventory
    {
        return $this->transactions->run(function () use ($actor, $data): WarehouseInventory {
            $inventory = $this->inventories->lockOrCreate($data['warehouse_id'], $data['product_id']);
            $onHand = $inventory->on_hand + $data['quantity_delta'];

            if ($onHand < $inventory->reserved) {
                throw new DomainConflictException(
                    'reserved_stock_conflict',
                    __('The adjustment would reduce stock below the reserved quantity.'),
                    ['available' => $inventory->on_hand - $inventory->reserved],
                );
            }

            if ($onHand > WarehouseInventory::MAX_QUANTITY) {
                throw new DomainConflictException(
                    'inventory_capacity_exceeded',
                    __('The adjustment would exceed the inventory capacity.'),
                    ['maximum' => WarehouseInventory::MAX_QUANTITY],
                );
            }

            $this->inventories->updateBalance($inventory, $onHand, $inventory->reserved);
            $this->inventories->createMovement([
                'warehouse_inventory_id' => $inventory->id,
                'actor_id' => $actor->id,
                'type' => StockMovementType::Adjustment,
                'on_hand_delta' => $data['quantity_delta'],
                'reserved_delta' => 0,
                'reason' => $data['reason'],
            ]);

            return $this->inventories->withDetails($inventory);
        });
    }

    public function transfer(User $actor, array $data): array
    {
        return $this->transactions->run(function () use ($actor, $data): array {
            $balances = $this->inventories
                ->lockPair([$data['from_warehouse_id'], $data['to_warehouse_id']], $data['product_id'])
                ->keyBy('warehouse_id');
            $source = $balances->get($data['from_warehouse_id']);
            $destination = $balances->get($data['to_warehouse_id']);

            if (! $source || ! $destination || $source->on_hand - $source->reserved < $data['quantity']) {
                throw new DomainConflictException(
                    'insufficient_available_stock',
                    __('The source warehouse does not have enough available stock.'),
                    ['available' => $source ? $source->on_hand - $source->reserved : 0],
                );
            }

            if ($destination->on_hand + $data['quantity'] > WarehouseInventory::MAX_QUANTITY) {
                throw new DomainConflictException(
                    'inventory_capacity_exceeded',
                    __('The transfer would exceed the destination inventory capacity.'),
                    ['maximum' => WarehouseInventory::MAX_QUANTITY],
                );
            }

            $this->inventories->updateBalance($source, $source->on_hand - $data['quantity'], $source->reserved);
            $this->inventories->updateBalance($destination, $destination->on_hand + $data['quantity'], $destination->reserved);

            $correlationId = (string) Str::uuid();
            $common = [
                'actor_id' => $actor->id,
                'reserved_delta' => 0,
                'correlation_id' => $correlationId,
                'reason' => $data['reason'],
            ];
            $this->inventories->createMovement([
                ...$common,
                'warehouse_inventory_id' => $source->id,
                'type' => StockMovementType::TransferOut,
                'on_hand_delta' => -$data['quantity'],
            ]);
            $this->inventories->createMovement([
                ...$common,
                'warehouse_inventory_id' => $destination->id,
                'type' => StockMovementType::TransferIn,
                'on_hand_delta' => $data['quantity'],
            ]);

            return [
                'correlation_id' => $correlationId,
                'source' => $this->inventories->withDetails($source),
                'destination' => $this->inventories->withDetails($destination),
            ];
        });
    }
}
