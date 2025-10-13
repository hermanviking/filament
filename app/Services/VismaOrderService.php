<?php

namespace App\Services;

use App\Models\VismaOrder;
use App\Models\VismaOrderItem;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Client\Response as HttpResponse;

class VismaOrderService
{
    private const DEFAULT_BASE_URL = 'https://salesorder.visma.net/api/v3';
    private const TOKEN_ENDPOINT   = 'https://connect.visma.com/connect/token';

    // ---- token caches (read + write) ---------------------------------------
    private ?string $accessToken = null;
    private ?int $tokenExpiresAt = null;

    private ?string $writeAccessToken = null;
    private ?int $writeTokenExpiresAt = null;

    //Debugger
    private function attachDebuggers(PendingRequest $client): PendingRequest
    {
        if (! (bool) env('VISMA_HTTP_DEBUG', false)) {
            return $client;
        }

        // Raw HTTP wire log to a file
        $stream = @fopen(storage_path('logs/visma-http.log'), 'ab');

        return $client
            // Uncomment during local/tinker if you want stdout dumps per request:
            // ->dump()

            ->withOptions(['debug' => $stream])
            ->beforeSending(function (HttpRequest $request, array $options) {
                $headers = $request->headers();
                unset($headers['Authorization']); // never log tokens

                Log::info('Visma: outgoing request', [
                    'method'  => $request->method(),
                    'url'     => (string) $request->url(),
                    'headers' => $headers,
                    'body'    => $request->body(), // JSON string for JSON requests
                ]);
            });
    }

    // ---- PUBLIC: Import many pages -----------------------------------------
    public function importAllOrders(int $top = 100, int $maxPages = 10, ?string $filter = null): int
    {
        $imported = 0;
        $nextUrl  = null;

        for ($i = 0; $i < $maxPages; $i++) {
            $page = $this->fetchOrdersPage($nextUrl, $top, $filter);
            $imported += $this->importOrdersFromListPayload($page);

            $nextUrl = $this->stringValue(data_get($page, 'nextPage'));
            if (!$nextUrl) break;
        }

        return $imported;
    }

    // ---- PUBLIC: Fetch a page ----------------------------------------------
    public function fetchOrdersPage(?string $nextUrl = null, int $top = 100, ?string $filter = null): array
    {
        if ($nextUrl) {
            // Accept absolute nextPage URL from API
            $response = $this->http()->get($nextUrl);
        } else {
            $params = array_filter([
                '$top'    => $top,
                '$filter' => $filter,
            ], fn($v) => !is_null($v));

            $params = array_merge($params, $this->expand(['Lines','Customer']));

            // NOTE: list endpoint is lowercase per what worked in tinker
            $response = $this->http()->get('salesorders', $params);
        }

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'Failed to fetch orders from Visma (HTTP %d): %s',
                $response->status(),
                (string) $response->body()
            ));
        }

        $json = $response->json();
        return is_array($json) ? $json : [];
    }

    // ---- PUBLIC: Import one list payload -----------------------------------
    public function importOrdersFromListPayload(array $listPayload): int
    {
        $rows = data_get($listPayload, 'value', []);
        if (!is_array($rows) || empty($rows)) return 0;

        $count = 0;

        DB::transaction(function () use ($rows, &$count) {
            foreach ($rows as $orderPayload) {
                $this->persistOrderFromPayload($orderPayload);
                $count++;
            }
        });

        return $count;
    }

    // ---- PUBLIC: Fetch single order ----------------------------------------
    public function fetchSingleOrder(string $type, string $orderId): array
    {
        // NOTE: single endpoint is PascalCase per what worked in tinker
        $url  = sprintf('SalesOrders/%s/%s', rawurlencode($type), rawurlencode($orderId));
        $resp = $this->http()->get($url, $this->expand(['Lines','Customer']));

        if ($resp->failed()) {
            throw new RuntimeException(sprintf(
                'Unable to retrieve sales order %s/%s (HTTP %d): %s',
                $type, $orderId, $resp->status(), (string) $resp->body()
            ));
        }

        $data = $resp->json();
        if (!is_array($data)) {
            throw new RuntimeException('Unexpected response when fetching sales order.');
        }

        return $data;
    }

    // ---- PUBLIC: Push updates to Visma (Open orders only) -------------------
    public function pushVismaOrder(VismaOrder $order): VismaOrder
    {
        if ($order->status !== 'Open') {
            throw new RuntimeException('Only "Open" orders can be updated in Visma.');
        }

        // 1) GET ETag using READ token (avoids 403 if write token lacks read)
        $getUrl = sprintf('SalesOrders/%s/%s', rawurlencode($order->type), rawurlencode($order->order_id));
        $get    = $this->http()->get($getUrl, $this->expand(['Lines','Customer']));

        if ($get->failed()) {
            throw new RuntimeException(sprintf(
                'Unable to read ETag for order %s/%s (HTTP %d): %s',
                $order->type, $order->order_id, $get->status(), (string) $get->body()
            ));
        }

        $etag = $get->header('ETag');
        if (!$etag) {
            throw new RuntimeException('Visma did not return an ETag header for the order.');
        }

        // 2) PATCH header fields (no lines here)
        $order->refresh()->loadMissing('items');

        // Only send shipping if we have a parsable date; Visma expects ISO like "YYYY-MM-DDTHH:MM:SS"
        $scheduledDate = $this->normalizeIsoDate($order->shipping_scheduled_date);
        $shippingPatch = $scheduledDate ? ['scheduledDate' => $scheduledDate] : null;

        $headerPatch = array_filter([
            'customerRefNo' => $order->customer_ref_no,
            'description'   => $order->description,
            'shipping'      => $shippingPatch, // will be removed if null
        ], fn ($v) => !is_null($v));

        if (!empty($headerPatch)) {
            $patch = $this->httpWrite()
                ->withHeaders(['If-Match' => $etag])
                ->patch($getUrl, $headerPatch);

            if ($patch->status() === 412 || $patch->status() === 409) {
                $fresh = $this->fetchSingleOrder($order->type, $order->order_id);
                $this->persistOrderFromPayload($fresh);
                throw new RuntimeException('Visma version conflict. Latest data loaded; please review and try again.');
            }

            if ($patch->status() === 428) {
                throw new RuntimeException('Visma requires If-Match (ETag) for updates.');
            }

            if ($patch->failed()) {
                throw new RuntimeException(sprintf(
                    'Visma header update failed (HTTP %d): %s',
                    $patch->status(), (string) $patch->body()
                ));
            }

            // ETag may change after a PATCH
            $etag = $patch->header('ETag') ?: $etag;
        }

        // 3) PATCH lines (update existing lines only)
        $linesGet = $this->http()->get($getUrl, $this->expand(['Lines']));
        if ($linesGet->failed()) {
            throw new RuntimeException(sprintf(
                'Unable to load remote lines (HTTP %d): %s',
                $linesGet->status(), (string) $linesGet->body()
            ));
        }

        $remote = (array) $linesGet->json();
        $remoteById = collect($remote['orderLines'] ?? [])->keyBy('lineId');

        $updateLines = $order->items->map(function ($i) use ($remoteById) {
            $r = $remoteById->get($i->line_id) ?: [];

            // Build candidate changes
            $maybe = [
                'unitOfMeasure'   => $i->unit_of_measure,
                'quantity'        => is_null($i->quantity) ? null : (float) $i->quantity,
                'unitPrice'       => is_null($i->unit_price) ? null : (float) $i->unit_price,
                'discountPercent' => is_null($i->discount_percent) ? null : (float) $i->discount_percent,
                'description'     => $i->description,
            ];

            $changed = [];
            foreach ($maybe as $k => $v) {
                if ($v === null) continue;
                $rv = data_get($r, $k);
                $numbers = in_array($k, ['quantity','unitPrice','discountPercent'], true);
                $differs = $numbers ? !$this->sameNumber($v, $rv) : $v !== $rv;
                if ($differs) $changed[$k] = $v;
            }

            // If something changed, send lineId and the changed fields
            return count($changed)
                ? array_merge(['lineId' => $i->line_id], $changed)
                : null;
        })->filter()->values()->all();

        if (!empty($updateLines)) {
            $linesResp = $this->httpWrite()
                ->withHeaders(['If-Match' => $etag])
                ->patch($getUrl . '/lines', ['update' => $updateLines]);

            if (in_array($linesResp->status(), [409, 412], true)) {
                $fresh = $this->fetchSingleOrder($order->type, $order->order_id);
                $this->persistOrderFromPayload($fresh);
                throw new RuntimeException('Visma version conflict while updating lines. Latest data loaded; please retry.');
            }

            if ($linesResp->status() === 428) {
                throw new RuntimeException('Visma requires If-Match (ETag) for line updates.');
            }

            // Tolerate "There are no lines set to patch" as a no-op
            $body = (string) $linesResp->body();
            if ($linesResp->status() === 400 && str_contains($body, 'There are no lines set to patch')) {
                // no-op
            } elseif ($linesResp->failed()) {
                throw new RuntimeException(sprintf(
                    'Visma line update failed (HTTP %d): %s',
                    $linesResp->status(), $body
                ));
            }

            $etag = $linesResp->header('ETag') ?: $etag;
        }

        // 4) Pull back the latest and persist (keep version/status/lines in sync)
        $updated = $this->fetchSingleOrder($order->type, $order->order_id);
        return $this->persistOrderFromPayload($updated);
    }

    // ---- PERSIST: Order + Lines --------------------------------------------
    public function persistOrderFromPayload(array $payload): VismaOrder
    {
        $orderId = $this->stringValue(data_get($payload, 'orderId'));
        if (!$orderId) throw new RuntimeException('Missing orderId in Visma payload.');

        $order = VismaOrder::query()->firstOrNew(['order_id' => $orderId]);

        // Scalars
        $order->fill([
            'order_id'                => $orderId,
            'type'                    => $this->stringValue(data_get($payload, 'type')),
            'status'                  => $this->stringValue(data_get($payload, 'status')),
            'date'                    => $this->stringValue(data_get($payload, 'date')),
            'shipping_scheduled_date' => $this->stringValue(data_get($payload, 'shippingScheduledDate')),
            'request_on'              => $this->stringValue(data_get($payload, 'requestOn')),
            'last_modified'           => $this->stringValue(data_get($payload, 'lastModified')),
            'cancel_by'               => $this->stringValue(data_get($payload, 'cancelBy')),
            'customer_id'             => $this->stringValue(data_get($payload, 'customerId')),
            'customer_name'           => $this->stringValue(data_get($payload, 'customerName')),
            'order_total'             => $this->floatValue(data_get($payload, 'orderTotal', data_get($payload, 'totals.orderTotal'))),
            'tax_total'               => $this->floatValue(data_get($payload, 'taxTotal', data_get($payload, 'totals.taxTotal'))),
            'currency'                => $this->stringValue(data_get($payload, 'currency', data_get($payload, 'currencyId'))),
            'location'                => $this->stringValue(data_get($payload, 'location')),
            'customer_order'          => $this->stringValue(data_get($payload, 'customerOrder')),
            'customer_ref_no'         => $this->stringValue(data_get($payload, 'customerRefNo')),
            'description'             => $this->stringValue(data_get($payload, 'description')),
            'emailed'                 => (bool) data_get($payload, 'emailed', false),
        ]);

        // JSON blobs (model should cast these)
        $order->fill([
            'parent_customer'       => data_get($payload, 'parentCustomer'),
            'branch'                => data_get($payload, 'branch'),
            'project'               => data_get($payload, 'project'),
            'print'                 => data_get($payload, 'print'),
            'billing'               => data_get($payload, 'billing'),
            'payment_settings'      => data_get($payload, 'paymentSettings'),
            'financial_information' => data_get($payload, 'financialInformation'),
            'owner'                 => data_get($payload, 'owner'),
            'origin'                => data_get($payload, 'origin'),
            'shipping'              => data_get($payload, 'shipping'),
            'status_details'        => data_get($payload, 'statusDetails'),
            'customer_block'        => data_get($payload, 'customer'),
            'totals'                => data_get($payload, 'totals'),
            'freight'               => data_get($payload, 'freight'),
            'sales_person'          => data_get($payload, 'salesPerson'),
            'attachments'           => data_get($payload, 'attachments'),
            'custom_fields'         => data_get($payload, 'customFields'),
            'rot_rut'               => data_get($payload, 'rotRut'),
            'commissions'           => data_get($payload, 'commissions'),
            'tax'                   => data_get($payload, 'tax'),
            'shipment'              => data_get($payload, 'shipment'),
            'discounts'             => data_get($payload, 'discounts'),
            'payments'              => data_get($payload, 'payments'),
            'raw_payload'           => $payload,
        ]);

        $order->save();

        // Replace items for a fresh sync
        $order->items()->delete();
        $this->persistItemsFromPayload($order, (array) data_get($payload, 'orderLines', []));

        return $order->load('items');
    }

    private function persistItemsFromPayload(VismaOrder $order, array $lines): void
    {
        foreach ($lines as $line) {
            VismaOrderItem::query()->create([
                'visma_order_id'              => $order->id,

                'line_id'                     => $this->intValue(data_get($line, 'lineId')),
                'sort_order'                  => $this->intValue(data_get($line, 'sortOrder')),
                'line_type'                   => $this->stringValue(data_get($line, 'lineType')),
                'operation'                   => $this->stringValue(data_get($line, 'operation')),

                'inventory_id'                => $this->stringValue(data_get($line, 'inventory.id', data_get($line, 'inventoryId'))),
                'inventory_description'       => $this->stringValue(data_get($line, 'inventory.description')),
                'inventory_base_unit'         => $this->stringValue(data_get($line, 'inventory.baseUnit')),

                'unit_of_measure'             => $this->stringValue(data_get($line, 'unitOfMeasure')),

                'quantity'                    => $this->floatValue(data_get($line, 'quantity')),
                'base_order_quantity'         => $this->floatValue(data_get($line, 'baseOrderQuantity')),
                'unit_cost'                   => $this->floatValue(data_get($line, 'unitCost')),
                'unit_price'                  => $this->floatValue(data_get($line, 'unitPrice')),
                'extended_price'              => $this->floatValue(data_get($line, 'extendedPrice')),
                'line_total_before_discount'  => $this->floatValue(data_get($line, 'lineTotalBeforeDiscount')),
                'discount_amount'             => $this->floatValue(data_get($line, 'discountAmount')),
                'discount_percent'            => $this->floatValue(data_get($line, 'discountPercent')),

                'order_date'                  => $this->stringValue(data_get($line, 'orderDate')),
                'ship_date'                   => $this->stringValue(data_get($line, 'shipDate')),
                'request_date'                => $this->stringValue(data_get($line, 'requestDate')),

                'description'                 => $this->stringValue(data_get($line, 'description')),
                'warehouse_id'                => $this->stringValue(data_get($line, 'warehouseId')),
                'tax_category_id'             => $this->stringValue(data_get($line, 'taxCategoryId')),
                'completed'                   => (bool) data_get($line, 'completed', false),
                'free_item'                   => (bool) data_get($line, 'freeItem', false),
                'open_line'                   => (bool) data_get($line, 'openLine', false),

                // JSON blobs
                'branch'                      => data_get($line, 'branch'),
                'shipping_rule'               => data_get($line, 'shippingRule'),
                'reason_code'                 => data_get($line, 'reasonCode'),
                'warehouse_location'          => data_get($line, 'warehouseLocation'),
                'sales_person'                => data_get($line, 'salesPerson'),
                'supplier'                    => data_get($line, 'supplier'),
                'attachments'                 => data_get($line, 'attachments'),
                'allocations'                 => data_get($line, 'allocations'),
                'custom_fields'               => data_get($line, 'customFields'),
                'raw_payload'                 => $line,
            ]);
        }
    }

    // ---- HTTP clients -------------------------------------------------------
    private function baseClient(bool $write): PendingRequest
    {
        $client = Http::withHeaders([
                'ipp-tenant-id' => config('services.visma.tenant_id'),
                'User-Agent'    => config('app.name', 'YourApp') . '/1.0',
            ])
            ->withToken($this->getAccessToken($write))
            ->baseUrl(rtrim(config('services.visma.sales_order_base_url') ?? self::DEFAULT_BASE_URL, '/') . '/')
            ->acceptJson()
            ->timeout(60)
            ->retry(3, 250); // simple backoff for 5xx/connection issues

        return $this->attachDebuggers($client);
    }

    private function http(): PendingRequest
    {
        return $this->baseClient(false); // read token
    }

    private function httpWrite(): PendingRequest
    {
        return $this->baseClient(true); // write token
    }

    // ---- Token (read/write) -------------------------------------------------
    private function getAccessToken(bool $write = false): string
    {
        $now = time();
        $tokenProp  = $write ? 'writeAccessToken' : 'accessToken';
        $expiryProp = $write ? 'writeTokenExpiresAt' : 'tokenExpiresAt';

        if ($this->{$tokenProp} && $this->{$expiryProp} && $this->{$expiryProp} > $now) {
            return $this->{$tokenProp};
        }

        $clientId     = env('VISMA_CLIENT_ID');
        $clientSecret = env('VISMA_CLIENT_SECRET');
        $tenantId     = config('services.visma.tenant_id');

        $scope = $write
            // write token includes read so we can also call write-only endpoints that check read claims
            ? 'visma.net.erp.salesorder:read visma.net.erp.salesorder:write'
            : 'visma.net.erp.salesorder:read';

        if (!filled($clientId) || !filled($clientSecret) || !filled($tenantId)) {
            throw new RuntimeException('Visma credentials missing (client id/secret/tenant).');
        }

        $payload = [
            'grant_type'    => 'client_credentials',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'tenant_id'     => $tenantId,
            'scope'         => $scope,
        ];

        $response = Http::asForm()->timeout(30)->post(self::TOKEN_ENDPOINT, $payload);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'Unable to retrieve Visma access token (HTTP %d): %s',
                $response->status(),
                (string) $response->body()
            ));
        }

        $data  = $response->json();
        $token = (string) data_get($data, 'access_token');
        $ttl   = (int) data_get($data, 'expires_in', 3600);

        if (!$token) {
            throw new RuntimeException('Visma token response did not contain access_token.');
        }

        $this->{$tokenProp}  = $token;
        $this->{$expiryProp} = $now + max($ttl - 60, 0);

        return $token;
    }

    // ---- tiny helpers -------------------------------------------------------
    private function stringValue($v): ?string
    {
        if (is_string($v)) return trim($v);
        if (is_numeric($v)) return (string) $v;
        return null;
    }

    private function floatValue($v): ?float
    {
        return is_numeric($v) ? (float) $v : null;
    }

    private function intValue($v): ?int
    {
        return is_numeric($v) ? (int) $v : null;
    }

    private function sameNumber($a, $b, float $epsilon = 0.0001): bool
    {
        if ($a === null || $b === null) return $a === $b;
        return abs((float)$a - (float)$b) < $epsilon;
    }

    /**
     * Normalize a date/datetime-ish value to "YYYY-MM-DDTHH:MM:SS" or null.
     * Accepts Carbon, numeric timestamp, or parseable string.
     */
    private function normalizeIsoDate($v): ?string
    {
        if (!$v) return null;

        if ($v instanceof CarbonInterface) {
            return $v->copy()->format('Y-m-d\TH:i:s');
        }

        if (is_numeric($v)) {
            return Carbon::createFromTimestamp((int) $v)->format('Y-m-d\TH:i:s');
        }

        try {
            return Carbon::parse((string) $v)->format('Y-m-d\TH:i:s');
        } catch (\Throwable $e) {
            return null; // if unparsable, skip sending shipping
        }
    }

    // --- Create (POST) a Sales Order in Visma ------------------------------
    /**
     * Create an order in Visma from a local VismaOrder model.
     * - Returns the freshly persisted local model (synced with remote).
     */
    public function createOrderInVisma(VismaOrder $order): VismaOrder
    {
        $order->loadMissing('items');

        // ---- Basic local guards so we don't send a broken payload ----
        if (!filled($order->customer_id)) {
            throw new RuntimeException('Select a customer before creating the order in Visma.');
        }

        $lines = $order->items
            ->filter(fn ($i) => filled($i->inventory_id) && (float) $i->quantity > 0)
            ->values();

        if ($lines->isEmpty()) {
            throw new RuntimeException('Add at least one item with Inventory ID and Quantity > 0.');
        }

        // Build payload and POST
        $payload = $this->buildCreatePayloadFromModel($order);
        $resp = $this->httpWrite()->post('SalesOrders', $payload);

        if ($resp->failed()) {
            throw new RuntimeException(sprintf(
                'Visma create order failed (HTTP %d): %s',
                $resp->status(), (string) $resp->body()
            ));
        }

        // The API returns the created order; persist locally to sync ETag/version/lines, etc.
        $created = $resp->json();
        return $this->persistOrderFromPayload(is_array($created) ? $created : []);
    }

    /**
     * Minimal valid payload for POST /SalesOrders.
     * Visma requires:
     *  - type (optional if default exists in tenant, but include it)
     *  - customer { id: "<number>" }
     *  - orderLines with inventoryId (or inventory:{id}) and quantity
     */
    private function buildCreatePayloadFromModel(VismaOrder $order): array
    {
        $termsId     = config('services.visma.default_terms_id', 'NET30');
        $locationId  = $order->location ?: config('services.visma.default_location_id', 'Main');
        $scheduled   = $this->normalizeIsoDate($order->shipping_scheduled_date);

        $lines = [];
        foreach ($order->items as $i) {
            if (!$i->inventory_id || (float)$i->quantity <= 0) continue;

            $line = [
                'inventoryId'     => (string) $i->inventory_id,
                'quantity'        => (float) $i->quantity,
                'unitOfMeasure'   => $i->unit_of_measure ?: null,
                'unitPrice'       => isset($i->unit_price) ? (float) $i->unit_price : null,
                'discountPercent' => isset($i->discount_percent) ? (float) $i->discount_percent : null,
                'description'     => $i->description ?: null,
                'warehouseId'     => $i->warehouse_id ?: null,
                'taxCategoryId'   => $i->tax_category_id ?: null,
            ];
            $lines[] = array_filter($line, fn ($v) => !is_null($v));
        }

        if (empty($lines)) {
            throw new RuntimeException("At least one line with 'inventoryId' and 'quantity' is required.");
        }

        return array_filter([
            'type'            => $order->type ?: 'BB',
            'description'     => $order->description,
            'currencyId'      => $order->currency ?: 'NOK', // <- use currencyId consistently
            'customer'        => [
                'id'         => (string) $order->customer_id,   // <- must exist in Visma
                'locationId' => $locationId,                    // <- e.g. "Main"
            ],
            'paymentSettings' => [
                'termsId' => $termsId,                          // <- must exist in Visma
            ],
            'shipping'        => $scheduled ? ['scheduledDate' => $scheduled] : null,
            'customerRefNo'   => $order->customer_ref_no,
            'orderLines'      => $lines,
        ], fn ($v) => !is_null($v));
    }

    // ---- misc helpers -------------------------------------------------------
    private function expand(array $parts): array
    {
        // Many APIs expect a comma-separated string rather than an array for $expand
        return ['expand' => implode(',', $parts)];
    }
}
