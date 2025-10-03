<?php

namespace App\Console\Commands;

use App\Models\Products;
use App\Services\MagentoIntegration;
use Illuminate\Console\Command;
use Carbon\Carbon; // Make sure you import Carbon for working with dates

class SendProductToMagento extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magento:send-products'; // Removed productId argument, send all products

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send all product data to Magento';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Fetch all products from the database
        $products = Products::all(); // You can add a filter here if needed

        if ($products->isEmpty()) {
            $this->error('No products found in the database.');
            return;
        }

        // Initialize the MagentoIntegration service
        $magento = new MagentoIntegration();

        // Loop through each product and send it to Magento
        foreach ($products as $product) {
            // Prepare product data
            $productData = [
                'sku' => $product->sku,
               'name' => $product->name,
               //'price' => '1',
                'price' => $product->price,
                'description' => $product->description,
                'category_ids' => explode(',', $product->category_ids), // Assuming category_ids are stored as comma-separated
                'image' => $product->image, // If you are sending a URL or path to the image
            ];

            // Send the product data to Magento
            $response = $magento->createProduct($productData);

            // Update the sync status and last synced timestamp for each product
            $product->last_synced = Carbon::now(); // Set the current timestamp
            if (isset($response['error'])) {
                // If there is an error, set sync status to 'failed'
                $product->sync_status = 'failed';
                $this->error("Failed to send product '{$product->name}' to Magento: " . $response['error']);
            } else {
                // If the product is successfully sent, set sync status to 'success'
                $product->sync_status = 'success';
                $this->info("Product '{$product->name}' successfully sent to Magento.");
            }

            // Save the updated product information (sync status and last synced)
            $product->save();
        }

        $this->info('All products processed.');
    }
}
