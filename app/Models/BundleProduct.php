<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BundleProduct extends Model
{
    use HasFactory;

    protected $table = 'bundle_product'; // Specify the pivot table
    protected $fillable = ['bundle_id', 'product_id', 'quantity', 'price']; // Add other fields if necessary

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
