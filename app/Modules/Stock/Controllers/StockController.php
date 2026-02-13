<?php

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Models\StockMovement;
use App\Modules\Stock\Requests\StockMovementRequest;
use App\Modules\Product\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * Get all stock movements
     * 
     * GET /api/stock-movements
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockMovement::with(['product', 'createdBy']);

        // Filter by product
        if ($request->has('product_id') && !empty($request->product_id)) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by type
        if ($request->has('type') && in_array($request->type, ['in', 'out', 'adjustment'])) {
            $query->where('type', $request->type);
        }

        // Date filter
        if ($request->has('from_date') && !empty($request->from_date)) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && !empty($request->to_date)) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $movements = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Stock movements retrieved successfully',
            'data' => [
                'movements' => $movements,
                'total' => $movements->count()
            ]
        ], 200);
    }

    /**
     * Create manual stock movement
     * 
     * POST /api/stock-movements
     */
    public function store(StockMovementRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $product = Product::findOrFail($request->product_id);
            $stockBefore = $product->quantity;

            // Calculate new stock
            switch ($request->type) {
                case 'in':
                    $stockAfter = $stockBefore + $request->quantity;
                    break;
                case 'out':
                    if ($stockBefore < $request->quantity) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Insufficient stock. Available: ' . $stockBefore,
                        ], 400);
                    }
                    $stockAfter = $stockBefore - $request->quantity;
                    break;
                case 'adjustment':
                    $stockAfter = $request->quantity;
                    break;
                default:
                    $stockAfter = $stockBefore;
            }

            // Create movement record
            $movement = StockMovement::create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'type' => $request->type,
                'quantity' => $request->quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reason' => $request->reason,
                'reference' => $request->reference,
                'created_by' => $request->user()->id,
            ]);

            // Update product stock
            $product->update(['quantity' => $stockAfter]);

            DB::commit();

            $movement->load(['product', 'createdBy']);

            return response()->json([
                'success' => true,
                'message' => 'Stock movement created successfully',
                'data' => [
                    'movement' => $movement
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create stock movement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single stock movement
     * 
     * GET /api/stock-movements/{id}
     */
    public function show(string $id): JsonResponse
    {
        $movement = StockMovement::with(['product', 'createdBy'])->find($id);

        if (!$movement) {
            return response()->json([
                'success' => false,
                'message' => 'Stock movement not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Stock movement retrieved successfully',
            'data' => [
                'movement' => $movement
            ]
        ], 200);
    }

    /**
     * Get stock movement statistics
     * 
     * GET /api/stock-movements/stats
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_movements' => StockMovement::count(),
            'stock_in_count' => StockMovement::stockIn()->count(),
            'stock_out_count' => StockMovement::stockOut()->count(),
            'adjustment_count' => StockMovement::adjustment()->count(),
            'today_movements' => StockMovement::whereDate('created_at', today())->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Stock movement statistics retrieved successfully',
            'data' => [
                'stats' => $stats
            ]
        ], 200);
    }

    /**
     * Get product stock history
     * 
     * GET /api/stock-movements/product/{productId}/history
     */
    public function productHistory(string $productId): JsonResponse
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $movements = StockMovement::with('createdBy')
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Product stock history retrieved successfully',
            'data' => [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'current_stock' => $product->quantity,
                ],
                'movements' => $movements,
                'total' => $movements->count()
            ]
        ], 200);
    }
}
