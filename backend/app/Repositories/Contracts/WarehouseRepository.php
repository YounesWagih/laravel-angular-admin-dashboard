<?php

namespace App\Repositories\Contracts;

use App\Models\Warehouse;
use Illuminate\Pagination\LengthAwarePaginator;

interface WarehouseRepository
{
    public function paginate(array $filters): LengthAwarePaginator;

    public function create(array $attributes): Warehouse;

    public function update(Warehouse $warehouse, array $attributes): Warehouse;

    public function withDetails(Warehouse $warehouse): Warehouse;

    public function findForUpdate(int $warehouseId): Warehouse;

    public function hasInventoryOrHistory(Warehouse $warehouse): bool;

    public function delete(Warehouse $warehouse): void;
}
