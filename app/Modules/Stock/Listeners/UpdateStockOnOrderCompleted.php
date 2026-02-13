<?php

namespace App\Modules\Stock\Listeners;

use App\Modules\Order\Events\OrderCompleted;
use App\Modules\Stock\Models\StockMovement;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateStockOnOrderCompleted
{
    /**
     * Handle the event.
     */
    public function handle(OrderCompleted $event): void
    {
        $order = $event->order;

        // Load order items if not loaded
        $order->load('items');

        try {
            DB::beginTransaction();

            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);

                if (!$product) {
                    Log::warning("Product not found for stock update: {$item->product_id}");
                    continue;
                }

                $stockBefore = $product->quantity;

                // Determine stock change based on order type
                if ($order->type === 'purchase') {
                    // Purchase order = Stock IN (we received items)
                    $stockAfter = $stockBefore + $item->quantity;
                    $movementType = 'in';
                    $reason = 'Purchase Order Completed';
                } else {
                    // Sale order = Stock OUT (we sold items)
                    $stockAfter = $stockBefore - $item->quantity;
                    $movementType = 'out';
                    $reason = 'Sales Order Completed';

                    // Prevent negative stock
                    if ($stockAfter < 0) {
                        Log::warning("Insufficient stock for product: {$product->name}");
                        $stockAfter = 0;
                    }
                }

                // Create stock movement record
                StockMovement::create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'type' => $movementType,
                    'quantity' => $item->quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reason' => $reason,
                    'reference' => $order->order_number,
                    'created_by' => $order->created_by,
                ]);

                // Update product stock
                $product->update(['quantity' => $stockAfter]);

                Log::info("Stock updated for {$product->name}: {$stockBefore} -> {$stockAfter}");
            }

            DB::commit();

            Log::info("Stock updated successfully for order: {$order->order_number}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update stock for order {$order->order_number}: {$e->getMessage()}");
        }
    }
}