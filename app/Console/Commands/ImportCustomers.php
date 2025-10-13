<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use App\Models\VismaSettings;


class ImportCustomers extends Command
{
    private const DEFAULT_BASE_URL = 'https://api.finance.visma.net/v1';

    protected $signature = 'app:import-customers {--status=Active} {--page-size=500}';
    protected $description = 'Synchronise customers from Visma.net ERP.';

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
        $status   = (string) $this->option('status');
        $pageSize = (int) $this->option('page-size');
        if ($pageSize < 1 || $pageSize > 500) {
            $pageSize = 500;
        }

        if (!$this->getAccessToken()) {
            $this->error('Unable to fetch access token. Aborting import.');
            return self::FAILURE;
        }

        $page = 1;
        $total = 0;
        while (true) {
            $rows = $this->fetchCustomers($page, $pageSize, $status);
            if ($rows === null) return self::FAILURE;
            if (empty($rows)) break;

            $imported = 0;
            foreach ($rows as $row) {
                $number = data_get($row, 'number');
                if (blank($number)) {
                    $this->warn('Skipped customer without number.');
                    continue;
                }
                Customer::updateOrCreate(['number' => $number], $this->mapCustomerData($row));
                $imported++;
                $total++;
            }
            $this->info("Imported {$imported} customers from page {$page}.");
            if (count($rows) < $pageSize) break; // last page
            $page++;
        }

        $this->info("Customer import completed. {$total} customers synchronised.");
        return self::SUCCESS;
    }

    private function http(): PendingRequest
    {

        $baseUrl = $this->s->financeBaseUrl();
        $client = Http::withToken($this->getAccessToken() ?? '')
            ->acceptJson()->baseUrl($baseUrl)->timeout(60)->retry(3, 250, throw: false);
        if ($this->s->http_debug) {
            $stream = @fopen(storage_path('logs/visma-http.log'), 'ab');
            $client = $client->withOptions(['debug' => $stream]);
        }
        return $client;
    }

    private function fetchCustomers(int $page, int $pageSize, string $status): ?array
    {
        try {
            $r = $this->http()->get('customer', ['status' => $status, 'pageNumber' => $page, 'pageSize' => $pageSize]);
        } catch (\Throwable $e) {
            $this->error("HTTP error on page {$page}: {$e->getMessage()}");
            return null;
        }
        if ($r->failed()) {
            $this->error("Failed to fetch customers (HTTP {$r->status()}).");
            $this->line((string) $r->body());
            return null;
        }
        $p = $r->json();
        if (isset($p['data']) && is_array($p['data'])) return $p['data'];
        if (is_array($p)) return $p; // flat array variant
        $this->error('Unexpected customer payload structure.');
        return null;
    }

    private function mapCustomerData(array $c): array
    {
        $mainAddress     = (array) data_get($c, 'mainAddress', []);
        $invoiceAddress  = (array) data_get($c, 'invoiceAddress', []);
        $deliveryAddress = (array) data_get($c, 'deliveryAddress', []);

        $mainContact     = (array) data_get($c, 'mainContact', []);
        $invoiceContact  = (array) data_get($c, 'invoiceContact', []);
        $deliveryContact = (array) data_get($c, 'deliveryContact', []);

        $paymentSettings = (array) data_get($c, 'paymentSettings', []);
        $financialInfo   = (array) data_get($c, 'financialInformation', []);
        $attributes      = (array) data_get($c, 'attributes', []);
        $customFields    = (array) data_get($c, 'customFields', []);

        $priceClassId          = data_get($c, 'priceClass.id', data_get($c, 'priceClassId'));
        $priceClassDescription = data_get($c, 'priceClass.description');
        $customerClassId       = data_get($c, 'customerClass.id', data_get($c, 'customerClassId'));
        $customerClassDesc     = data_get($c, 'customerClass.description');
        $currencyId            = data_get($c, 'currencyId', data_get($c, 'currency'));
        $languageId            = data_get($c, 'languageId');
        $salesPersonId         = data_get($c, 'salesPerson.id', data_get($c, 'salesPersonId'));
        $branchId              = data_get($c, 'branch.id', data_get($c, 'branchId'));
        $defaultLocationId     = data_get($c, 'defaultLocationId');
        $shipViaId             = data_get($c, 'shipVia.id', data_get($c, 'shipViaId'));
        $deliveryTermsId       = data_get($c, 'deliveryTerms.id', data_get($c, 'deliveryTermsId'));
        $taxZoneId             = data_get($c, 'taxZone.id', data_get($c, 'taxZoneId'));
        $vatCodeId             = data_get($c, 'vatCode.id');

        $einvoiceParticipantId = data_get($c, 'einvoice.participantId', data_get($c, 'peppolParticipantId'));
        $einvoiceAddress       = data_get($c, 'einvoice.address');
        $einvoiceOperator      = data_get($c, 'einvoice.operator');
        $edocEmail             = data_get($c, 'edoc.email');
        $edocEnabled           = data_get($c, 'edoc.enabled');

        return [
            'number'                        => (string) data_get($c, 'number'),
            'name'                          => (string) data_get($c, 'name', ''),
            'status'                        => data_get($c, 'status'),
            'corporateId'                   => data_get($c, 'corporateId'),

            'customer_class_id'             => $customerClassId,
            'customer_class_description'    => $customerClassDesc,
            'price_class_id'                => $priceClassId,
            'price_class_description'       => $priceClassDescription,
            'currency_id'                   => $currencyId,
            'language_id'                   => $languageId,
            'sales_person_id'               => $salesPersonId,
            'branch_id'                     => $branchId,
            'default_location_id'           => $defaultLocationId,

            'credit_limit'                  => data_get($c, 'creditLimit'),
            'credit_hold'                   => (bool) data_get($c, 'creditHold', false),
            'balance'                       => data_get($c, 'balance'),
            'overdue_balance'               => data_get($c, 'overdueBalance'),
            'terms_id'                      => data_get($paymentSettings, 'termsId'),
            'payment_method_id'             => data_get($paymentSettings, 'paymentMethodId'),
            'cash_discount_id'              => data_get($paymentSettings, 'cashDiscountId'),

            'tax_zone_id'                   => $taxZoneId,
            'vat_code_id'                   => $vatCodeId,
            'vat_registration_id'           => data_get($c, 'vatRegistrationId', data_get($c, 'vatId')),
            'vat_exempt'                    => (bool) data_get($c, 'vatExempt', false),

            'ship_via_id'                   => $shipViaId,
            'delivery_terms_id'             => $deliveryTermsId,

            'einvoice_participant_id'       => $einvoiceParticipantId,
            'einvoice_address'              => $einvoiceAddress,
            'einvoice_operator'             => $einvoiceOperator,
            'edoc_email'                    => $edocEmail,
            'edoc_enabled'                  => is_bool($edocEnabled) ? $edocEnabled : null,

            'main_address_line1'            => data_get($mainAddress, 'addressLine1'),
            'main_address_line2'            => data_get($mainAddress, 'addressLine2'),
            'main_postal_code'              => data_get($mainAddress, 'postalCode'),
            'main_city'                     => data_get($mainAddress, 'city'),
            'main_country'                  => data_get($mainAddress, 'country.name'),
            'main_country_id'               => data_get($mainAddress, 'country.id', data_get($mainAddress, 'countryId')),
            'main_county'                   => data_get($mainAddress, 'county.name'),

            'invoice_address_line1'         => data_get($invoiceAddress, 'addressLine1'),
            'invoice_address_line2'         => data_get($invoiceAddress, 'addressLine2'),
            'invoice_postal_code'           => data_get($invoiceAddress, 'postalCode'),
            'invoice_city'                  => data_get($invoiceAddress, 'city'),
            'invoice_country'               => data_get($invoiceAddress, 'country.name'),
            'invoice_country_id'            => data_get($invoiceAddress, 'country.id', data_get($invoiceAddress, 'countryId')),
            'invoice_county'                => data_get($invoiceAddress, 'county.name'),

            'delivery_address_line1'        => data_get($deliveryAddress, 'addressLine1'),
            'delivery_address_line2'        => data_get($deliveryAddress, 'addressLine2'),
            'delivery_postal_code'          => data_get($deliveryAddress, 'postalCode'),
            'delivery_city'                 => data_get($deliveryAddress, 'city'),
            'delivery_country'              => data_get($deliveryAddress, 'country.name'),
            'delivery_country_id'           => data_get($deliveryAddress, 'country.id', data_get($deliveryAddress, 'countryId')),
            'delivery_county'               => data_get($deliveryAddress, 'county.name'),

            'main_contact_name'             => data_get($mainContact, 'name'),
            'main_contact_attention'        => data_get($mainContact, 'attention'),
            'main_contact_email'            => data_get($mainContact, 'email'),
            'main_contact_phone'            => data_get($mainContact, 'phone1'),
            'main_contact_phone2'           => data_get($mainContact, 'phone2'),

            'invoice_contact_name'          => data_get($invoiceContact, 'name'),
            'invoice_contact_attention'     => data_get($invoiceContact, 'attention'),
            'invoice_contact_email'         => data_get($invoiceContact, 'email'),
            'invoice_contact_phone'         => data_get($invoiceContact, 'phone1'),

            'delivery_contact_name'         => data_get($deliveryContact, 'name'),
            'delivery_contact_attention'    => data_get($deliveryContact, 'attention'),
            'delivery_contact_email'        => data_get($deliveryContact, 'email'),
            'delivery_contact_phone'        => data_get($deliveryContact, 'phone1'),

            'payment_settings'              => !empty($paymentSettings) ? $paymentSettings : null,
            'financial_information'         => !empty($financialInfo)   ? $financialInfo   : null,
            'attributes_data'               => !empty($attributes)      ? $attributes      : null,
            'main_address_json'             => !empty($mainAddress)     ? $mainAddress     : null,
            'invoice_address_json'          => !empty($invoiceAddress)  ? $invoiceAddress  : null,
            'delivery_address_json'         => !empty($deliveryAddress) ? $deliveryAddress : null,
            'main_contact_json'             => !empty($mainContact)     ? $mainContact     : null,
            'invoice_contact_json'          => !empty($invoiceContact)  ? $invoiceContact  : null,
            'delivery_contact_json'         => !empty($deliveryContact) ? $deliveryContact : null,
            'custom_fields'                 => !empty($customFields)    ? $customFields    : null,
            'raw_payload'                   => $c,
        ];
    }

    private function getAccessToken(): ?string
    {
        $now = time();
        $s = VismaSettings::active();

        if ($this->accessToken && $this->tokenExpiresAt && $this->tokenExpiresAt > $now) return $this->accessToken;

        $url = 'https://connect.visma.com/connect/token';
        //$clientId = env('VISMA_CLIENT_ID');
        //$clientSecret = env('VISMA_CLIENT_SECRET');
        //$tenantId = env('VISMA_TENANT_ID');
        // Request both scopes so token has finance audience (and salesorder if needed)
        //$scope = 'vismanet_erp_service_api:read visma.net.erp.salesorder:read';

        try {
            $r = Http::asForm()->timeout(30)->post($url, [
                'grant_type'    => 'client_credentials',
                'client_id'     => $s->client_id,
                'client_secret' => $s->client_secret,
                'tenant_id'     => $s->tenantId(),
                'scope'         => $s->financeScope(),
            ]);
        } catch (\Throwable $e) {
            $this->error('Error fetching access token: ' . $e->getMessage());
            return null;
        }

        if ($r->failed()) {
            $this->error(sprintf('Token request failed (HTTP %d): %s', $r->status(), (string) $r->body()));
            return null;
        }
        $data  = $r->json();
        $token = (string) data_get($data, 'access_token');
        $ttl   = (int) data_get($data, 'expires_in', 3600);
        if (!$token) {
            $this->error('Error: No access token returned.');
            return null;
        }
        $this->accessToken = $token;
        $this->tokenExpiresAt = $now + max($ttl - 60, 0);
        return $token;
    }
}
