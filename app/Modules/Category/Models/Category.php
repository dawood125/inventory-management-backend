<?php

namespace App\Modules\Category\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Product\Models\Product;
use Database\Factories\CategoryFactory;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
    ];

    protected static function newFactory()
    {
        return CategoryFactory::new();
    }

    /**
     * Get products for this category
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get products count
     */
    public function getProductCountAttribute(): int
    {
        return $this->products()->count();
    }
}