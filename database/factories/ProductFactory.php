<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Modules\Product\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $price = $this->faker->randomFloat(2, 10, 2000);
        
        return [
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####-????')),
            'name' => ucwords($this->faker->words(3, true)),
            'description' => $this->faker->paragraph(),
            'price' => $price,
            'cost_price' => $price * 0.70,
            'quantity' => $this->faker->numberBetween(0, 100),
            'min_stock' => $this->faker->numberBetween(5, 15),
            'max_stock' => 100,
            'location' => 'Zone ' . $this->faker->randomLetter(),
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'inactive']),
        ];
    }
}