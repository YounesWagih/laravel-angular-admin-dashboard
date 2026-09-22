<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Models\InventoryReservation;
use App\Models\OrderItem;
use App\Models\WarehouseInventory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryReservation>
 */
class InventoryReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'warehouse_inventory_id' => WarehouseInventory::factory(),
            'quantity' => 1,
            'status' => ReservationStatus::Active,
        ];
    }
}
