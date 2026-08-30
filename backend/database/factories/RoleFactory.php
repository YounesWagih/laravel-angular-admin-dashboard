<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Role> */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => Str::title(fake()->unique()->words(2, true)),
            'guard_name' => 'web',
            'description' => fake()->optional()->sentence(),
            'is_default' => false,
        ];
    }
}
