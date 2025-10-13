<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'number','name','status','corporateId',
        // classes / meta
        'customer_class_id','customer_class_description',
        'price_class_id','price_class_description','customer_price_class_id',
        'currency_id','language_id','sales_person_id','branch_id','default_location_id',
        // finance
        'credit_limit','credit_hold','balance','overdue_balance',
        'terms_id','payment_method_id','cash_discount_id',
        // tax
        'tax_zone_id','vat_code_id','vat_registration_id','vat_exempt',
        // shipping
        'ship_via_id','delivery_terms_id',
        // e-invoice / EDI
        'einvoice_participant_id','einvoice_address','einvoice_operator','edoc_email','edoc_enabled',
        // flattened addresses
        'main_address_line1','main_address_line2','main_postal_code','main_city','main_country','main_country_id','main_county',
        'invoice_address_line1','invoice_address_line2','invoice_postal_code','invoice_city','invoice_country','invoice_country_id','invoice_county',
        'delivery_address_line1','delivery_address_line2','delivery_postal_code','delivery_city','delivery_country','delivery_country_id','delivery_county',
        // flattened contacts
        'main_contact_name','main_contact_attention','main_contact_email','main_contact_phone','main_contact_phone2',
        'invoice_contact_name','invoice_contact_attention','invoice_contact_email','invoice_contact_phone',
        'delivery_contact_name','delivery_contact_attention','delivery_contact_email','delivery_contact_phone',
        // rich JSON blobs
        'payment_settings','financial_information','attributes_data',
        'main_address_json','invoice_address_json','delivery_address_json',
        'main_contact_json','invoice_contact_json','delivery_contact_json',
        'custom_fields','raw_payload',
    ];

    protected $casts = [
        'credit_hold'          => 'bool',
        'vat_exempt'           => 'bool',
        'edoc_enabled'         => 'bool',
        'credit_limit'         => 'decimal:2',
        'balance'              => 'decimal:2',
        'overdue_balance'      => 'decimal:2',
        'payment_settings'     => 'array',
        'financial_information'=> 'array',
        'attributes_data'      => 'array',
        'main_address_json'    => 'array',
        'invoice_address_json' => 'array',
        'delivery_address_json'=> 'array',
        'main_contact_json'    => 'array',
        'invoice_contact_json' => 'array',
        'delivery_contact_json'=> 'array',
        'custom_fields'        => 'array',
        'raw_payload'          => 'array',
    ];
}
