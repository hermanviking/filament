<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'customer_price_class_id',
        'invoice_address',
        'invoice_city',
        'invoice_postal_code',
        'delivery_address',
        'delivery_city',
        'delivery_postal_code',
        'total_amount',
        'status',
        'visma_sales_order_number',
        'visma_sales_order_type',
        'visma_status',
        'visma_last_synced_at',
        'visma_payload',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'visma_last_synced_at' => 'datetime',
        'visma_payload' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            if (blank($order->visma_sales_order_type)) {
                $order->visma_sales_order_type = (string) config('services.visma.sales_order_type');
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function recalculateTotals(): void
    {
        $total = $this->items->sum(fn (OrderItem $item): float => (float) $item->quantity * (float) $item->discounted_price);

        $this->total_amount = round($total, 2);
    }

    public function applyTotalsAndSave(): void
    {
        $this->loadMissing('items');
        $this->recalculateTotals();
        $this->save();
    }
}
