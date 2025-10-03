<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Bundle;
use App\Services\MagentoIntegration;
use Illuminate\Support\Facades\Log;

class ExportBundlesToMagento extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:bundles-to-magento {bundleId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export bundles from Laravel to Magento';

    protected $magentoIntegration;

    /**
     * Create a new command instance.
     */
    public function __construct(MagentoIntegration $magentoIntegration)
    {
        parent::__construct();
        $this->magentoIntegration = $magentoIntegration; // Correct naming for property
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Optionally take a specific bundle ID to export
        $bundleId = $this->argument('bundleId');

        if ($bundleId) {
            // Export a single bundle
            $bundles = Bundle::with('items.product')->where('id', $bundleId)->get();
        } else {
            // Export all bundles
            $bundles = Bundle::with('items.product')->get();
        }

        foreach ($bundles as $bundle) {
            // Ensure SKU is present before attempting to export
            if (empty($bundle->sku)) {
                $this->error("Bundle {$bundle->name} does not have an SKU. Skipping...");
                Log::warning("Skipped bundle {$bundle->id} due to missing SKU.");
                continue;
            }

            $bundleData = $this->prepareBundleData($bundle);
            $response = $this->magentoIntegration->createBundleProduct($bundleData);

            if (!isset($response['error'])) {
                $this->info("Bundle {$bundle->name} exported successfully to Magento.");
                Log::info("Bundle {$bundle->id} exported successfully to Magento.");
            } else {
                $this->error("Failed to export bundle {$bundle->name} to Magento: " . ($response['error']));
                Log::error("Failed to export bundle {$bundle->id} to Magento: " . ($response['error']));
            }
        }
    }

    /**
     * Prepare the bundle data for Magento.
     */
    protected function prepareBundleData($bundle)
    {
        return [
            'sku' => $bundle->sku,
            'name' => $bundle->name,
            'price' => $bundle->calculatePrice(),
            'items' => $bundle->items->map(function ($item) {
                return [
                    'product' => [
                        'sku' => $item->product->sku,
                        'name' => $item->product->name,
                        'price' => $item->product->price,
                    ],
                    'quantity' => $item->quantity,
                ];
            })->toArray(),
        ];
    }
}
