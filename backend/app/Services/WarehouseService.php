<?php

namespace App\Services;

use App\Exceptions\DomainConflictException;
use App\Models\Warehouse;
use App\Repositories\Contracts\TransactionManager;
use App\Repositories\Contracts\WarehouseRepository;
use Illuminate\Pagination\LengthAwarePaginator;

final class WarehouseService
{
    public function __construct(
        private readonly WarehouseRepository $warehouses,
        private readonly TransactionManager $transactions,
    ) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->warehouses->paginate($filters);
    }

    public function create(array $data): Warehouse
    {
        return $this->warehouses->create($data);
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        return $this->warehouses->update($warehouse, $data);
    }

    public function details(Warehouse $warehouse): Warehouse
    {
        return $this->warehouses->withDetails($warehouse);
    }

    public function delete(Warehouse $warehouse): void
    {
        $this->transactions->run(function () use ($warehouse): void {
            $warehouse = $this->warehouses->findForUpdate($warehouse->id);

            if ($this->warehouses->hasInventoryOrHistory($warehouse)) {
                throw new DomainConflictException(
                    'warehouse_in_use',
                    __('A warehouse with inventory or history cannot be deleted.'),
                );
            }

            $this->warehouses->delete($warehouse);
        });
    }
}
