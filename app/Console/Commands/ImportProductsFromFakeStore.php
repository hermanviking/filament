<?php

namespace App\Console\Commands;

use App\Models\Products;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportProductsFromFakeStore extends Command
{
    protected $signature = 'products:import-fakestore';

    protected $description = 'Import products from FakeStore API';

    public function handle()
    {
        // Define the FakeStore API URL
        $url = 'https://fakestoreapi.com/products';

        // Fetch data from FakeStore API
        $response = Http::get($url);

        // Check if the request was successful
        if ($response->successful()) {
            $products = $response->json();

            // Loop through each product and save or update in the database
            foreach ($products as $productData) {
                Products::updateOrCreate(
                    ['sku' => $productData['id']], // Assuming the 'id' can be used as SKU
                    [
                        'name' => $productData['title'],
                        'price' => $productData['price'],
                        'image'=> $productData['image']                        // Add any other fields as necessary
                    ]
                );
            }

            $this->info('Products imported successfully from FakeStore API.');
        } else {
            $this->error('Failed to fetch data from FakeStore API.');
        }
    }
}
