<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Products;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class VismaOrderService
{
    private const BASE_URL = 'https://salesorder.visma.net/api/v3';

    private ?string $accessToken = null;

    public function syncOrderFromVisma(string $orderNumber): Order
    {
        $orderNumber = trim($orderNumber);

        if ($orderNumber === '') {
            throw new RuntimeException('A Visma order number is required.');
        }

        $payload = $this->fetchOrderPayload($orderNumber);

        if (!is_array($payload) || $payload === []) {
            throw new RuntimeException(sprintf('Visma order "%s" could not be retrieved.', $orderNumber));
        }

        return DB::transaction(function () use ($payload) {
            $order = $this->storeVismaOrderPayload($payload);

            return $order->fresh(['items.product', 'customer']);
        });
    }

    public function pushOrderToVisma(Order $order): Order
    {
        $order->loadMissing('items.product', 'customer');

        if ($order->items->isEmpty()) {
            throw new RuntimeException('Orders must contain at least one line before they can be sent to Visma.');
        }

        $orderType = $order->visma_sales_order_type ?? $this->getSalesOrderType();

        if (filled($orderType) && $order->visma_sales_order_type !== $orderType) {
            $order->visma_sales_order_type = $orderType;
            $order->save();
        }

        if (!filled($orderType)) {
            throw new RuntimeException('A Visma sales order type is required before pushing the order.');
        }

        $payload = $this->buildSalesOrderPayload($order);
        $client = $this->http();

        try {
            $response = $order->visma_sales_order_number
                ? $client->put(
                    sprintf(
                        '/SalesOrders/%s/%s',
                        urlencode($orderType),
                        urlencode($order->visma_sales_order_number)
                    ),
                    $payload
                )
                : $client->post('/SalesOrders', $payload);
        } catch (Throwable $exception) {
            Log::error('Unexpected error while communicating with Visma.', [
                'order_id' => $order->id,
                'exception' => $exception,
            ]);

            throw new RuntimeException('Unable to communicate with Visma: ' . $exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            Log::warning('Visma rejected order payload.', [
                'order_id' => $order->id,
                'payload' => $payload,
                'response_status' => $response->status(),
                'response_body' => $response->body(),
            ]);

            throw new RuntimeException(sprintf(
                'Visma rejected the sales order (HTTP %d): %s',
                $response->status(),
                (string) $response->body()
            ));
        }

        $responseBody = $response->json();
        $vismaOrderNumber = data_get($responseBody, 'orderId')
            ?? data_get($responseBody, 'orderNumber')
            ?? data_get($responseBody, 'orderNo')
            ?? $order->visma_sales_order_number;

        if (!filled($vismaOrderNumber)) {
            throw new RuntimeException('Visma did not return a sales order number.');
        }

        return $this->syncOrderFromVisma($vismaOrderNumber);
    }

    protected function storeVismaOrderPayload(array $payload): Order
    {
        $orderNumber = data_get($payload, 'orderId')
            ?? data_get($payload, 'orderNumber')
            ?? data_get($payload, 'orderNo');
        $status = data_get($payload, 'status.description') ?? data_get($payload, 'status');
        $totals = data_get($payload, 'totals', []);
        $details = data_get($payload, 'orderLines', data_get($payload, 'details', []));
        $billingAddress = data_get($payload, 'billing.address', data_get($payload, 'billingAddress', []));
        $shippingAddress = data_get($payload, 'shipping.address', data_get($payload, 'shippingAddress', []));
        $customerNumber = data_get($payload, 'customer.id')
            ?? data_get($payload, 'customer.customerNo')
            ?? data_get($payload, 'customerNo');
        $customerName = data_get($payload, 'customer.name') ?? data_get($payload, 'customerName');

        if (!filled($customerNumber)) {
            throw new RuntimeException('The Visma order payload does not contain a customer number.');
        }

        /** @var Customer $customer */
        $customer = Customer::firstOrCreate(
            ['number' => $customerNumber],
            ['name' => $customerName ?: $customerNumber]
        );

        $orderAttributes = collect([
            'customer_id' => $customer->id,
            'customer_price_class_id' => $customer->customer_price_class_id,
            'invoice_address' => data_get($billingAddress, 'line1') ?? data_get($billingAddress, 'addressLine1'),
            'invoice_city' => data_get($billingAddress, 'city'),
            'invoice_postal_code' => data_get($billingAddress, 'postalCode'),
            'delivery_address' => data_get($shippingAddress, 'line1') ?? data_get($shippingAddress, 'addressLine1'),
            'delivery_city' => data_get($shippingAddress, 'city'),
            'delivery_postal_code' => data_get($shippingAddress, 'postalCode'),
            'status' => $status ?: 'pending',
            'total_amount' => data_get($totals, 'orderTotal') ?? data_get($payload, 'orderTotal'),
            'visma_sales_order_number' => $orderNumber,
            'visma_sales_order_type' => data_get($payload, 'type') ?? data_get($payload, 'orderType'),
            'visma_status' => $status,
            'visma_last_synced_at' => Carbon::now(),
            'visma_payload' => $payload,
        ])->filter(fn ($value) => !is_null($value))->all();

        /** @var Order $order */
        $order = Order::updateOrCreate(
            ['visma_sales_order_number' => $orderNumber],
            $orderAttributes
        );

        $this->syncOrderItems($order, is_array($details) ? $details : []);

        $order->total_amount = $order->items->sum(function (OrderItem $item) {
            return $item->quantity * $item->discounted_price;
        });
        $order->save();

        return $order;
    }

    protected function syncOrderItems(Order $order, array $details): void
    {
        $existingItemIds = [];

        foreach ($details as $detail) {
            $inventoryId = data_get($detail, 'inventoryId') ?? data_get($detail, 'inventory.id');

            if (!filled($inventoryId)) {
                continue;
            }

            $product = Products::query()
                ->where('inventory_id', $inventoryId)
                ->orWhere('sku', $inventoryId)
                ->first();

            if (!$product) {
                throw new RuntimeException(sprintf(
                    'Product "%s" referenced by Visma order could not be found locally.',
                    $inventoryId
                ));
            }

            $quantity = (float) data_get($detail, 'quantity', 1);
            $unitPrice = (float) data_get($detail, 'unitPrice', 0);
            $discountPercent = (float) (data_get($detail, 'discountPercent') ?? data_get($detail, 'discPct', 0));
            $discountAmount = (float) data_get($detail, 'discountAmount', 0);
            $lineTotal = (float) data_get($detail, 'extendedPrice', data_get($detail, 'lineTotal', 0));
            $lineNumber = data_get($detail, 'lineId') ?? data_get($detail, 'lineNbr');

            $discountedUnitPrice = $unitPrice;

            if ($discountPercent !== 0.0) {
                $discountedUnitPrice = $unitPrice * (1 - $discountPercent / 100);
            } elseif ($discountAmount > 0 && $quantity > 0) {
                $discountedUnitPrice = ($unitPrice * $quantity - $discountAmount) / max($quantity, 1);
            } elseif ($lineTotal > 0 && $quantity > 0) {
                $discountedUnitPrice = $lineTotal / max($quantity, 1);
            }

            $calculatedDiscountAmount = max(0, ($unitPrice - $discountedUnitPrice) * $quantity);
            if ($discountAmount <= 0 && $calculatedDiscountAmount > 0) {
                $discountAmount = $calculatedDiscountAmount;
            }

            $orderItem = $order->items()
                ->when($lineNumber !== null, function ($query) use ($lineNumber) {
                    $query->where('visma_line_number', $lineNumber);
                })
                ->where('product_id', $product->id)
                ->first();

            if (!$orderItem) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $product->id;
            }

            $orderItem->fill([
                'quantity' => $quantity,
                'price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'discounted_price' => $discountedUnitPrice,
                'visma_line_number' => $lineNumber,
            ]);
            $orderItem->save();

            $existingItemIds[] = $orderItem->id;
        }

        $order->items()->whereNotIn('id', $existingItemIds)->delete();
        $order->load('items');
    }

    protected function buildSalesOrderPayload(Order $order): array
    {
        $details = [];
        $orderType = $order->visma_sales_order_type ?? $this->getSalesOrderType();

        foreach ($order->items as $index => $item) {
            $product = $item->product;

            if (!$product) {
                throw new RuntimeException('All order lines must have an associated product before sending to Visma.');
            }

            $inventoryId = $product->inventory_id ?: $product->sku;

            if (!filled($inventoryId)) {
                throw new RuntimeException(sprintf('Product "%s" does not have an inventory ID or SKU.', $product->name));
            }

            $line = [
                'lineId' => $item->visma_line_number ?? ($index + 1),
                'inventory' => [
                    'id' => $inventoryId,
                ],
                'quantity' => (float) $item->quantity,
                'unitPrice' => (float) $item->price,
            ];

            if ($item->discount_percent) {
                $line['discountPercent'] = (float) $item->discount_percent;
            }

            if ($item->discount_amount) {
                $line['discountAmount'] = (float) $item->discount_amount;
            }

            $details[] = $line;
        }

        $payload = [
            'orderId' => $order->visma_sales_order_number,
            'type' => $orderType,
            'date' => optional($order->created_at)->format('Y-m-d') ?? Carbon::now()->format('Y-m-d'),
            'customer' => [
                'id' => $order->customer->number,
            ],
            'orderLines' => $details,
            'billing' => [],
            'shipping' => [],
            'currencyId' => env('VISMA_DEFAULT_CURRENCY', 'NOK'),
        ];

        $billingAddress = array_filter([
            'line1' => $order->invoice_address,
            'postalCode' => $order->invoice_postal_code,
            'city' => $order->invoice_city,
        ], fn ($value) => filled($value));

        if ($billingAddress !== []) {
            $payload['billing']['address'] = $billingAddress;
        }

        $shippingAddress = array_filter([
            'line1' => $order->delivery_address,
            'postalCode' => $order->delivery_postal_code,
            'city' => $order->delivery_city,
        ], fn ($value) => filled($value));

        if ($shippingAddress !== []) {
            $payload['shipping']['address'] = $shippingAddress;
        }

        if ($payload['billing'] === []) {
            unset($payload['billing']);
        }

        if ($payload['shipping'] === []) {
            unset($payload['shipping']);
        }

        $version = data_get($order->visma_payload, 'version');
        if (filled($version)) {
            $payload['version'] = $version;
        }

        return Arr::where($payload, fn ($value) => !is_null($value));
    }

    protected function fetchOrderPayload(string $orderNumber): array
    {
        $orderType = $this->resolveSalesOrderType($orderNumber);
        $attemptedOrderType = $orderType;
        $response = $this->requestSalesOrder($orderNumber, $orderType);

        if ($response->status() === 404 && filled($orderType)) {
            $response = $this->requestSalesOrder($orderNumber, null);

            if ($response->successful()) {
                $attemptedOrderType = null;
            }
        }

        if ($response->failed()) {
            $details = sprintf('HTTP %d: %s', $response->status(), (string) $response->body());

            if (filled($attemptedOrderType)) {
                $details .= sprintf(' (type=%s)', $attemptedOrderType);
            }

            throw new RuntimeException(sprintf(
                'Failed to retrieve Visma order "%s": %s',
                $orderNumber,
                $details
            ));
        }

        $payload = $response->json();

        if (is_array($payload) && array_key_exists('value', $payload)) {
            $first = $payload['value'][0] ?? [];

            if ($first !== []) {
                $payload = $first;
            }
        }

        if (!is_array($payload)) {
            throw new RuntimeException('Visma returned an unexpected response when fetching the order.');
        }

        return $payload;
    }

    protected function http(): PendingRequest
    {
<<<<<<< ours
        return Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->baseUrl(self::BASE_URL);
=======
        $client = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->baseUrl(self::BASE_URL);

        $headers = array_filter([
            'ipp-company-id' => env('VISMA_COMPANY_ID'),
            'ipp-application-type' => env('VISMA_APPLICATION_TYPE'),
            'ipp-user-id' => env('VISMA_USER_ID'),
            'Ocp-Apim-Subscription-Key' => env('VISMA_SUBSCRIPTION_KEY'),
        ], fn ($value) => filled($value));

        if ($headers !== []) {
            $client = $client->withHeaders($headers);
        }

        return $client;
>>>>>>> theirs
    }

    protected function resolveSalesOrderType(string $orderNumber): ?string
    {
        $storedType = Order::query()
            ->where('visma_sales_order_number', $orderNumber)
            ->value('visma_sales_order_type');

        if (filled($storedType)) {
            return $storedType;
        }

        return $this->getSalesOrderType();
    }

    protected function requestSalesOrder(string $orderNumber, ?string $orderType): Response
    {
        $expands = $this->getExpandParameters();

        if (filled($orderType)) {
            return $this->http()->get(
                $this->appendQueryString(
                    sprintf('/SalesOrders/%s/%s', urlencode($orderType), urlencode($orderNumber)),
                    ['expand' => $expands]
                )
            );
        }

        $response = $this->http()->get(
            $this->appendQueryString(
                sprintf('/SalesOrders/%s', urlencode($orderNumber)),
                ['expand' => $expands]
            )
        );
<<<<<<< ours
        $query = [
            'expand' => implode(',', [
                'Billing',
                'Payment',
                'FinancialInformation',
                'Shipping',
                'Customer',
                'Origin',
                'Note',
                'Discounts',
                'Payments',
                'Lines',
                'LinesExcludingAllocations',
            ]),
        ];

        if (filled($orderType)) {
            return $this->http()->get(
                sprintf('/SalesOrders/%s/%s', urlencode($orderType), urlencode($orderNumber)),
                $query
            );
        }

        $response = $this->http()->get(sprintf('/SalesOrders/%s', urlencode($orderNumber)), $query);
=======
>>>>>>> theirs

        if ($response->status() !== 404) {
            return $response;
        }

        return $this->http()->get(
            $this->appendQueryString(
                '/SalesOrders',
                [
                    'expand' => $expands,
                    'orderId' => $orderNumber,
                    'pageSize' => 1,
                ]
            )
        );
    }

    protected function appendQueryString(string $path, array $parameters = []): string
    {
        $query = $this->buildQueryString($parameters);

        if ($query === '') {
            return $path;
        }

        return $path . (str_contains($path, '?') ? '&' : '?') . $query;
    }

    protected function buildQueryString(array $parameters): string
    {
        $parts = [];

        foreach ($parameters as $key => $value) {
            if (is_null($value)) {
                continue;
            }

            if ($key === 'expand') {
                foreach ($this->normaliseExpandValues($value) as $expand) {
                    $parts[] = 'expand=' . rawurlencode($expand);
                }

                continue;
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    if (is_null($item)) {
                        continue;
                    }

                    $parts[] = sprintf('%s=%s', rawurlencode($key), rawurlencode((string) $item));
                }

                continue;
            }

            $parts[] = sprintf('%s=%s', rawurlencode($key), rawurlencode((string) $value));
        }

        return implode('&', $parts);
    }

    /**
     * @param  array|string  $value
     * @return array<int, string>
     */
    protected function normaliseExpandValues(array|string $value): array
    {
        $values = is_array($value) ? $value : explode(',', (string) $value);

        return array_values(array_filter(array_map('trim', $values), fn ($expand) => $expand !== ''));
    }

    protected function getExpandParameters(): array
    {
        return [
            'Billing',
            'Payment',
            'FinancialInformation',
            'Shipping',
            'Customer',
            'Origin',
            'Note',
            'Discounts',
            'Payments',
            'Lines',
            'LinesExcludingAllocations',
        ];
    }

<<<<<<< ours
            '/SalesOrders',
            array_merge($query, [
                'orderId' => $orderNumber,
                'pageSize' => 1,
            ])
        );
    }

=======
>>>>>>> theirs
    protected function getSalesOrderType(): ?string
    {
        $orderType = env('VISMA_SALES_ORDER_TYPE', 'SO');

        if (!is_string($orderType)) {
            return null;
        }

        $orderType = trim($orderType);

        return $orderType !== '' ? $orderType : null;
    }

    protected function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $clientId = env('VISMA_CLIENT_ID');
        $clientSecret = env('VISMA_CLIENT_SECRET');
        $tenantId = env('VISMA_TENANT_ID', env('VISMA_TENANT_ID_LIVE'));

        if (!filled($clientId) || !filled($clientSecret) || !filled($tenantId)) {
            throw new RuntimeException('Visma credentials are not configured.');
        }

        $scope = env('VISMA_SCOPE', 'vismanet_erp_service_api:create vismanet_erp_service_api:update');
        if (is_string($scope)) {
            $scope = trim($scope);
        }

        $tokenPayload = [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'tenant_id' => $tenantId,
        ];

        if (filled($scope)) {
            $tokenPayload['scope'] = $scope;
        }

        $response = Http::asForm()->post('https://connect.visma.com/connect/token', $tokenPayload);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'Unable to retrieve Visma access token (HTTP %d): %s',
                $response->status(),
                (string) $response->body()
            ));
        }

        $token = $response->json('access_token');

        if (!filled($token)) {
            throw new RuntimeException('Visma did not return an access token.');
        }

        return $this->accessToken = $token;
    }
}
