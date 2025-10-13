<?php
// app/Support/Visma/InventoryMapper.php

declare(strict_types=1);

namespace App\Support\Visma;

use Illuminate\Support\Carbon;

class InventoryMapper
{
    /**
     * Map a Visma Finance Inventory payload into your Products fillable array.
     */
    public function map(array $productData): array
    {
        $defaultPrice = data_get($productData, 'defaultPrice.amount', data_get($productData, 'defaultPrice', 0));
        if (is_array($defaultPrice)) {
            $defaultPrice = 0;
        }

        $rawAttributes = data_get($productData, 'attributes');
        if (! is_array($rawAttributes)) {
            $rawAttributes = [];
        }
        $attributes = $this->normalizeAttributes($rawAttributes);

        $warehouseDetails = data_get($productData, 'warehouseDetails');
        if (! is_array($warehouseDetails)) {
            $warehouseDetails = [];
        }
        $primaryWarehouse = $warehouseDetails[0] ?? [];

        $crossReferences = data_get($productData, 'crossReferences');
        if (! is_array($crossReferences)) {
            $crossReferences = [];
        }

        $itemClassId          = data_get($productData, 'itemClass.id', data_get($productData, 'itemClassId'));
        $itemClassDescription = data_get($productData, 'itemClass.description');

        $priceClassId          = data_get($productData, 'priceClass.id', data_get($productData, 'priceClassId', data_get($productData, 'priceClassID')));
        $priceClassDescription = data_get($productData, 'priceClass.description');

        $body = data_get($productData, 'body');

        return [
            'sku'                             => $this->stringValue(data_get($productData, 'inventoryNumber', data_get($productData, 'inventoryId'))) ?? '',
            'inventory_id'                    => $this->stringValue(data_get($productData, 'inventoryNumber', data_get($productData, 'inventoryId'))) ?? '',
            'name'                            => $this->stringValue(data_get($productData, 'description', data_get($productData, 'inventoryNumber', ''))) ?? '',
            'description'                     => $this->normalizeDescription($body, data_get($productData, 'description')),
            'body'                            => is_string($body) ? $body : null,
            'status'                          => $this->stringValue(data_get($productData, 'status')),
            'product_type'                    => $this->stringValue(data_get($productData, 'type')),
            'category'                        => $this->stringValue(data_get($productData, 'itemClass.description', data_get($productData, 'itemClassId', ''))) ?? '',
            'item_class_id'                   => $this->stringValue($itemClassId),
            'item_class_description'          => $this->stringValue($itemClassDescription),
            'image'                           => $this->stringValue(data_get($productData, 'imageUrl')) ?? '',
            'price'                           => (float) $defaultPrice,
            'recommended_price'               => $this->floatValue(data_get($productData, 'recommendedPrice')),
            'current_cost'                    => $this->floatValue(data_get($productData, 'currentCost')),
            'last_cost'                       => $this->floatValue(data_get($productData, 'lastCost')),
            'item_price_class_id'             => $this->stringValue(data_get($productData, 'priceClassId', data_get($productData, 'priceClassID'))),
            'price_class_id'                  => $this->stringValue($priceClassId),
            'price_class_description'         => $this->stringValue($priceClassDescription),

            // Attributes (common custom fields)
            'brand'                           => $this->stringValue($attributes['MERKE'] ?? null),
            'short_description'               => $this->stringValue($attributes['SHORTDESC'] ?? null),
            'volume'                          => $this->stringValue($attributes['VOLUM'] ?? null),
            'color_code'                      => $this->stringValue($attributes['FARGE'] ?? null),
            'kasselov_code'                   => $this->stringValue($attributes['KASSELOV'] ?? null),

            // Flags
            'is_hazardous'                    => $this->attributeFlag($attributes['FARLIG'] ?? null),
            'is_display_only'                 => $this->attributeFlag($attributes['DISPONLY'] ?? null),
            'is_parent'                       => $this->attributeFlag($attributes['ISPARENT'] ?? null),
            'is_web_item'                     => $this->attributeFlag($attributes['WEBVARE'] ?? null),
            'is_web_item_b2b'                 => $this->attributeFlag($attributes['WEBVAREB2B'] ?? null),
            'is_web_item_b2c'                 => $this->attributeFlag($attributes['WEBVAREB2C'] ?? null),
            'stock_item'                      => $this->attributeFlag(data_get($productData, 'stockItem')),
            'kit_item'                        => $this->attributeFlag(data_get($productData, 'kitItem')),

            // Units & warehouse
            'base_unit'                       => $this->stringValue(data_get($productData, 'baseUnit')),
            'sales_unit'                      => $this->stringValue(data_get($productData, 'salesUnit')),
            'purchase_unit'                   => $this->stringValue(data_get($productData, 'purchaseUnit')),
            'default_warehouse_id'            => $this->stringValue(data_get($productData, 'defaultWarehouse.id')),
            'default_issue_from'              => $this->stringValue(data_get($productData, 'defaultIssueFrom.id')),
            'default_receipt_to'              => $this->stringValue(data_get($productData, 'defaultReceiptTo.id')),

            // Quantities
            'quantity_on_hand'                => $this->floatValue(data_get($primaryWarehouse, 'quantityOnHand')),
            'quantity_available'              => $this->floatValue(data_get($primaryWarehouse, 'available')),
            'quantity_available_for_shipment' => $this->floatValue(data_get($primaryWarehouse, 'availableForShipment')),

            // Packaging / Intrastat
            'weight'                          => $this->floatValue(data_get($productData, 'packaging.baseItemWeight')),
            'weight_uom'                      => $this->stringValue(data_get($productData, 'packaging.weightUOM')),
            'volume_value'                    => $this->floatValue(data_get($productData, 'packaging.baseItemVolume')),
            'volume_uom'                      => $this->stringValue(data_get($productData, 'packaging.volumeUOM')),
            'country_of_origin'               => $this->stringValue(data_get($productData, 'intrastat.countryOfOrigin')),
            'supplementary_measure_unit'      => $this->stringValue(data_get($productData, 'intrastat.supplementaryMeasureUnit')),

            // VAT
            'vat_code_id'                     => $this->stringValue(data_get($productData, 'vatCode.id')),
            'vat_code_description'            => $this->stringValue(data_get($productData, 'vatCode.description')),

            // Raw JSON blobs (keep arrays)
            'attributes_data'                 => !empty($rawAttributes)   ? $rawAttributes   : null,
            'warehouse_details'               => !empty($warehouseDetails) ? $warehouseDetails : null,
            'cross_references'                => !empty($crossReferences) ? $crossReferences : null,

            // Ratings & sync meta
            'rating_rate'                     => $this->floatValue(data_get($productData, 'rating.rate')),
            'rating_count'                    => $this->intValue(data_get($productData, 'rating.count')),
            'last_modified_at'                => $this->parseDateTime(data_get($productData, 'lastModifiedDateTime')),
            'visma_timestamp'                 => $this->stringValue(data_get($productData, 'timestamp')),
        ];
    }

    /* ---------------------- helpers ---------------------- */

    protected function normalizeAttributes(array $attributes): array
    {
        $normalized = [];
        foreach ($attributes as $attribute) {
            $id = data_get($attribute, 'id');
            if (! $id) {
                continue;
            }
            $normalized[$id] = data_get($attribute, 'value');
        }
        return $normalized;
    }

    protected function attributeFlag(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return ((int) $value) === 1;
        }
        if (is_string($value)) {
            $v = strtolower(trim($value));
            return in_array($v, ['1', 'true', 'yes', 'y'], true);
        }
        return false;
    }

    protected function stringValue(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }
        if (is_scalar($value)) {
            $s = trim((string) $value);
            return $s === '' ? null : $s;
        }
        return null;
    }

    protected function floatValue(mixed $value): ?float
    {
        if (is_null($value)) {
            return null;
        }
        return is_numeric($value) ? (float) $value : null;
    }

    protected function intValue(mixed $value): ?int
    {
        if (is_null($value)) {
            return null;
        }
        return is_numeric($value) ? (int) $value : null;
    }

    protected function parseDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function normalizeDescription(mixed $body, mixed $fallback): string
    {
        if (is_string($body) && $body !== '') {
            $text = html_entity_decode(strip_tags($body));
            $text = preg_replace('/\s+/', ' ', $text ?? '');
            return trim((string) $text);
        }
        if (is_string($fallback)) {
            return trim($fallback);
        }
        return '';
    }
}
