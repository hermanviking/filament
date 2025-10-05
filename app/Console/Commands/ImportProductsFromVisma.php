<?php

namespace App\Console\Commands;

use App\Models\Products;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportProductsFromVisma extends Command
{
    protected $signature = 'import:visma-products';
    protected $description = 'Import products from Visma.net API';

    private const PAGE_SIZE = 100;
    private Client $client;
    private ?string $accessToken = null;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client();
    }

    public function handle(): int
    {
        $this->info('🚀 Starting Visma product import...');
        $this->accessToken = $this->getAccessToken();

        if (!$this->accessToken) {
            $this->error('❌ Failed to receive access token.');
            return self::FAILURE;
        }

        $this->info('✅ Access token successfully received.');

        $page = 1;
        $totalImported = 0;

        while (true) {
            $products = $this->fetchProducts($page);

            if (empty($products)) {
                $this->warn("⚠️ No products found on page {$page}. Stopping import.");
                break;
            }

            $importedThisPage = 0;

            foreach ($products as $productData) {
                $payload = $this->mapProductData($productData);

                if (blank($payload['sku'])) {
                    $this->warn('Skipped product without SKU.');
                    continue;
                }

                Products::updateOrCreate(['sku' => $payload['sku']], $payload);
                $importedThisPage++;
                $totalImported++;
            }

            $this->info("✅ Imported {$importedThisPage} products from page {$page}.");

            if (count($products) < self::PAGE_SIZE) {
                break;
            }

            $page++;
        }

        $this->info("🎉 Import complete: {$totalImported} products imported in total.");
        return self::SUCCESS;
    }

    private function fetchProducts(int $page): ?array
    {
        $response = Http::withToken($this->accessToken)
            ->acceptJson()
            ->get('https://integration.visma.net/API/controller/api/v1/inventory', [
                'status' => 'Active',
                'inventoryTypes' => 'FinishedGoodItem',
                'pageSize' => self::PAGE_SIZE,
                'pageNumber' => $page,
            ]);

        if ($response->failed()) {
            $this->error("❌ Error fetching products from page {$page} (status {$response->status()}).");
            $this->error($response->body());
            return null;
        }

        $payload = $response->json();
        return $payload['data'] ?? $payload ?? [];
    }

    private function getAccessToken(): ?string
    {
        try {
            $response = $this->client->post('https://connect.visma.com/connect/token', [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => env('VISMA_CLIENT_ID'),
                    'client_secret' => env('VISMA_CLIENT_SECRET'),
                    'scope' => 'vismanet_erp_service_api:read',
                    'tenant_id' => env('VISMA_TENANT_ID'),
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['access_token'] ?? null;
        } catch (\Exception $e) {
            $this->error('Error fetching access token: ' . $e->getMessage());
            return null;
        }
    }

    private function mapProductData(array $productData): array
    {
        $defaultPrice = data_get($productData, 'defaultPrice.amount', data_get($productData, 'defaultPrice', 0));
        if (is_array($defaultPrice)) {
            $defaultPrice = 0;
        }

        $rawAttributes = data_get($productData, 'attributes', []);
        if (!is_array($rawAttributes)) {
            $rawAttributes = [];
        }

        $attributes = $this->normalizeAttributes($rawAttributes);
        $shortDescription = $this->limitedStringValue($attributes['SHORTDESC'] ?? null);

        $warehouseDetails = data_get($productData, 'warehouseDetails', []);
        $primaryWarehouse = $warehouseDetails[0] ?? [];

        $crossReferences = data_get($productData, 'crossReferences', []);
        $itemClassId = data_get($productData, 'itemClass.id', data_get($productData, 'itemClassId'));
        $itemClassDescription = data_get($productData, 'itemClass.description');
        $priceClassId = data_get($productData, 'priceClass.id', data_get($productData, 'priceClassId'));
        $priceClassDescription = data_get($productData, 'priceClass.description');
        $body = data_get($productData, 'body');

        return [
            'sku' => $this->limitedStringValue(data_get($productData, 'inventoryNumber', data_get($productData, 'inventoryId'))) ?? '',
            'inventory_id' => $this->limitedStringValue(data_get($productData, 'inventoryId')),
            'name' => $this->limitedStringValue(data_get($productData, 'description', data_get($productData, 'inventoryNumber', ''))) ?? '',
            'description' => $this->normalizeDescription($body, $shortDescription, data_get($productData, 'description')),
            'body' => is_string($body) ? $body : null,
            'status' => $this->limitedStringValue(data_get($productData, 'status')),
            'product_type' => $this->limitedStringValue(data_get($productData, 'type')),
            'category' => $this->limitedStringValue(data_get($productData, 'itemClass.description', data_get($productData, 'itemClassId', ''))) ?? '',
            'item_class_id' => $this->limitedStringValue($itemClassId),
            'item_class_description' => $this->limitedStringValue($itemClassDescription),
            'image' => $this->limitedStringValue(data_get($productData, 'imageUrl')) ?? '',
            'price' => (float) $defaultPrice,
            'recommended_price' => $this->floatValue(data_get($productData, 'recommendedPrice')),
            'current_cost' => $this->floatValue(data_get($productData, 'currentCost')),
            'last_cost' => $this->floatValue(data_get($productData, 'lastCost')),
            'item_price_class_id' => $this->limitedStringValue(data_get($productData, 'priceClassId', data_get($productData, 'priceClassID'))),
            'price_class_id' => $this->limitedStringValue($priceClassId),
            'price_class_description' => $this->limitedStringValue($priceClassDescription),
            'brand' => $this->limitedStringValue($attributes['MERKE'] ?? null),
            'short_description' => $shortDescription,
            'volume' => $this->limitedStringValue($attributes['VOLUM'] ?? null),
            'color_code' => $this->limitedStringValue($attributes['FARGE'] ?? null),
            'kasselov_code' => $this->limitedStringValue($attributes['KASSELOV'] ?? null),
            'is_hazardous' => $this->attributeFlag($attributes['FARLIG'] ?? null),
            'is_display_only' => $this->attributeFlag($attributes['DISPONLY'] ?? null),
            'is_parent' => $this->attributeFlag($attributes['ISPARENT'] ?? null),
            'is_web_item' => $this->attributeFlag($attributes['WEBVARE'] ?? null),
            'is_web_item_b2b' => $this->attributeFlag($attributes['WEBVAREB2B'] ?? null),
            'is_web_item_b2c' => $this->attributeFlag($attributes['WEBVAREB2C'] ?? null),
            'stock_item' => $this->attributeFlag(data_get($productData, 'stockItem')),
            'kit_item' => $this->attributeFlag(data_get($productData, 'kitItem')),
            'base_unit' => $this->limitedStringValue(data_get($productData, 'baseUnit')),
            'sales_unit' => $this->limitedStringValue(data_get($productData, 'salesUnit')),
            'purchase_unit' => $this->limitedStringValue(data_get($productData, 'purchaseUnit')),
            'default_warehouse_id' => $this->limitedStringValue(data_get($productData, 'defaultWarehouse.id')),
            'default_issue_from' => $this->limitedStringValue(data_get($productData, 'defaultIssueFrom.id')),
            'default_receipt_to' => $this->limitedStringValue(data_get($productData, 'defaultReceiptTo.id')),
            'weight' => $this->floatValue(data_get($productData, 'packaging.baseItemWeight')),
            'weight_uom' => $this->limitedStringValue(data_get($productData, 'packaging.weightUOM')),
            'volume_value' => $this->floatValue(data_get($productData, 'packaging.baseItemVolume')),
            'volume_uom' => $this->limitedStringValue(data_get($productData, 'packaging.volumeUOM')),
            'country_of_origin' => $this->limitedStringValue(data_get($productData, 'intrastat.countryOfOrigin')),
            'supplementary_measure_unit' => $this->limitedStringValue(data_get($productData, 'intrastat.supplementaryMeasureUnit')),
            'vat_code_id' => $this->limitedStringValue(data_get($productData, 'vatCode.id')),
            'vat_code_description' => $this->limitedStringValue(data_get($productData, 'vatCode.description')),
            'attributes_data' => !empty($rawAttributes) ? $rawAttributes : null,
            'warehouse_details' => !empty($warehouseDetails) ? $warehouseDetails : null,
            'cross_references' => !empty($crossReferences) ? $crossReferences : null,
            'quantity_on_hand' => $this->floatValue(data_get($primaryWarehouse, 'quantityOnHand')),
            'quantity_available' => $this->floatValue(data_get($primaryWarehouse, 'available')),
            'quantity_available_for_shipment' => $this->floatValue(data_get($primaryWarehouse, 'availableForShipment')),
            'rating_rate' => $this->floatValue(data_get($productData, 'rating.rate')),
            'rating_count' => $this->intValue(data_get($productData, 'rating.count')),
            'last_modified_at' => $this->parseDateTime(data_get($productData, 'lastModifiedDateTime')),
            'visma_timestamp' => $this->limitedStringValue(data_get($productData, 'timestamp')),
        ];
    }

    private function normalizeAttributes(array $attributes): array
    {
        $normalized = [];
        foreach ($attributes as $attribute) {
            $identifier = data_get($attribute, 'id');
            if (!$identifier) continue;
            $normalized[$identifier] = data_get($attribute, 'value');
        }
        return $normalized;
    }

    private function attributeFlag($value): bool
    {
        if (is_bool($value)) return $value;
        if (is_numeric($value)) return (int)$value === 1;
        if (is_string($value)) {
            $v = strtolower(trim($value));
            return in_array($v, ['1', 'true', 'yes', 'y'], true);
        }
        return false;
    }

    private function stringValue($value): ?string
    {
        if (is_null($value)) return null;
        if (is_scalar($value)) {
            $string = trim((string)$value);
            return $string === '' ? null : $string;
        }
        return null;
    }

    private function limitedStringValue($value, int $limit = 255): ?string
    {
        $string = $this->stringValue($value);
        return $string ? Str::limit($string, $limit, '') : null;
    }

    private function floatValue($value): ?float
    {
        return is_numeric($value) ? (float)$value : null;
    }

    private function intValue($value): ?int
    {
        return is_numeric($value) ? (int)$value : null;
    }

    private function parseDateTime($value): ?Carbon
    {
        if (blank($value)) return null;
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeDescription($body, ?string $shortDescription, $fallback): string
    {
        if (is_string($shortDescription) && $shortDescription !== '') {
            return Str::limit(trim($shortDescription), 255, '');
        }

        $candidates = [];

        if (is_string($body) && $body !== '') {
            $sanitizedBody = preg_replace('/<style.*?>.*?<\/style>/si', '', $body);
            $text = html_entity_decode(strip_tags($sanitizedBody));
            $text = preg_replace('/\s+/', ' ', $text ?? '');
            $candidates[] = $text;
        }

        if (is_string($fallback) && $fallback !== '') {
            $candidates[] = $fallback;
        }

        foreach ($candidates as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate === '') continue;
            return Str::limit($candidate, 255, '');
        }

        return '';
    }
}
