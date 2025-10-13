<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class VismaSettings extends Model
{
    protected $table = 'visma_settings';

    protected $fillable = [
        'environment',
        'dev_finance_base_url',
        'live_finance_base_url',
        'dev_sales_base_url',
        'live_sales_base_url',
        'dev_tenant_id',
        'live_tenant_id',
        'client_id',
        'client_secret',
        'scope_read',
        'scope_write',
        'default_terms_id',
        'default_location_id',
        'default_currency',
        'default_order_type',
        'http_debug',
        'use_finance_v1',
        'finance_scope',
        'salesorder_scope',
    ];

    protected $casts = [
        'http_debug'   => 'bool',
        'use_finance_v1' => 'bool',
        // Laravel encrypted cast keeps secret at rest
        'client_secret' => 'encrypted',
    ];

    /** Return the single row, cached. */
    public static function active(): self
    {
        return Cache::remember('visma.settings', 300, function () {
            return static::query()->firstOrFail();
        });
    }

    /** Clear cache after save. */
    protected static function booted(): void
    {
        static::saved(fn() => Cache::forget('visma.settings'));
    }

    // ----- Convenience getters -----
    public function financeBaseUrl(): string
    {
        $url = $this->environment === 'live'
            ? ($this->live_finance_base_url ?: 'https://api.finance.visma.net/v1')
            : ($this->dev_finance_base_url ?: 'https://api.finance.visma.net/v1');

        return rtrim($url, '/') . '/';
    }

    public function salesBaseUrl(): string
    {
        $url = $this->environment === 'live'
            ? ($this->live_sales_base_url ?: 'https://salesorder.visma.net/api/v3')
            : ($this->dev_sales_base_url ?: 'https://salesorder.visma.net/api/v3');

        return rtrim($url, '/') . '/';
    }

    public function tenantId(): ?string
    {
        return $this->environment === 'live' ? $this->live_tenant_id : $this->dev_tenant_id;
    }


    public function writeScope(): string
    {
        return $this->scope_write ?: 'vismanet_erp_service_api:read visma.net.erp.salesorder:write';
    }
    public function financeScope(): string
    {
        // Finance Service API v1
        return $this->finance_scope ?: 'vismanet_erp_service_api:read';
    }

    public function salesOrderScope(): string
    {
        // SalesOrder v3 — default to read+write so one token works for both
        return $this->salesorder_scope ?: 'visma.net.erp.salesorder:read visma.net.erp.salesorder:write';
    }
    // app/Models/VismaSettings.php
    public function financeWebhookSecret(): ?string
    {
        return $this->finance_webhook_secret;
    }
    // app/Models/VismaSettings.php
    public function financeWebhookSubscriptionId(): ?string
    {
        return $this->finance_webhook_subscription_id;
    }
    public function financeWebhookSharedSecret(): ?string
    {
        return $this->finance_webhook_shared_secret;
    }
}
