<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => OrderStatus::Pending,
            'idempotency_key' => fake()->unique()->uuid(),
            'request_hash' => hash('sha256', fake()->uuid()),
            'subtotal' => '0.00',
            'cancellation_reason' => null,
        ];
    }
}
