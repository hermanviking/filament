<?php

namespace App\Console\Commands;

use App\Models\Products;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\VismaSettings;

class ImportProductsFromVisma extends Command
{
    private const DEFAULT_BASE_URL = 'https://api.finance.visma.net/v1';
    private const DEFAULT_SCOPE    = 'vismanet_erp_service_api:read';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:visma-products
                            {--status=Active : Filter by product status}
                            {--type=FinishedGoodItem : Filter by inventoryTypes}
                            {--page-size=100 : Page size (Visma max is typically 100)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from Visma.net ERP Inventory API';

    // Simple in-memory token cache for the command runtime
    private ?string $accessToken = null;
    private ?int $tokenExpiresAt = null;

    /** @var VismaSettings */
    private VismaSettings $s;

    public function __construct()
    {
        parent::__construct();
        // Initialize settings here (not as a property default)
        $this->s = VismaSettings::active();
    }

    public function handle(): int
    {
        $pageSize = (int) $this->option('page-size');
        $status   = (string) $this->option('status');
        $type     = (string) $this->option('type');

        if ($pageSize < 1 || $pageSize > 100) {
            $this->warn('Adjusting --page-size to 100 (allowed range 1..100).');
            $pageSize = 100;
        }

        $this->info(sprintf('Importing products (status=%s, type=%s, pageSize=%d)...', $status, $type, $pageSize));

        $token = $this->getAccessToken();
        if (!$token) {
            $this->error('Failed to obtain access token.');
            return self::FAILURE;
        }

        $page = 1;
        $totalImported = 0;

        while (true) {
            $products = $this->fetchProducts($page, $pageSize, $status, $type);

            if ($products === null) {
                return self::FAILURE; // already logged
            }

            if (empty($products)) {
                break; // no more pages
            }

            $importedThisPage = 0;

            foreach ($products as $productData) {
                $payload = $this->mapProductData($productData);

                if (blank($payload['sku'])) {
                    $this->warn('Skipped product without SKU.');
                    continue;
                }

                Products::updateOrCreate(
                    ['sku' => $payload['sku']],
                    $payload
                );

                $importedThisPage++;
                $totalImported++;
            }

            $this->info(sprintf('Imported %d products from page %d.', $importedThisPage, $page));

            if (count($products) < $pageSize) {
                break; // last page
            }

            $page++;
        }

        $this->info(sprintf('Products imported successfully (%d items).', $totalImported));
        return self::SUCCESS;
    }

    /**
     * Build a shared HTTP client with token + base URL.
     */
    private function http(): PendingRequest
    {
        $baseUrl = $this->s->financeBaseUrl();

        $client = Http::withToken($this->getAccessToken() ?? '')
            ->acceptJson()
            ->baseUrl($baseUrl)
            ->withHeaders([
                'ipp-company-id'        => env('VISMA_COMPANY_ID'),   // <- REQUIRED
            ])
            ->timeout(60)
            ->retry(3, 250, throw: false);

        // Optional wire logging when VISMA_HTTP_DEBUG=true
        if ($this->s->http_debug) {
            $stream = @fopen(storage_path('logs/visma-http.log'), 'ab');
            $client = $client->withOptions(['debug' => $stream]);
        }

        return $client;
    }

    /**
     * Fetch one page of products from Visma.
     * Returns array of product rows, empty array for last page, or null on error.
     */
    private function fetchProducts(int $page, int $pageSize, string $status, string $inventoryTypes): ?array
    {
        try {
            $response = $this->http()->get('inventory', [
                'status'        => $status,
                'inventoryTypes' => $inventoryTypes,
                'pageSize'      => $pageSize,
                'pageNumber'    => $page,
            ]);
        } catch (\Throwable $e) {
            $this->error(sprintf('HTTP error on page %d: %s', $page, $e->getMessage()));
            return null;
        }

        if ($response->failed()) {
            $this->error(sprintf('Error fetching products from page %d (status %d).', $page, $response->status()));
            $this->line((string) $response->body());
            return null;
        }

        $payload = $response->json();

        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        if (is_array($payload)) {
            return $payload; // some tenants return a flat array
        }

        $this->error('Unexpected product payload structure.');
        return null;
    }

    /**
     * Map Visma product payload to local Products model attributes.
     */
    private function mapProductData(array $productData): array
    {
        $defaultPrice = data_get($productData, 'defaultPrice.amount', data_get($productData, 'defaultPrice', 0));
        if (is_array($defaultPrice)) {
            $defaultPrice = 0;
        }

        $rawAttributes = data_get($productData, 'attributes');
        if (!is_array($rawAttributes)) {
            $rawAttributes = [];
        }
        $attributes = $this->normalizeAttributes($rawAttributes);

        $warehouseDetails = data_get($productData, 'warehouseDetails');
        if (!is_array($warehouseDetails)) {
            $warehouseDetails = [];
        }
        $primaryWarehouse = $warehouseDetails[0] ?? [];

        $crossReferences = data_get($productData, 'crossReferences');
        if (!is_array($crossReferences)) {
            $crossReferences = [];
        }

        $itemClassId          = data_get($productData, 'itemClass.id', data_get($productData, 'itemClassId'));
        $itemClassDescription = data_get($productData, 'itemClass.description');

        $priceClassId          = data_get($productData, 'priceClass.id', data_get($productData, 'priceClassId', data_get($productData, 'priceClassID')));
        $priceClassDescription = data_get($productData, 'priceClass.description');

        $body = data_get($productData, 'body');

        return [
            'sku'                           => $this->stringValue(data_get($productData, 'inventoryNumber', data_get($productData, 'inventoryId'))) ?? '',
            'inventory_id'                  => $this->stringValue(data_get($productData, 'inventoryNumber', data_get($productData, 'inventoryId'))) ?? '',
            'name'                          => $this->stringValue(data_get($productData, 'description', data_get($productData, 'inventoryNumber', ''))) ?? '',
            'description'                   => $this->normalizeDescription($body, data_get($productData, 'description')),
            'body'                          => is_string($body) ? $body : null,
            'status'                        => $this->stringValue(data_get($productData, 'status')),
            'product_type'                  => $this->stringValue(data_get($productData, 'type')),
            'category'                      => $this->stringValue(data_get($productData, 'itemClass.description', data_get($productData, 'itemClassId', ''))) ?? '',
            'item_class_id'                 => $this->stringValue($itemClassId),
            'item_class_description'        => $this->stringValue($itemClassDescription),
            'image'                         => $this->stringValue(data_get($productData, 'imageUrl')) ?? '',
            'price'                         => (float) $defaultPrice,
            'recommended_price'             => $this->floatValue(data_get($productData, 'recommendedPrice')),
            'current_cost'                  => $this->floatValue(data_get($productData, 'currentCost')),
            'last_cost'                     => $this->floatValue(data_get($productData, 'lastCost')),
            'item_price_class_id'           => $this->stringValue(data_get($productData, 'priceClassId', data_get($productData, 'priceClassID'))),
            'price_class_id'                => $this->stringValue($priceClassId),
            'price_class_description'       => $this->stringValue($priceClassDescription),
            'brand'                         => $this->stringValue($attributes['MERKE'] ?? null),
            'short_description'             => $this->stringValue($attributes['SHORTDESC'] ?? null),
            'volume'                        => $this->stringValue($attributes['VOLUM'] ?? null),
            'color_code'                    => $this->stringValue($attributes['FARGE'] ?? null),
            'kasselov_code'                 => $this->stringValue($attributes['KASSELOV'] ?? null),
            'is_hazardous'                  => $this->attributeFlag($attributes['FARLIG'] ?? null),
            'is_display_only'               => $this->attributeFlag($attributes['DISPONLY'] ?? null),
            'is_parent'                     => $this->attributeFlag($attributes['ISPARENT'] ?? null),
            'is_web_item'                   => $this->attributeFlag($attributes['WEBVARE'] ?? null),
            'is_web_item_b2b'               => $this->attributeFlag($attributes['WEBVAREB2B'] ?? null),
            'is_web_item_b2c'               => $this->attributeFlag($attributes['WEBVAREB2C'] ?? null),
            'stock_item'                    => $this->attributeFlag(data_get($productData, 'stockItem')),
            'kit_item'                      => $this->attributeFlag(data_get($productData, 'kitItem')),
            'base_unit'                     => $this->stringValue(data_get($productData, 'baseUnit')),
            'sales_unit'                    => $this->stringValue(data_get($productData, 'salesUnit')),
            'purchase_unit'                 => $this->stringValue(data_get($productData, 'purchaseUnit')),
            'default_warehouse_id'          => $this->stringValue(data_get($productData, 'defaultWarehouse.id')),
            'default_issue_from'            => $this->stringValue(data_get($productData, 'defaultIssueFrom.id')),
            'default_receipt_to'            => $this->stringValue(data_get($productData, 'defaultReceiptTo.id')),
            'weight'                        => $this->floatValue(data_get($productData, 'packaging.baseItemWeight')),
            'weight_uom'                    => $this->stringValue(data_get($productData, 'packaging.weightUOM')),
            'volume_value'                  => $this->floatValue(data_get($productData, 'packaging.baseItemVolume')),
            'volume_uom'                    => $this->stringValue(data_get($productData, 'packaging.volumeUOM')),
            'country_of_origin'             => $this->stringValue(data_get($productData, 'intrastat.countryOfOrigin')),
            'supplementary_measure_unit'    => $this->stringValue(data_get($productData, 'intrastat.supplementaryMeasureUnit')),
            'vat_code_id'                   => $this->stringValue(data_get($productData, 'vatCode.id')),
            'vat_code_description'          => $this->stringValue(data_get($productData, 'vatCode.description')),
            'attributes_data'               => !empty($rawAttributes) ? $rawAttributes : null,
            'warehouse_details'             => !empty($warehouseDetails) ? $warehouseDetails : null,
            'cross_references'              => !empty($crossReferences) ? $crossReferences : null,
            'quantity_on_hand'              => $this->floatValue(data_get($primaryWarehouse, 'quantityOnHand')),
            'quantity_available'            => $this->floatValue(data_get($primaryWarehouse, 'available')),
            'quantity_available_for_shipment' => $this->floatValue(data_get($primaryWarehouse, 'availableForShipment')),
            'rating_rate'                   => $this->floatValue(data_get($productData, 'rating.rate')),
            'rating_count'                  => $this->intValue(data_get($productData, 'rating.count')),
            'last_modified_at'              => $this->parseDateTime(data_get($productData, 'lastModifiedDateTime')),
            'visma_timestamp'               => $this->stringValue(data_get($productData, 'timestamp')),
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
        if (is_numeric($value)) return (int) $value === 1;
        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['1', 'true', 'yes', 'y'], true);
        }
        return false;
    }

    private function stringValue($value): ?string
    {
        if (is_null($value)) return null;
        if (is_scalar($value)) {
            $string = trim((string) $value);
            return $string === '' ? null : $string;
        }
        return null;
    }

    private function floatValue($value): ?float
    {
        if (is_null($value)) return null;
        return is_numeric($value) ? (float) $value : null;
    }

    private function intValue($value): ?int
    {
        if (is_null($value)) return null;
        return is_numeric($value) ? (int) $value : null;
    }

    private function parseDateTime($value): ?Carbon
    {
        if (blank($value)) return null;
        try {
            return Carbon::parse($value);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function normalizeDescription($body, $fallback): string
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


    /**
     * Get OAuth token using client credentials.
     */
    private function getAccessToken(): ?string
    {
        $now = time();
        if ($this->accessToken && $this->tokenExpiresAt && $this->tokenExpiresAt > $now) {
            return $this->accessToken;
        }

        $url         = 'https://connect.visma.com/connect/token';



        try {
            $response = Http::asForm()->timeout(30)->post($url, [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->s->client_id,
                'client_secret' => $this->s->client_secret,
                'scope'         => $this->s->financeScope(),
                'tenant_id'     => $this->s->tenantId(),
            ]);
        } catch (\Throwable $e) {
            $this->error('Error fetching access token: ' . $e->getMessage());
            return null;
        }

        if ($response->failed()) {
            $this->error(sprintf('Token request failed (HTTP %d): %s', $response->status(), (string) $response->body()));
            return null;
        }

        $data  = $response->json();
        $token = (string) data_get($data, 'access_token');
        $ttl   = (int) data_get($data, 'expires_in', 3600);

        if (!$token) {
            $this->error('Error: No access token returned.');
            return null;
        }

        $this->accessToken   = $token;
        $this->tokenExpiresAt = $now + max($ttl - 60, 0);

        return $token;
    }
}
