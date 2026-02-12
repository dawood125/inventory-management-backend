<?php

namespace App\Modules\Order\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Requests\OrderRequest;
use App\Modules\Product\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Get all orders
     * 
     * GET /api/orders
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['items', 'supplier', 'createdBy']);

        // Filter by type
        if ($request->has('type') && in_array($request->type, ['purchase', 'sale'])) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['pending', 'processing', 'completed', 'cancelled'])) {
            $query->where('status', $request->status);
        }

        // Search by order number or customer name
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // Date filter
        if ($request->has('from_date') && !empty($request->from_date)) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && !empty($request->to_date)) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully',
            'data' => [
                'orders' => $orders,
                'total' => $orders->count()
            ]
        ], 200);
    }

    /**
     * Create new order
     * 
     * POST /api/orders
     */
    public function store(OrderRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Generate order number
            $orderNumber = Order::generateOrderNumber($request->type);

            // Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'type' => $request->type,
                'status' => 'pending',
                'supplier_id' => $request->type === 'purchase' ? $request->supplier_id : null,
                'customer_name' => $request->type === 'sale' ? $request->customer_name : null,
                'notes' => $request->notes,
                'created_by' => $request->user()->id,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            // Create order items
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Get price based on order type
                $price = $request->type === 'purchase' ? $product->cost_price : $product->price;
                $itemTotal = $item['quantity'] * $price;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'total' => $itemTotal,
                ]);

                $totalAmount += $itemTotal;
            }

            // Update order total
            $order->update(['total_amount' => $totalAmount]);

            DB::commit();

            // Load relationships
            $order->load(['items', 'supplier', 'createdBy']);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order' => $order
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single order
     * 
     * GET /api/orders/{id}
     */
    public function show(string $id): JsonResponse
    {
        $order = Order::with(['items', 'supplier', 'createdBy'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully',
            'data' => [
                'order' => $order
            ]
        ], 200);
    }

    /**
     * Update order status
     * 
     * PATCH /api/orders/{id}/status
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:pending,processing,completed,cancelled'],
        ]);

        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        // Don't allow changing completed or cancelled orders
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot change status of completed or cancelled orders',
            ], 400);
        }

        $oldStatus = $order->status;
        $newStatus = $request->status;

        // Update order status
        $order->update(['status' => $newStatus]);

        // TODO: Fire event when order is completed
        // We will add this in Stock module
        // if ($newStatus === 'completed') {
        //     event(new OrderCompleted($order));
        // }

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => [
                'order' => $order,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]
        ], 200);
    }

    /**
     * Delete order
     * 
     * DELETE /api/orders/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        // Only allow deleting pending orders
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending orders can be deleted',
            ], 400);
        }

        // Delete order (items will be deleted automatically due to cascade)
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully',
        ], 200);
    }

    /**
     * Get order statistics
     * 
     * GET /api/orders/stats
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::pending()->count(),
            'completed_orders' => Order::completed()->count(),
            'total_purchases' => Order::purchase()->completed()->sum('total_amount'),
            'total_sales' => Order::sale()->completed()->sum('total_amount'),
            'purchase_orders' => Order::purchase()->count(),
            'sale_orders' => Order::sale()->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Order statistics retrieved successfully',
            'data' => [
                'stats' => $stats
            ]
        ], 200);
    }
}