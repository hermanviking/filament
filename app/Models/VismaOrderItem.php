<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VismaOrderItem extends Model
{
    use HasFactory;

    // protected $table = 'visma_order_items'; // optional, Laravel infers this

    protected $fillable = [
        'visma_order_id',
        'line_id','sort_order','line_type','operation',
        'inventory_id','inventory_description','inventory_base_unit',
        'unit_of_measure',
        'quantity','base_order_quantity','unit_cost','unit_price',
        'extended_price','line_total_before_discount','discount_amount','discount_percent',
        'order_date','ship_date','request_date',
        'description','warehouse_id','tax_category_id','completed','free_item','open_line',
        'branch','shipping_rule','reason_code','warehouse_location','sales_person',
        'supplier','attachments','allocations','custom_fields','raw_payload',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'base_order_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'extended_price' => 'decimal:4',
        'line_total_before_discount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'discount_percent' => 'decimal:4',
        'order_date' => 'datetime',
        'ship_date' => 'datetime',
        'request_date' => 'datetime',
        'completed' => 'boolean',
        'free_item' => 'boolean',
        'open_line' => 'boolean',
        'branch' => 'array',
        'shipping_rule' => 'array',
        'reason_code' => 'array',
        'warehouse_location' => 'array',
        'sales_person' => 'array',
        'supplier' => 'array',
        'attachments' => 'array',
        'allocations' => 'array',
        'custom_fields' => 'array',
        'raw_payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(VismaOrder::class, 'visma_order_id');
    }
}
