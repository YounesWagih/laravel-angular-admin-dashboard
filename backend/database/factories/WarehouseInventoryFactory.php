<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarehouseInventory>
 */
class WarehouseInventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'on_hand' => 100,
            'reserved' => 0,
        ];
    }

    public function withReserved(int $reserved): static
    {
        return $this->state(fn (): array => [
            'on_hand' => max(100, $reserved),
            'reserved' => $reserved,
        ]);
    }
}
