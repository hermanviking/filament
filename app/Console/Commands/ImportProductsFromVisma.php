<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\Products; // Assuming you have a Product model

class ImportProductsFromVisma extends Command
{
    protected $signature = 'import:visma-products';
    protected $description = 'Import products from Visma.net API';

    private $client;
    private $accessToken;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client();
    }

    public function handle()
    {
        $this->info('Fetching products from Visma.net API...');

        // Obtain access token
        $this->accessToken = $this->getAccessToken();

        // Display message if the token is successfully received
        if ($this->accessToken) {
            $this->info('Access token successfully received.');
        } else {
            $this->error('Failed to receive access token.');
            return; // Exit early if the token couldn't be retrieved
        }

        // Fetch products (pagination is handled here)
        $page = 1;
        $products = [];
        $hasMoreProducts = true;

        while ($hasMoreProducts) {
            $fetchedProducts = $this->fetchProducts($page);

            $this->info('Fetched Products for page ' . $page . ': ' . json_encode($fetchedProducts, JSON_PRETTY_PRINT));

            if (empty($fetchedProducts)) {
                $this->error('No products fetched or error in fetching.');
                break;
            }

            $products = array_merge($products, $fetchedProducts);

            // Check if there's another page of products
            $hasMoreProducts = count($fetchedProducts) >= 10; // Assuming 10 is the page size
            $page++;
        }

        if (!empty($products)) {
            // Store products in the database
            foreach ($products as $productData) {
                Product::updateOrCreate(
                    ['inventoryId' => $productData['id']], // Assuming the 'id' can be used as SKU
                    [
                        'description' => $productData['name'],
                        'defaultPrice' => $productData['price'],
                    ]
                );
            }
            $this->info('Products imported successfully!');
        } else {
            $this->info('No products to import.');
        }
    }

    private function getAccessToken()
    {
        $url = 'https://connect.visma.com/connect/token'; // Update with the correct token URL
        $clientId = env('VISMA_CLIENT_ID'); // Set your client ID in .env
        $clientSecret = env('VISMA_CLIENT_SECRET'); // Set your client secret in .env
        $tenantId = env('VISMA_TENANT_ID'); // Set your tenant ID in .env

        try {
            $response = $this->client->post($url, [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => 'vismanet_erp_service_api:read', // Adjust if needed
                    'tenant_id' => $tenantId // Add tenant_id to the request
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (!isset($data['access_token'])) {
                $this->error('Error: No access token returned.');
                return null;  // Return null if access token is not received
            }

            return $data['access_token'];  // Return the access token if received
        } catch (\Exception $e) {
            $this->error('Error fetching access token: ' . $e->getMessage());
            return null;  // Return null on error
        }
    }

    function fetchProducts($page)
    {
        $url = 'https://integration.visma.net/API/controller/api/v1/inventory?pageSize=10&page=' . $page; // Add pagination to the URL
    
        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json',
                ],
            ]);
    
            $data = json_decode($response->getBody(), true);
    
            // Log the raw API Response for debugging purposes
            $this->info('Raw API Response for page ' . $page . ': ' . json_encode($data));
    
            // Check if the response contains the expected products
            return isset($data['data']) ? $data['data'] : []; // Adjust based on actual response structure
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // Handle the error and capture the full response body
            $responseBody = $e->getResponse() ? (string) $e->getResponse()->getBody() : 'No response body';
            $this->error('Error fetching products from page ' . $page . ': ' . $e->getMessage());
            $this->error('Full API Error Response: ' . $responseBody); // Log the full error response
    
            return [];
        }
    }

    protected function processData(array $data)
    {
        // Example: Iterate over the data and perform actions (e.g., saving to DB)
        foreach ($data as $item) {
            Product::updateOrCreate(
                ['sku' => $item['inventoryId']], // Assuming the 'id' can be used as SKU
                [
                    'name' => $item['description'],
                    'price' => $item['defaultPrice'],
                ]
            );
            
        }

        $this->info('Data processed successfully!');
    }}
