<?php

namespace Database\Factories;

use App\Enums\PermissionName;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Permission> */
class PermissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(PermissionName::cases())->value,
            'guard_name' => 'web',
        ];
    }
}
