<?php

namespace App\Console\Commands;

use App\Models\Products;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportProductsFromVisma extends Command
{
    private const PAGE_SIZE = 100;

    protected $signature = 'import:visma-products';
    protected $description = 'Import products from Visma.net API';

    private Client $client;
    private ?string $accessToken = null;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client();
    }

    public function handle(): int
    {
        $this->info('Fetching products from Visma.net API...');

        $this->accessToken = $this->getAccessToken();

        if (!$this->accessToken) {
            $this->error('Failed to receive access token.');

            return self::FAILURE;
        }

        $page = 1;
        $totalImported = 0;

        while (true) {
            $products = $this->fetchProducts($page);

            if ($products === null) {
                return self::FAILURE;
            }

            if (empty($products)) {
                break;
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

            if (count($products) < self::PAGE_SIZE) {
                break;
            }

            $page++;
        }

        $this->info(sprintf('Products imported successfully (%d items).', $totalImported));

        return self::SUCCESS;
    }

    private function mapProductData(array $productData): array
    {
        $defaultPrice = data_get($productData, 'defaultPrice.amount', data_get($productData, 'defaultPrice', 0));

        if (is_array($defaultPrice)) {
            $defaultPrice = 0;
        }

        return [
            'sku' => data_get($productData, 'inventoryNumber', data_get($productData, 'inventoryId')),
            'name' => data_get($productData, 'description', data_get($productData, 'inventoryNumber', '')),
            'description' => data_get($productData, 'longDescription', data_get($productData, 'description', '')),
            'category' => data_get($productData, 'itemClassId', data_get($productData, 'itemClass', '')),
            'image' => data_get($productData, 'imageUrl'),
            'price' => (float) $defaultPrice,
            'item_price_class_id' => data_get($productData, 'priceClassId', data_get($productData, 'priceClassID')),
            'rating_rate' => data_get($productData, 'rating.rate'),
            'rating_count' => data_get($productData, 'rating.count'),
        ];
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
            $this->error(sprintf('Error fetching products from page %d (status %d).', $page, $response->status()));
            $this->error((string) $response->body());

            return null;
        }

        $payload = $response->json();

        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        if (is_array($payload)) {
            return $payload;
        }

        $this->error('Unexpected product payload structure.');

        return null;
    }

    private function getAccessToken(): ?string
    {
        $url = 'https://connect.visma.com/connect/token';
        $clientId = env('VISMA_CLIENT_ID');
        $clientSecret = env('VISMA_CLIENT_SECRET');
        $tenantId = env('VISMA_TENANT_ID');

        try {
            $response = $this->client->post($url, [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => 'vismanet_erp_service_api:read',
                    'tenant_id' => $tenantId,
                ],
            ]);
        } catch (\Throwable $exception) {
            $this->error('Error fetching access token: ' . $exception->getMessage());

            return null;
        }

        $data = json_decode($response->getBody(), true);

        if (!is_array($data) || !isset($data['access_token'])) {
            $this->error('Error: No access token returned.');

            return null;
        }

        return $data['access_token'];
    }
}
