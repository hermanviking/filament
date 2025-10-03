<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    use HasFactory;

    // Specify the table name if it differs from the default 'products'
    protected $table = 'products';

    // Allow mass-assignment for these fields
    protected $fillable = [
        'sku',
        'name',
        'price',
        'description',
        'category',
        'image',
        'item_price_class_id',
        'rating_rate',
        'rating_count',
    ];

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_product')->withPivot('quantity');
    }

    public function bundles()
    {
        return $this->belongsToMany(Bundle::class, 'bundle_product')
            ->withPivot('quantity')
            ->withTimestamps();
    }





    // Optionally, you can set $guarded if you want to protect fields from mass assignment
    // protected $guarded = ['id']; // Example: Protect the 'id' field if needed
}
