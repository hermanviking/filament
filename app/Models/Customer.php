<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    /**
     * Allow mass-assignment for fields that are synced from Visma.
     */
    protected $fillable = [
        'number',
        'name',
        'corporateId',
        'main_address_line1',
        'main_postal_code',
        'main_city',
        'main_country',
        'main_county',
        'invoice_address_line1',
        'invoice_postal_code',
        'invoice_city',
        'invoice_country',
        'invoice_county',
        'delivery_address_line1',
        'delivery_postal_code',
        'delivery_city',
        'delivery_country',
        'delivery_county',
        'main_contact_name',
        'main_contact_attention',
        'main_contact_email',
        'main_contact_phone',
        'invoice_contact_name',
        'invoice_contact_attention',
        'invoice_contact_email',
        'invoice_contact_phone',
        'delivery_contact_name',
        'delivery_contact_attention',
        'delivery_contact_email',
        'delivery_contact_phone',
        'customer_price_class_id',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
