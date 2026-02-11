<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Category\Models\Category;
use App\Modules\Supplier\Models\Supplier;
use Database\Factories\ProductFactory;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sku',
        'name',
        'description',
        'category_id',
        'supplier_id',
        'price',
        'cost_price',
        'quantity',
        'min_stock',
        'max_stock',
        'location',
        'image',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'float',
        'cost_price' => 'float',
        'quantity' => 'integer',
        'min_stock' => 'integer',
        'max_stock' => 'integer',
    ];

    protected static function newFactory()
    {
        return ProductFactory::new();
    }

    /**
     * Get the category that owns the product.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the supplier that owns the product.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Check if product is low stock
     */
    public function isLowStock(): bool
    {
        return $this->quantity > 0 && $this->quantity <= $this->min_stock;
    }

    /**
     * Check if product is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity === 0;
    }

    /**
     * Get stock status
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->quantity === 0) {
            return 'out_of_stock';
        } elseif ($this->quantity <= $this->min_stock) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    /**
     * Calculate inventory value
     */
    public function getInventoryValueAttribute(): float
    {
        return $this->quantity * $this->cost_price;
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for low stock products
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'min_stock')
                     ->where('quantity', '>', 0);
    }

    /**
     * Scope for out of stock products
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', 0);
    }
}