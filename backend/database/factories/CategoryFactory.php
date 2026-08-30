<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => [
                'en' => Str::title(fake()->unique()->words(2, true)),
                'ar' => fake('ar_SA')->unique()->words(2, true),
            ],
            'description' => [
                'en' => fake()->sentence(),
                'ar' => fake('ar_SA')->sentence(),
            ],
        ];
    }
}
