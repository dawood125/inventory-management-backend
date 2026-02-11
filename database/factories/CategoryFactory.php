<?php

namespace Database\Factories;

use App\Modules\Category\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Category\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Electronics', 'Furniture', 'Clothing', 'Office Supplies', 'Groceries',
            'Books', 'Toys', 'Health', 'Beauty', 'Automotive',
            'Sports', 'Outdoors', 'Home & Garden', 'Pet Supplies', 'Tools',
            'Jewelry', 'Music', 'Games', 'Industrial', 'Computers'
        ];
        return [
            'name' => $this->faker->unique()->randomElement($categories),
            'description' => $this->faker->sentence(),
        ];
    }
}
