<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => [
                'en' => Str::title(fake()->words(3, true)),
                'ar' => fake('ar_SA')->words(3, true),
            ],
            'description' => [
                'en' => fake()->paragraph(),
                'ar' => fake('ar_SA')->paragraph(),
            ],
            'image' => null,
            'price' => fake()->randomFloat(2, 10, 100000),
            'stock' => fake()->numberBetween(0, 500),
            'status' => fake()->randomElement(Status::cases()),
        ];
    }
}
