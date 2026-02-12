<?php

namespace App\Modules\Order\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Modules\Supplier\Models\Supplier;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_number',
        'type',
        'status',
        'total_amount',
        'supplier_id',
        'customer_name',
        'notes',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'total_amount' => 'float',
    ];

    /**
     * Get order items
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get supplier (for purchase orders)
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get user who created the order
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if order is purchase type
     */
    public function isPurchase(): bool
    {
        return $this->type === 'purchase';
    }

    /**
     * Check if order is sale type
     */
    public function isSale(): bool
    {
        return $this->type === 'sale';
    }

    /**
     * Check if order is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if order is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(string $type): string
    {
        $prefix = $type === 'purchase' ? 'PO' : 'SO';
        $year = date('Y');
        $lastOrder = self::where('type', $type)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastOrder ? (intval(substr($lastOrder->order_number, -4)) + 1) : 1;

        return sprintf('%s-%s-%04d', $prefix, $year, $nextNumber);
    }

    /**
     * Calculate total from items
     */
    public function calculateTotal(): float
    {
        return $this->items->sum('total');
    }

    /**
     * Scope for purchase orders
     */
    public function scopePurchase($query)
    {
        return $query->where('type', 'purchase');
    }

    /**
     * Scope for sale orders
     */
    public function scopeSale($query)
    {
        return $query->where('type', 'sale');
    }

    /**
     * Scope for pending orders
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed orders
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}