<?php

// app/Jobs/SyncVismaInventoryItem.php
namespace App\Jobs;

use App\Models\Products;
use App\Models\VismaSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncVismaInventoryItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $inventoryId,
        public ?string $eventType = null
    ) {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        // Deduplicate bursts
        $lock = Cache::lock("visma-inv-sync:{$this->inventoryId}", 10);
        if (! $lock->get()) return;

        try {
            $s = VismaSettings::active();

            // --- token (Finance scope) ---
            $tokenResp = Http::asForm()->timeout(20)->post('https://connect.visma.com/connect/token', [
                'grant_type'    => 'client_credentials',
                'client_id'     => $s->client_id,
                'client_secret' => $s->client_secret,
                'tenant_id'     => $s->tenantId(),
                'scope'         => $s->financeScope(), // e.g. vismanet_erp_service_api:read
            ]);
            if ($tokenResp->failed()) {
                Log::error('Visma token failed', ['status' => $tokenResp->status(), 'body' => (string) $tokenResp->body()]);
                return;
            }
            $token = (string) data_get($tokenResp->json(), 'access_token');

            // --- GET the latest item ---
            $base = rtrim($s->financeBaseUrl(), '/') . '/';
            $resp = Http::withToken($token)->acceptJson()->timeout(30)->get($base . 'inventory/' . rawurlencode($this->inventoryId));

            if ($resp->failed()) {
                Log::warning('Visma inventory fetch failed', [
                    'id' => $this->inventoryId,
                    'status' => $resp->status(),
                    'body' => (string) $resp->body(),
                ]);
                return;
            }

            $data = $resp->json();
            if (! is_array($data)) return;

            // --- map -> Products attributes (reuse your importer map) ---
            $payload = app(\App\Support\Visma\InventoryMapper::class)->map($data);

            if (blank($payload['sku'] ?? null)) {
                Log::notice('Visma inventory missing SKU/inventoryNumber', ['id' => $this->inventoryId]);
                return;
            }

            Products::updateOrCreate(['sku' => $payload['sku']], $payload);
        } finally {
            optional($lock)->release();
        }
    }
}
