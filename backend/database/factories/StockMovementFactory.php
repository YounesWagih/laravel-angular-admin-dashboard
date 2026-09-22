<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use App\Models\WarehouseInventory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_inventory_id' => WarehouseInventory::factory(),
            'actor_id' => null,
            'order_id' => null,
            'type' => StockMovementType::Adjustment,
            'on_hand_delta' => 10,
            'reserved_delta' => 0,
            'correlation_id' => null,
            'reason' => fake()->sentence(),
        ];
    }
}
