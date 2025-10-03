<?php

namespace App\Console\Commands;
use GuzzleHttp\Client;
use App\Models\Products; // Assuming you have a Product model
use Illuminate\Support\Facades\Http;


use Illuminate\Console\Command;

class import extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $client;
    private $accessToken;

    /**
     * Execute the console command.
     */
    public function __construct()
    {
        parent::__construct();
        $this->client = new Client();  // Initialize Guzzle client
    }

    public function handle()
    {
        // Get access token
        $this->accessToken = $this->getAccessToken();
        if (!$this->accessToken) {
            return;  // Stop if we couldn't get the access token
        }

        // Define the API URL for fetching products
        $url = 'https://integration.visma.net/API/controller/api/v1/inventory?status=Active&pageSize=2000&page=1&inventoryTypes=FinishedGoodItem';
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json',
        ])->get($url);

        // Fetch data from Visma API
   //     try {
     //       $response = $this->client->get($url, [
       //         'headers' => [
         //           'Authorization' => 'Bearer ' . $this->accessToken,
           //         'Content-Type' => 'application/json',
             //   ],
           // ]);

            // Fetch the response body
$responseBody = $response->getBody()->getContents();
$test = json_decode($responseBody,true);

// Display the raw response body in the console
//$this->info('Raw API Response:');
//$this->info(print_r($test));
       // } catch (\Exception $e) {
         //   $this->error('Error fetching products from Visma API: ' . $e->getMessage());
           // return;
       // }

        // Check if the request was successful
        if ($response->getStatusCode() === 200) {
            $products = $response->json();
            
            // Log or print the response to inspect its structure
            
            // Alternatively, you can use dd() to debug
            // dd($products);
            
            // Now try accessing the products as per the actual structure
            foreach ($products as $productData) {
                Products::updateOrCreate(
                    ['sku' => $productData['inventoryNumber']], // Assuming the 'inventoryId' is the SKU
                    ['name' => $productData['description']??'',
                        'price' => $productData['defaultPrice'],
                        'description'=>$productData['inventoryId'],
                        'category' =>'test',
                        'image' =>'test',
                        'item_price_class_id' => $productData['priceClassID']??''
                    ]
                );
            }
        
            $this->info('Products imported successfully from Visma API.');
        } else {
            $this->error('Failed to fetch data from Visma API. Status code: ' . $response->getStatusCode());
        }
        
    }

    private function getAccessToken()
    {
        $url = 'https://connect.visma.com/connect/token'; // URL to get the access token
        $clientId = env('VISMA_CLIENT_ID'); // Set your client ID in .env
        $clientSecret = env('VISMA_CLIENT_SECRET'); // Set your client secret in .env
        $tenantId = env('VISMA_TENANT_ID_LIVE'); // Set your tenant ID in .env

        try {
            // Get the access token from Visma
            $response = $this->client->post($url, [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => 'vismanet_erp_service_api:read', // Adjust if needed
                    'tenant_id' => $tenantId // Add tenant_id to the request
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

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
}

