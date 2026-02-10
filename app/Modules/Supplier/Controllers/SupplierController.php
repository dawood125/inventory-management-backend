<?php

namespace App\Modules\Supplier\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Requests\SupplierRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Get all suppliers
     * 
     * GET /api/suppliers
     */
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query();

        // Filter by status if provided
        if ($request->has('status') && in_array($request->status, ['active', 'inactive'])) {
            $query->where('status', $request->status);
        }

        // Search by name or email
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Suppliers retrieved successfully',
            'data' => [
                'suppliers' => $suppliers,
                'total' => $suppliers->count()
            ]
        ], 200);
    }

    /**
     * Create new supplier
     * 
     * POST /api/suppliers
     */
    public function store(SupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'country' => $request->country,
            'status' => $request->status ?? 'active',
            'rating' => $request->rating ?? 0.0,
            'total_orders' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully',
            'data' => [
                'supplier' => $supplier
            ]
        ], 201);
    }

    /**
     * Get single supplier
     * 
     * GET /api/suppliers/{id}
     */
    public function show(string $id): JsonResponse
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Supplier retrieved successfully',
            'data' => [
                'supplier' => $supplier
            ]
        ], 200);
    }

    /**
     * Update supplier
     * 
     * PUT /api/suppliers/{id}
     */
    public function update(SupplierRequest $request, string $id): JsonResponse
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found',
            ], 404);
        }

        $supplier->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'country' => $request->country,
            'status' => $request->status ?? $supplier->status,
            'rating' => $request->rating ?? $supplier->rating,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully',
            'data' => [
                'supplier' => $supplier
            ]
        ], 200);
    }

    /**
     * Delete supplier
     * 
     * DELETE /api/suppliers/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found',
            ], 404);
        }

        // TODO: Check if supplier has products or orders before deleting
        // We'll add this check when those models exist

        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully',
        ], 200);
    }

    /**
     * Update supplier status
     * 
     * PATCH /api/suppliers/{id}/status
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $supplier = Supplier::find($id);

        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found',
            ], 404);
        }

        $supplier->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier status updated successfully',
            'data' => [
                'supplier' => $supplier
            ]
        ], 200);
    }
}