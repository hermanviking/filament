<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bundle extends Model
{
    use HasFactory;

    protected $fillable = ['sku', 'name', 'description', 'price'];

    /**
     * Relationship: A bundle contains many products through the pivot table.
     */
    public function items(): HasMany
    {
        return $this->hasMany(BundleProduct::class, 'bundle_id');
    }

    public function calculatePrice(): float
    {
        return $this->items->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });
    }
}
