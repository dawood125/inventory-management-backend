<?php

namespace App\Modules\Supplier\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'status',
        'rating',
        'total_orders',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'rating' => 'float',
        'total_orders' => 'integer',
    ];

    /**
     * Scope for active suppliers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for inactive suppliers
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
}