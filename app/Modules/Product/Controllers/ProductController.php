<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Requests\ProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get all products
     * 
     * GET /api/products
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'supplier']);

        // Filter by category
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by supplier
        if ($request->has('supplier_id') && !empty($request->supplier_id)) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['active', 'inactive', 'discontinued'])) {
            $query->where('status', $request->status);
        }

        // Filter by stock status
        if ($request->has('stock_status')) {
            switch ($request->stock_status) {
                case 'low_stock':
                    $query->lowStock();
                    break;
                case 'out_of_stock':
                    $query->outOfStock();
                    break;
                case 'in_stock':
                    $query->whereColumn('quantity', '>', 'min_stock');
                    break;
            }
        }

        // Search by name or SKU
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->get();

        // Add computed attributes
        $products->transform(function ($product) {
            $product->stock_status = $product->stock_status;
            $product->inventory_value = $product->inventory_value;
            return $product;
        });

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data' => [
                'products' => $products,
                'total' => $products->count()
            ]
        ], 200);
    }

    /**
     * Create new product
     * 
     * POST /api/products
     */
    public function store(ProductRequest $request): JsonResponse
    {
        $product = Product::create([
            'sku' => $request->sku,
            'name' => $request->name,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'supplier_id' => $request->supplier_id,
            'price' => $request->price,
            'cost_price' => $request->cost_price,
            'quantity' => $request->quantity ?? 0,
            'min_stock' => $request->min_stock ?? 10,
            'max_stock' => $request->max_stock ?? 100,
            'location' => $request->location,
            'image' => $request->image,
            'status' => $request->status ?? 'active',
        ]);

        // Load relationships
        $product->load(['category', 'supplier']);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => [
                'product' => $product
            ]
        ], 201);
    }

    /**
     * Get single product
     * 
     * GET /api/products/{id}
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::with(['category', 'supplier'])->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Add computed attributes
        $product->stock_status = $product->stock_status;
        $product->inventory_value = $product->inventory_value;

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully',
            'data' => [
                'product' => $product
            ]
        ], 200);
    }

    /**
     * Update product
     * 
     * PUT /api/products/{id}
     */
    public function update(ProductRequest $request, string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $product->update([
            'sku' => $request->sku,
            'name' => $request->name,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'supplier_id' => $request->supplier_id,
            'price' => $request->price,
            'cost_price' => $request->cost_price,
            'quantity' => $request->quantity ?? $product->quantity,
            'min_stock' => $request->min_stock ?? $product->min_stock,
            'max_stock' => $request->max_stock ?? $product->max_stock,
            'location' => $request->location,
            'image' => $request->image,
            'status' => $request->status ?? $product->status,
        ]);

        // Load relationships
        $product->load(['category', 'supplier']);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => [
                'product' => $product
            ]
        ], 200);
    }

    /**
     * Delete product
     * 
     * DELETE /api/products/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Check if product has stock
        if ($product->quantity > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete product with existing stock. Please adjust stock to 0 first.',
            ], 400);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ], 200);
    }

    /**
     * Get low stock products
     * 
     * GET /api/products/alerts/low-stock
     */
    public function lowStock(): JsonResponse
    {
        $products = Product::with(['category', 'supplier'])
            ->lowStock()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Low stock products retrieved successfully',
            'data' => [
                'products' => $products,
                'total' => $products->count()
            ]
        ], 200);
    }

    /**
     * Get out of stock products
     * 
     * GET /api/products/alerts/out-of-stock
     */
    public function outOfStock(): JsonResponse
    {
        $products = Product::with(['category', 'supplier'])
            ->outOfStock()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Out of stock products retrieved successfully',
            'data' => [
                'products' => $products,
                'total' => $products->count()
            ]
        ], 200);
    }
}