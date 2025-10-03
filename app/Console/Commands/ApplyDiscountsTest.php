<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ApplyDiscountsTest extends Command
{
    // Command signature
    protected $signature = 'discounts:test';

    // Command description
    protected $description = 'Test the applyDiscount function from the console';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Mocking the set and get callbacks
        $set = function ($key, $value) {
            $this->info("Setting {$key} to " . json_encode($value));
        };

        $get = function ($key) {
            if ($key === 'items') {
                return [
                    [
                        'product_id' => 1,
                        'item_price_class_id' => 'KENO1L',
                        'price' => 100,
                        'quantity' => 601, // Quantity qualifies for a discount
                    ],
                ];
            }
        
            if ($key === 'customer_price_class_id') {
                return 'FORHANDLER'; // Matching customer price class
            }
        
            return null;
        };
        

        // Call the applyDiscount function
        $this->info('Running applyDiscount function...');
        $this->applyDiscount($set, $get);

        $this->info('Done!');
    }

    private static function applyDiscount(callable $set, callable $get)
{
    try {
        $items = $get('items') ?? [];
        $customerPriceClassId = $get('customer_price_class_id');

        // Load discounts JSON
        $discountsPath = storage_path('discounts.json');
        if (!file_exists($discountsPath)) {
            Log::error('Discount file not found at: ' . $discountsPath);
            return;
        }

        $discounts = json_decode(file_get_contents($discountsPath), true);
        if (!$discounts) {
            Log::error('Failed to parse discounts JSON: ' . json_last_error_msg());
            return;
        }

        foreach ($items as $index => $item) {
            $productId = $item['product_id'] ?? null;
            $itemPriceClassId = $item['item_price_class_id'] ?? null;
            $quantity = $item['quantity'] ?? 1; // Default to 1 if quantity is not provided
            $price = $item['price'] ?? 0;

            if ($productId && $customerPriceClassId && $itemPriceClassId) {
                foreach ($discounts as $discount) {
                    if (!$discount['active']) {
                        continue; // Skip inactive discounts
                    }

                    // Check if the discount applies to the customer and item price classes
                    $applicableCustomer = false;
                    foreach ($discount['customerPriceClasses'] as $customerClass) {
                        if ($customerClass['priceClassId'] === $customerPriceClassId) {
                            $applicableCustomer = true;
                            break;
                        }
                    }

                    $applicableItem = false;
                    foreach ($discount['itemPriceClasses'] as $itemClass) {
                        if ($itemClass['priceClassId'] === $itemPriceClassId) {
                            $applicableItem = true;
                            break;
                        }
                    }

                    if ($applicableCustomer && $applicableItem) {
                        // Determine the discount based on quantity
                        $discountPercent = 0;
                        foreach ($discount['discountBreakpoints'] as $breakpoint) {
                            if ($breakpoint['active'] && $quantity >= $breakpoint['breakQuantity']) {
                                $discountPercent = max($discountPercent, $breakpoint['discountPercent']); // Use the highest applicable discount
                            }
                        }

                        // Apply the discount if found
                        if ($discountPercent > 0) {
                            $set("items.{$index}.discount_percent", $discountPercent);

                            // Calculate discounted price
                            $discountedPrice = $price - ($price * ($discountPercent / 100));
                            $set("items.{$index}.price", round($discountedPrice, 2));

                            // Log the applied discount
                            Log::info("Applied quantity discount: {$discountPercent}% to item {$productId}, quantity: {$quantity}");
                        }
                    }
                }
            }
        }
    } catch (\Throwable $e) {
        Log::error('Error applying discounts: ' . $e->getMessage());
    }
}

    
}
