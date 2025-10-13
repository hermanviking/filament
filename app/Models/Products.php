<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Products extends Model
{
    use HasFactory;

    // Specify the table name if it differs from the default 'products'
    protected $table = 'products';

    // Allow mass-assignment for these fields
    protected $fillable = [
        'sku',
        'inventory_id',
        'name',
        'price',
        'recommended_price',
        'current_cost',
        'last_cost',
        'description',
        'body',
        'short_description',
        'category',
        'item_class_id',
        'item_class_description',
        'image',
        'brand',
        'color_code',
        'volume',
        'volume_value',
        'volume_uom',
        'weight',
        'weight_uom',
        'status',
        'product_type',
        'vat_code_id',
        'vat_code_description',
        'item_price_class_id',
        'price_class_id',
        'price_class_description',
        'base_unit',
        'sales_unit',
        'purchase_unit',
        'default_warehouse_id',
        'default_issue_from',
        'default_receipt_to',
        'quantity_on_hand',
        'quantity_available',
        'quantity_available_for_shipment',
        'country_of_origin',
        'supplementary_measure_unit',
        'kasselov_code',
        'is_web_item',
        'is_web_item_b2b',
        'is_web_item_b2c',
        'stock_item',
        'kit_item',
        'is_hazardous',
        'is_display_only',
        'is_parent',
        'attributes_data',
        'warehouse_details',
        'cross_references',
        'rating_rate',
        'rating_count',
        'last_modified_at',
        'visma_timestamp',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'recommended_price' => 'decimal:2',
        'current_cost' => 'decimal:2',
        'last_cost' => 'decimal:2',
        'weight' => 'decimal:4',
        'volume_value' => 'decimal:4',
        'quantity_on_hand' => 'decimal:2',
        'quantity_available' => 'decimal:2',
        'quantity_available_for_shipment' => 'decimal:2',
        'rating_rate' => 'decimal:2',
        'is_web_item' => 'boolean',
        'is_web_item_b2b' => 'boolean',
        'is_web_item_b2c' => 'boolean',
        'stock_item' => 'boolean',
        'kit_item' => 'boolean',
        'is_hazardous' => 'boolean',
        'is_display_only' => 'boolean',
        'is_parent' => 'boolean',
        'attributes_data' => 'array',
        'warehouse_details' => 'array',
        'cross_references' => 'array',
        'last_modified_at' => 'datetime',
    ];

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_product')->withPivot('quantity');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
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
