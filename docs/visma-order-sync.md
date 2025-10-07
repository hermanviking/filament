# Visma Order Sync Guide

This document explains how to configure, run, and troubleshoot the Visma order integration that was added to the Filament admin panel. The integration talks to the [Visma Sales Order API](https://salesorder.visma.net/swagger/index.html).

## Prerequisites

Before you can import or push orders to Visma you need:

- API credentials for Visma.net ERP (client id, client secret, tenant id, and the OAuth scopes granted to your integration).
- A `discounts.json` file exported from Visma that contains the discount matrix for customer and item price classes.
- Products and customers already synchronised locally so that the Visma inventory and customer numbers exist in your database.

## Environment variables

Add the following keys to your `.env` file (or set them in your hosting control panel):

```dotenv
VISMA_CLIENT_ID="your-app-client-id"
VISMA_CLIENT_SECRET="your-app-client-secret"
VISMA_TENANT_ID="your-tenant-id"
VISMA_TENANT_ID_LIVE="your-live-tenant-id" # optional fallback if you use a separate live tenant id
VISMA_SCOPE="vismanet_erp_service_api:create vismanet_erp_service_api:update"
VISMA_DEFAULT_CURRENCY="NOK"
VISMA_SALES_ORDER_TYPE="SO" # Visma document type for sales orders
```

The default scope string grants permission to create and update sales orders. If your Visma Connect application exposes
additional scopes (for example `vismanet_erp_service_api:read` or `vismanet_erp_service_api:write`), list them space separated
in `VISMA_SCOPE`. Leaving the variable empty removes the `scope` parameter from the token request entirely, which is useful for
legacy tenants that expect the default access.

`VISMA_SALES_ORDER_TYPE` controls the Visma document type segment that is sent with order create/update requests and used when
retrieving existing orders. The Sales Order API expects requests in the form `/SalesOrders/{type}/{orderId}`; configure the
type (for example `SO`, `BB`, etc.) so the integration can resolve the correct endpoint and avoid 404 "Document could not be
found" errors during syncs.

After editing the environment file remember to reload PHP-FPM (or the queue worker) so the new variables are picked up.

## Preparing discount data

1. Export the Visma discount codes JSON.
2. Save the export as `storage/discounts.json` in the Laravel project so the Filament form can calculate automatic discounts.
3. Ensure the file is readable by the web server user.

If the file is missing the discount percent fields in the Filament order form will stay at `0` and no automatic discount will be applied.

## Importing an order from Visma

1. Navigate to **Orders** in the Filament admin.
2. Click **Import from Visma** in the header.
3. Enter the Visma sales order number and confirm.
4. The order, its customer, and its line items will be imported and totals recalculated.

Any errors returned by Visma are surfaced as Filament notifications and logged to `storage/logs/laravel.log`.

## Sending an order to Visma

You can push an order to Visma in three places:

- Immediately after creating the order (the create page triggers the sync once the record is stored).
- From the edit page by clicking **Send to Visma**.
- From the list table using the row action with the paper plane icon.

If the order already exists in Visma, the integration updates it with a `PUT` call; otherwise it creates a new sales order via `POST`.

## Refreshing from Visma

Use the **Refresh from Visma** action in the list or edit pages to pull the latest data for an order. The integration updates the local line items, recalculates totals, and stores the raw payload in `orders.visma_payload` for auditing.

## Troubleshooting

- **Missing product** – ensure the Visma `inventoryId` or SKU exists on the local product. Re-run your Visma product import if required.
- **Authentication errors** – double check your client id/secret/tenant id combination, ensure the Visma app has the required scopes,
  and adjust `VISMA_SCOPE` if Visma reports `invalid_scope` during the token request.
- **Order not found (404)** – confirm the Visma sales order number exists and that `VISMA_SALES_ORDER_TYPE` (or the
  per-order `visma_sales_order_type` value) matches the document type the order was created with. The integration will
  retry without an explicit type if the first lookup fails, but Visma still requires the correct type segment for most
  tenants.
- **Discounts not applying** – confirm the `customer_price_class_id` is populated on the customer and `item_price_class_id` is set on each product.
- **Timeouts** – Visma occasionally throttles calls. The integration already logs payloads/responses; review the logs and retry the action from Filament.

## Relevant artisan commands

The project already ships with commands to pull master data from Visma:

- `php artisan visma:import-customers`
- `php artisan visma:import-products`

Run these on a schedule (or manually) so the order sync can resolve product and customer references.

