<?php

namespace App\Modules\Report\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Modules\Category\Models\Category;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Order\Models\Order;
use App\Modules\Stock\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Get dashboard statistics
     * 
     * GET /api/reports/dashboard
     */
    public function dashboard(): JsonResponse
    {
        // Product stats
        $totalProducts = Product::count();
        $activeProducts = Product::where('status', 'active')->count();
        $lowStockProducts = Product::whereColumn('quantity', '<=', 'min_stock')
            ->where('quantity', '>', 0)
            ->count();
        $outOfStockProducts = Product::where('quantity', 0)->count();

        // Inventory value
        $inventoryValue = Product::selectRaw('SUM(quantity * cost_price) as total')
            ->first()
            ->total ?? 0;

        // Category & Supplier counts
        $totalCategories = Category::count();
        $totalSuppliers = Supplier::where('status', 'active')->count();

        // Order stats
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $completedOrders = Order::where('status', 'completed')->count();

        // Sales & Purchase totals (completed orders only)
        $totalSales = Order::where('type', 'sale')
            ->where('status', 'completed')
            ->sum('total_amount');

        $totalPurchases = Order::where('type', 'purchase')
            ->where('status', 'completed')
            ->sum('total_amount');

        // Today's stats
        $todayOrders = Order::whereDate('created_at', today())->count();
        $todaySales = Order::where('type', 'sale')
            ->whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('total_amount');

        // This month stats
        $monthSales = Order::where('type', 'sale')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', 'completed')
            ->sum('total_amount');

        $monthPurchases = Order::where('type', 'purchase')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', 'completed')
            ->sum('total_amount');

        return response()->json([
            'success' => true,
            'message' => 'Dashboard statistics retrieved successfully',
            'data' => [
                'products' => [
                    'total' => $totalProducts,
                    'active' => $activeProducts,
                    'low_stock' => $lowStockProducts,
                    'out_of_stock' => $outOfStockProducts,
                ],
                'inventory' => [
                    'total_value' => round($inventoryValue, 2),
                ],
                'categories' => [
                    'total' => $totalCategories,
                ],
                'suppliers' => [
                    'total_active' => $totalSuppliers,
                ],
                'orders' => [
                    'total' => $totalOrders,
                    'pending' => $pendingOrders,
                    'completed' => $completedOrders,
                    'today' => $todayOrders,
                ],
                'sales' => [
                    'total' => round($totalSales, 2),
                    'today' => round($todaySales, 2),
                    'this_month' => round($monthSales, 2),
                ],
                'purchases' => [
                    'total' => round($totalPurchases, 2),
                    'this_month' => round($monthPurchases, 2),
                ],
                'profit' => [
                    'total' => round($totalSales - $totalPurchases, 2),
                    'this_month' => round($monthSales - $monthPurchases, 2),
                ],
            ]
        ], 200);
    }

    /**
     * Get inventory report
     * 
     * GET /api/reports/inventory
     */
    public function inventory(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'supplier']);

        // Filter by category
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        $products = $query->orderBy('name')->get();

        // Calculate totals
        $totalItems = $products->sum('quantity');
        $totalValue = $products->sum(function ($product) {
            return $product->quantity * $product->cost_price;
        });
        $totalRetailValue = $products->sum(function ($product) {
            return $product->quantity * $product->price;
        });

        // Transform products with calculated fields
        $inventoryData = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category->name ?? 'N/A',
                'supplier' => $product->supplier->name ?? 'N/A',
                'quantity' => $product->quantity,
                'min_stock' => $product->min_stock,
                'max_stock' => $product->max_stock,
                'cost_price' => $product->cost_price,
                'sell_price' => $product->price,
                'stock_value' => round($product->quantity * $product->cost_price, 2),
                'retail_value' => round($product->quantity * $product->price, 2),
                'stock_status' => $product->stock_status,
                'status' => $product->status,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Inventory report retrieved successfully',
            'data' => [
                'summary' => [
                    'total_products' => $products->count(),
                    'total_items' => $totalItems,
                    'total_cost_value' => round($totalValue, 2),
                    'total_retail_value' => round($totalRetailValue, 2),
                    'potential_profit' => round($totalRetailValue - $totalValue, 2),
                ],
                'products' => $inventoryData,
            ]
        ], 200);
    }

    /**
     * Get sales report
     * 
     * GET /api/reports/sales
     */
    public function sales(Request $request): JsonResponse
    {
        $query = Order::with(['items', 'createdBy'])
            ->where('type', 'sale');

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Date range filter
        if ($request->has('from_date') && !empty($request->from_date)) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && !empty($request->to_date)) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        // Calculate totals
        $totalSales = $orders->where('status', 'completed')->sum('total_amount');
        $totalOrders = $orders->count();
        $completedOrders = $orders->where('status', 'completed')->count();
        $pendingOrders = $orders->where('status', 'pending')->count();

        // Monthly breakdown
        $monthlySales = Order::where('type', 'sale')
            ->where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->selectRaw('MONTH(created_at) as month, SUM(total_amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => date('F', mktime(0, 0, 0, $item->month, 1)),
                    'month_number' => $item->month,
                    'total' => round($item->total, 2),
                ];
            });

        // Top selling products
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.type', 'sale')
            ->where('orders.status', 'completed')
            ->select('order_items.product_name', DB::raw('SUM(order_items.quantity) as total_quantity'), DB::raw('SUM(order_items.total) as total_amount'))
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Sales report retrieved successfully',
            'data' => [
                'summary' => [
                    'total_sales' => round($totalSales, 2),
                    'total_orders' => $totalOrders,
                    'completed_orders' => $completedOrders,
                    'pending_orders' => $pendingOrders,
                    'average_order_value' => $completedOrders > 0 ? round($totalSales / $completedOrders, 2) : 0,
                ],
                'monthly_sales' => $monthlySales,
                'top_products' => $topProducts,
                'orders' => $orders,
            ]
        ], 200);
    }

    /**
     * Get purchase report
     * 
     * GET /api/reports/purchases
     */
    public function purchases(Request $request): JsonResponse
    {
        $query = Order::with(['items', 'supplier', 'createdBy'])
            ->where('type', 'purchase');

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Filter by supplier
        if ($request->has('supplier_id') && !empty($request->supplier_id)) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Date range filter
        if ($request->has('from_date') && !empty($request->from_date)) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && !empty($request->to_date)) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        // Calculate totals
        $totalPurchases = $orders->where('status', 'completed')->sum('total_amount');
        $totalOrders = $orders->count();
        $completedOrders = $orders->where('status', 'completed')->count();
        $pendingOrders = $orders->where('status', 'pending')->count();

        // Monthly breakdown
        $monthlyPurchases = Order::where('type', 'purchase')
            ->where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->selectRaw('MONTH(created_at) as month, SUM(total_amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => date('F', mktime(0, 0, 0, $item->month, 1)),
                    'month_number' => $item->month,
                    'total' => round($item->total, 2),
                ];
            });

        // Top suppliers
        $topSuppliers = Order::where('type', 'purchase')
            ->where('status', 'completed')
            ->whereNotNull('supplier_id')
            ->with('supplier')
            ->selectRaw('supplier_id, COUNT(*) as order_count, SUM(total_amount) as total_amount')
            ->groupBy('supplier_id')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'supplier_name' => $item->supplier->name ?? 'Unknown',
                    'order_count' => $item->order_count,
                    'total_amount' => round($item->total_amount, 2),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Purchase report retrieved successfully',
            'data' => [
                'summary' => [
                    'total_purchases' => round($totalPurchases, 2),
                    'total_orders' => $totalOrders,
                    'completed_orders' => $completedOrders,
                    'pending_orders' => $pendingOrders,
                    'average_order_value' => $completedOrders > 0 ? round($totalPurchases / $completedOrders, 2) : 0,
                ],
                'monthly_purchases' => $monthlyPurchases,
                'top_suppliers' => $topSuppliers,
                'orders' => $orders,
            ]
        ], 200);
    }

    /**
     * Get low stock report
     * 
     * GET /api/reports/low-stock
     */
    public function lowStock(): JsonResponse
    {
        // Low stock products (quantity <= min_stock but > 0)
        $lowStockProducts = Product::with(['category', 'supplier'])
            ->whereColumn('quantity', '<=', 'min_stock')
            ->where('quantity', '>', 0)
            ->where('status', 'active')
            ->orderBy('quantity')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category' => $product->category->name ?? 'N/A',
                    'supplier' => $product->supplier->name ?? 'N/A',
                    'current_stock' => $product->quantity,
                    'min_stock' => $product->min_stock,
                    'max_stock' => $product->max_stock,
                    'reorder_quantity' => $product->max_stock - $product->quantity,
                    'stock_status' => 'low_stock',
                ];
            });

        // Out of stock products
        $outOfStockProducts = Product::with(['category', 'supplier'])
            ->where('quantity', 0)
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category' => $product->category->name ?? 'N/A',
                    'supplier' => $product->supplier->name ?? 'N/A',
                    'current_stock' => 0,
                    'min_stock' => $product->min_stock,
                    'max_stock' => $product->max_stock,
                    'reorder_quantity' => $product->max_stock,
                    'stock_status' => 'out_of_stock',
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Low stock report retrieved successfully',
            'data' => [
                'summary' => [
                    'low_stock_count' => $lowStockProducts->count(),
                    'out_of_stock_count' => $outOfStockProducts->count(),
                    'total_alerts' => $lowStockProducts->count() + $outOfStockProducts->count(),
                ],
                'low_stock_products' => $lowStockProducts,
                'out_of_stock_products' => $outOfStockProducts,
            ]
        ], 200);
    }

    /**
     * Get category-wise report
     * 
     * GET /api/reports/categories
     */
    public function categories(): JsonResponse
    {
        $categories = Category::withCount('products')
            ->get()
            ->map(function ($category) {
                $products = Product::where('category_id', $category->id)->get();
                
                $totalStock = $products->sum('quantity');
                $totalValue = $products->sum(function ($p) {
                    return $p->quantity * $p->cost_price;
                });
                $lowStockCount = $products->filter(function ($p) {
                    return $p->quantity <= $p->min_stock && $p->quantity > 0;
                })->count();
                $outOfStockCount = $products->where('quantity', 0)->count();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'product_count' => $category->products_count,
                    'total_stock' => $totalStock,
                    'total_value' => round($totalValue, 2),
                    'low_stock_count' => $lowStockCount,
                    'out_of_stock_count' => $outOfStockCount,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Category report retrieved successfully',
            'data' => [
                'categories' => $categories,
                'total_categories' => $categories->count(),
            ]
        ], 200);
    }
}