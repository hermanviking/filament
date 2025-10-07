<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'invoice_address',
        'invoice_city',
        'invoice_postal_code',
        'delivery_address',
        'delivery_city',
        'delivery_postal_code',
        'total_amount',
        'status',
        'customer_price_class_id',
        'visma_sales_order_number',
        'visma_sales_order_type',
        'visma_status',
        'visma_last_synced_at',
        'visma_payload',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'visma_payload' => 'array',
        'visma_last_synced_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function products()
    {
        return $this->belongsToMany(Products::class, 'order_product', 'order_id', 'product_id')->withPivot('quantity');
    }
    public function calculateTotalAmount()
    {
        $totalAmount = 0;

        foreach ($this->products as $product) {
            $totalAmount += $product->price * $product->pivot->quantity;
        }

        return $totalAmount;
    }
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

}
