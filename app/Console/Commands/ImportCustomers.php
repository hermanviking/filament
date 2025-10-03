<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use App\Models\Customer; // Assuming you have a Product model
use Illuminate\Support\Facades\Http;


use Illuminate\Console\Command;

class ImportCustomers extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-customers';

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
        $page = 1;
        $pageSize = 500; // Adjust as needed
        do {
            $url = "https://integration.visma.net/API/controller/api/v1/customer?status=Active&pageNumber={$page}&pageSize={$pageSize}
";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->get(
                $url
            );

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
            $test = json_decode($responseBody, true);

            // Display the raw response body in the console
            //$this->info('Raw API Response:');
            //$this->info(print_r($test));
            // } catch (\Exception $e) {
            //   $this->error('Error fetching products from Visma API: ' . $e->getMessage());
            // return;
            // }

            // Check if the request was successful
            if ($response->getStatusCode() === 200) {
                $customers = $response->json();

                // Log or print the response to inspect its structure

                // Alternatively, you can use dd() to debug
                // dd($customers);

                // Now try accessing the products as per the actual structure
                foreach ($customers as $customerData) {
                    // Ensure the data contains the address and contact fields, and update or create the customer accordingly
                    Customer::updateOrCreate(
                        ['number' => $customerData['number']], // Assuming the 'number' is unique for each customer
                        [
                            'name' => $customerData['name'] ?? '',

                            // Main Address Fields
                            'main_address_line1' => $customerData['mainAddress']['addressLine1'] ?? '',
                            'main_postal_code' => $customerData['mainAddress']['postalCode'] ?? '',
                            'main_city' => $customerData['mainAddress']['city'] ?? '',
                            'main_country' => $customerData['mainAddress']['country']['name'] ?? '',
                            'main_county' => $customerData['mainAddress']['county']['name'] ?? '',

                            // Invoice Address Fields
                            'invoice_address_line1' => $customerData['invoiceAddress']['addressLine1'] ?? '',
                            'invoice_postal_code' => $customerData['invoiceAddress']['postalCode'] ?? '',
                            'invoice_city' => $customerData['invoiceAddress']['city'] ?? '',
                            'invoice_country' => $customerData['invoiceAddress']['country']['name'] ?? '',
                            'invoice_county' => $customerData['invoiceAddress']['county']['name'] ?? '',

                            // Delivery Address Fields
                            'delivery_address_line1' => $customerData['deliveryAddress']['addressLine1'] ?? '',
                            'delivery_postal_code' => $customerData['deliveryAddress']['postalCode'] ?? '',
                            'delivery_city' => $customerData['deliveryAddress']['city'] ?? '',
                            'delivery_country' => $customerData['deliveryAddress']['country']['name'] ?? '',
                            'delivery_county' => $customerData['deliveryAddress']['county']['name'] ?? '',

                            // Main Contact Information
                            'main_contact_name' => $customerData['mainContact']['name'] ?? '',
                            'main_contact_attention' => $customerData['mainContact']['attention'] ?? '',
                            'main_contact_email' => $customerData['mainContact']['email'] ?? '',
                            'main_contact_phone' => $customerData['mainContact']['phone1'] ?? '',

                            // Invoice Contact Information
                            'invoice_contact_name' => $customerData['invoiceContact']['name'] ?? '',
                            'invoice_contact_attention' => $customerData['invoiceContact']['attention'] ?? '',
                            'invoice_contact_email' => $customerData['invoiceContact']['email'] ?? '',
                            'invoice_contact_phone' => $customerData['invoiceContact']['phone1'] ?? '',

                            // Delivery Contact Information (if you have it, otherwise this section is optional)
                            'delivery_contact_name' => $customerData['deliveryContact']['name'] ?? '',
                            'delivery_contact_attention' => $customerData['deliveryContact']['attention'] ?? '',
                            'delivery_contact_email' => $customerData['deliveryContact']['email'] ?? '',
                            'delivery_contact_phone' => $customerData['deliveryContact']['phone1'] ?? '',
                            'customer_price_class_id' => $customerData['priceClass']['id'] ?? '',

                            'corporateId' => $customerData['corporateId'] ?? null,


                            // Additional fields like VAT, credit terms, or any other attributes can be added here as necessary
                            // Example:
                            // 'credit_terms' => $customerData['creditTerms']['description'] ?? '',
                            // 'currency' => $customerData['currencyId'] ?? 'NOK',
                        ]
                    );
                }
                $page++;



                $this->info('Customers imported successfully from Visma API from page' . $page . 'and url: ' . $url);
            } else {
                $this->error('Failed to fetch data from Visma API. Status code: ' . $response->getStatusCode());
                break;
            }
        } while (count($customers) > 0);
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
