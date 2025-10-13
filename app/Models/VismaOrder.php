<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VismaOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    // optional, only if you want to be explicit
    // protected $table = 'visma_orders';

    protected $fillable = [
        'order_id','type','status',
        'date','shipping_scheduled_date','request_on','last_modified','cancel_by',
        'customer_id','customer_name',
        'order_total','tax_total','currency',
        'location','customer_order','customer_ref_no','description','emailed',
        'parent_customer','branch','project','print','billing','payment_settings',
        'financial_information','owner','origin','shipping','status_details',
        'customer_block','totals','freight','sales_person','attachments',
        'custom_fields','rot_rut','commissions','tax','shipment','discounts',
        'payments','raw_payload',
    ];

    protected $casts = [
        'date' => 'datetime',
        'shipping_scheduled_date' => 'datetime',
        'request_on' => 'datetime',
        'last_modified' => 'datetime',
        'cancel_by' => 'datetime',
        'emailed' => 'boolean',
        'order_total' => 'decimal:4',
        'tax_total' => 'decimal:4',

        'parent_customer' => 'array',
        'branch' => 'array',
        'project' => 'array',
        'print' => 'array',
        'billing' => 'array',
        'payment_settings' => 'array',
        'financial_information' => 'array',
        'owner' => 'array',
        'origin' => 'array',
        'shipping' => 'array',
        'status_details' => 'array',
        'customer_block' => 'array',
        'totals' => 'array',
        'freight' => 'array',
        'sales_person' => 'array',
        'attachments' => 'array',
        'custom_fields' => 'array',
        'rot_rut' => 'array',
        'commissions' => 'array',
        'tax' => 'array',
        'shipment' => 'array',
        'discounts' => 'array',
        'payments' => 'array',
        'raw_payload' => 'array',
    ];

 public function items()
{
    return $this->hasMany(\App\Models\VismaOrderItem::class);
}

}
