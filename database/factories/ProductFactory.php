<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'sku' => strtoupper(fake()->bothify('SKU-####??')),
            'price' => fake()->randomFloat(2, 100, 2500),
            'stock_qty' => fake()->numberBetween(5, 50),
            'low_stock_threshold' => 5,
            'is_active' => true,
        ];
    }
}
