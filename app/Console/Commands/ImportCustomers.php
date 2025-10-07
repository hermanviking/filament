<?php

namespace App\Console\Commands;

use App\Models\Customer;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportCustomers extends Command
{
    private const PAGE_SIZE = 500;

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
    protected $description = 'Synchronise customers from Visma.net ERP.';

    private Client $client;
    private ?string $accessToken = null;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client();
    }

    public function handle(): int
    {
        $this->accessToken = $this->getAccessToken();

        if (!$this->accessToken) {
            $this->error('Unable to fetch access token. Aborting import.');

            return self::FAILURE;
        }

        $page = 1;
        $totalImported = 0;

        while (true) {
            $customers = $this->fetchCustomers($page);

            if ($customers === null) {
                return self::FAILURE;
            }

            if (empty($customers)) {
                break;
            }

            $importedThisPage = 0;

            foreach ($customers as $customerData) {
                $number = data_get($customerData, 'number');

                if (blank($number)) {
                    $this->warn('Skipped customer without a number.');
                    continue;
                }

                Customer::updateOrCreate(
                    ['number' => $number],
                    $this->mapCustomerData($customerData)
                );

                $importedThisPage++;
                $totalImported++;
            }

            $this->info(sprintf('Imported %d customers from page %d.', $importedThisPage, $page));

            if (count($customers) < self::PAGE_SIZE) {
                break;
            }

            $page++;
        }

        $this->info(sprintf('Customer import completed. %d customers synchronised.', $totalImported));

        return self::SUCCESS;
    }

    private function fetchCustomers(int $page): ?array
    {
        $response = Http::withToken($this->accessToken)
            ->acceptJson()
            ->get('https://integration.visma.net/API/controller/api/v1/customer', [
                'status' => 'Active',
                'pageNumber' => $page,
                'pageSize' => self::PAGE_SIZE,
            ]);

        if ($response->failed()) {
            $this->error(sprintf('Failed to fetch customers from Visma (status %d).', $response->status()));
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

        $this->error('Unexpected customer payload structure returned from Visma.');

        return null;
    }

    private function mapCustomerData(array $customerData): array
    {
        return [
            'name' => data_get($customerData, 'name', ''),
            'corporateId' => data_get($customerData, 'corporateId'),
            'main_address_line1' => data_get($customerData, 'mainAddress.addressLine1'),
            'main_postal_code' => data_get($customerData, 'mainAddress.postalCode'),
            'main_city' => data_get($customerData, 'mainAddress.city'),
            'main_country' => data_get($customerData, 'mainAddress.country.name'),
            'main_county' => data_get($customerData, 'mainAddress.county.name'),
            'invoice_address_line1' => data_get($customerData, 'invoiceAddress.addressLine1'),
            'invoice_postal_code' => data_get($customerData, 'invoiceAddress.postalCode'),
            'invoice_city' => data_get($customerData, 'invoiceAddress.city'),
            'invoice_country' => data_get($customerData, 'invoiceAddress.country.name'),
            'invoice_county' => data_get($customerData, 'invoiceAddress.county.name'),
            'delivery_address_line1' => data_get($customerData, 'deliveryAddress.addressLine1'),
            'delivery_postal_code' => data_get($customerData, 'deliveryAddress.postalCode'),
            'delivery_city' => data_get($customerData, 'deliveryAddress.city'),
            'delivery_country' => data_get($customerData, 'deliveryAddress.country.name'),
            'delivery_county' => data_get($customerData, 'deliveryAddress.county.name'),
            'main_contact_name' => data_get($customerData, 'mainContact.name'),
            'main_contact_attention' => data_get($customerData, 'mainContact.attention'),
            'main_contact_email' => data_get($customerData, 'mainContact.email'),
            'main_contact_phone' => data_get($customerData, 'mainContact.phone1'),
            'invoice_contact_name' => data_get($customerData, 'invoiceContact.name'),
            'invoice_contact_attention' => data_get($customerData, 'invoiceContact.attention'),
            'invoice_contact_email' => data_get($customerData, 'invoiceContact.email'),
            'invoice_contact_phone' => data_get($customerData, 'invoiceContact.phone1'),
            'delivery_contact_name' => data_get($customerData, 'deliveryContact.name'),
            'delivery_contact_attention' => data_get($customerData, 'deliveryContact.attention'),
            'delivery_contact_email' => data_get($customerData, 'deliveryContact.email'),
            'delivery_contact_phone' => data_get($customerData, 'deliveryContact.phone1'),
            'customer_price_class_id' => data_get($customerData, 'priceClass.id', data_get($customerData, 'priceClassId')),
        ];
    }

    private function getAccessToken(): ?string
    {
        $url = 'https://connect.visma.com/connect/token';
        $clientId = env('VISMA_CLIENT_ID');
        $clientSecret = env('VISMA_CLIENT_SECRET');
        $tenantId = env('VISMA_TENANT_ID_LIVE');

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

        $data = json_decode($response->getBody()->getContents(), true);

        if (!is_array($data) || !isset($data['access_token'])) {
            $this->error('Error: No access token returned.');

            return null;
        }

        return $data['access_token'];
    }
}
