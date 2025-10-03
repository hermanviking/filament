<?php

namespace App\Services;

use GuzzleHttp\Client;

class MagentoIntegration
{
    private $client;
    private $accessToken;
    private $magentoUrl;

    // Constructor receives the Access Token and Magento URL from the .env file
    public function __construct()
    {
        $this->client = new Client();
        $this->accessToken = env('MAGENTO_API_TOKEN'); // Retrieve the Magento Access Token from .env
        $this->magentoUrl = env('MAGENTO_API_URL'); // Retrieve the Magento URL from .env
    }

    // Function to create product in Magento
    public function createProduct($productData)
    {
        // Magento API endpoint for creating a product
        $url = $this->magentoUrl . '/products';

        // Prepare data for the product
        $data = [
            'product' => [
                'sku' => $productData['sku'],
                'name' => $productData['name'],
                'price' => $productData['price'],
                'status' => 1,  // 1 means "Enabled", 2 means "Disabled"
                'visibility' => 4,  // 4 means "Catalog, Search"
                'type_id' => 'simple',  // Simple product type
                'attribute_set_id' => 4,  // Make sure this ID exists in your Magento setup




            ],
            'saveOptions' => true,
        ];

        try {
            // Send POST request to Magento API
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);

            // Parse the response
            $responseData = json_decode($response->getBody(), true);

            if ($response->getStatusCode() == 200) {
                return $responseData;  // Return the Magento response if successful
            } else {
                return ['error' => $responseData];  // Return the error response
            }

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];  // Return any exception message
        }
    }

    /**
     * Create bundle product in Magento.
     */
    public function createBundleProduct($bundleData)
    {
        // Magento API endpoint for creating a product
        $url = $this->magentoUrl . '/products';

        // Prepare the bundle data for the product
        $data = [
            'product' => [
                'sku' => $bundleData['sku'],
                'name' => $bundleData['name'],
                'price' => (float) $bundleData['price'], // Cast price to float to match Magento's expectation
                'status' => 1, // 1 means "Enabled"
                'visibility' => 4, // 4 means "Catalog, Search"
                'type_id' => 'bundle', // Product type as "bundle"
                'attribute_set_id' => 4, // Make sure this ID exists in your Magento setup
                'extension_attributes' => [
                    'bundle_product_options' => $this->prepareBundleOptions($bundleData['items'])
                ],
            ],
            'saveOptions' => true,
        ];

        try {
            // Send POST request to Magento API
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);

            // Parse the response
            $responseData = json_decode($response->getBody(), true);

            if ($response->getStatusCode() == 200) {
                return $responseData;  // Return the Magento response if successful
            } else {
                return ['error' => $responseData];  // Return the error response
            }

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];  // Return any exception message
        }
    }


    /**
     * Prepare bundle options for the Magento API.
     */
    private function prepareBundleOptions($items)
    {
        $options = [];

        foreach ($items as $item) {
            $product = $item['product'];

            $options[] = [
                'option_id' => 0, // Leave 0 or null for new option
                'title' => $product['name'], // The title of the bundle option
                'required' => true, // Whether this option is required
                'type' => 'select', // The option type, e.g., 'select', 'radio'
                'position' => 0, // The position of the option
                'sku' => null, // Typically leave this as null for bundle options
                'product_links' => $this->prepareProductLinks($item)
            ];
        }

        return $options;
    }

    private function prepareProductLinks($item)
    {
        $product = $item['product'];

        return [
            [
                'sku' => $product['sku'], // SKU of the product being linked
                'qty' => (float) $item['quantity'], // Ensure quantity is a float
                'position' => 1, // Position in the option list
                'is_default' => true, // Is this the default selection?
                'price' => (float) $product['price'], // Price of the linked product
                'price_type' => 'fixed' // 'fixed' or 'percent'
            ]
        ];
    }


}
