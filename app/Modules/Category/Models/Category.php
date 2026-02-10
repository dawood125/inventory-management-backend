<?php

namespace App\Modules\Category\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * Get products count for this category
     * We'll add relationship when Product model is created
     */
    public function getProductCountAttribute(): int
    {
        // Will be updated when Product model exists
        return 0;
    }
}