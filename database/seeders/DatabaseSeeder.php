<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Category\Models\Category;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Product\Models\Product;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create 5 Categories
        $categories = Category::factory()->count(5)->create();

        // 2. Create 5 Suppliers
        $suppliers = Supplier::factory()->count(5)->create();

        // 3. Create 50 Random Products
        Product::factory()->count(50)->make()->each(function ($product) use ($categories, $suppliers) {
            $product->category_id = $categories->random()->id;
            $product->supplier_id = $suppliers->random()->id;
            $product->save();
        });

        // 4. Create 3 SPECIFIC Products for Testing Search
        // This ensures you know exactly what to search for
        Product::create([
            'sku' => 'TEST-HEADPHONE-001',
            'name' => 'Sony Noise Cancelling Headphones',
            'description' => 'Best headphones for coding',
            'category_id' => $categories->first()->id,
            'supplier_id' => $suppliers->first()->id,
            'price' => 299.99,
            'cost_price' => 150.00,
            'quantity' => 50,
            'min_stock' => 10,
            'status' => 'active'
        ]);

        Product::create([
            'sku' => 'TEST-LOW-STOCK',
            'name' => 'Low Stock Gaming Mouse',
            'description' => 'A mouse that is running out',
            'category_id' => $categories->first()->id,
            'supplier_id' => $suppliers->first()->id,
            'price' => 50.00,
            'cost_price' => 25.00,
            'quantity' => 2, // Quantity (2) is less than Min Stock (5) -> LOW STOCK
            'min_stock' => 5,
            'status' => 'active'
        ]);
        
        Product::create([
            'sku' => 'TEST-OUT-STOCK',
            'name' => 'Sold Out Graphic Card',
            'description' => 'Mining rig gpu',
            'category_id' => $categories->first()->id,
            'supplier_id' => $suppliers->first()->id,
            'price' => 1500.00,
            'cost_price' => 800.00,
            'quantity' => 0, // Quantity 0 -> OUT OF STOCK
            'min_stock' => 5,
            'status' => 'active'
        ]);
    }
}