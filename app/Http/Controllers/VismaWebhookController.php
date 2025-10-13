<?php

// app/Http/Controllers/VismaWebhookController.php
namespace App\Http\Controllers;

use App\Jobs\SyncVismaInventoryItem;
use App\Models\VismaSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class VismaWebhookController extends Controller
{
    public function inventory(Request $request, string $token): Response
    {
        $s = VismaSettings::active();

        // 1) URL token check (what you already have)
        if (! $s || ! hash_equals((string) $s->financeWebhookSecret(), (string) $token)) {
            return response()->noContent(401);
        }

        // 2) OPTIONAL: subscription id gate (if the portal sends it)
        $incomingSubId = $request->header('X-Subscription-Id') ?? $request->header('X-Visma-Subscription-Id');
        $savedSubId    = $s->financeWebhookSubscriptionId();
        if ($savedSubId && $incomingSubId && ! hash_equals($savedSubId, $incomingSubId)) {
            Log::warning('Visma webhook rejected due to mismatched subscription id', compact('incomingSubId', 'savedSubId'));
            return response()->noContent(400);
        }

        // 3) OPTIONAL: HMAC signature check if the portal includes a signature header
        $sharedSecret = $s->financeWebhookSharedSecret();
        if ($sharedSecret) {
            $raw = $request->getContent();
            $calc = base64_encode(hash_hmac('sha256', $raw, $sharedSecret, true));

            // Try a few header names & formats (some providers prefix with "sha256=")
            $provided = $request->header('X-Visma-Signature')
                ?? $request->header('X-Webhook-Signature')
                ?? $request->header('X-Hub-Signature-256')
                ?? '';

            $provided = is_string($provided) ? trim($provided) : '';
            $provided = str_starts_with($provided, 'sha256=') ? substr($provided, 7) : $provided;

            if ($provided !== '' && ! hash_equals($calc, $provided)) {
                Log::warning('Visma webhook signature mismatch');
                return response()->noContent(401);
            }
        }

        // 4) Process
        $payload = $request->json()->all();
        Log::info('Visma inventory webhook', ['payload' => $payload]);

        $events = is_array($payload) && array_is_list($payload) ? $payload : [$payload];

        foreach ($events as $event) {
            $id = data_get($event, 'entityId')
                ?? data_get($event, 'inventoryId')
                ?? data_get($event, 'id')
                ?? data_get($event, 'key.inventoryId');

            if ($id) {
                SyncVismaInventoryItem::dispatch((string) $id, (string) data_get($event, 'eventType'));
            }
        }

        return response()->noContent(202);
    }
}
