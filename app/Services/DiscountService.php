<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class DiscountService
{
    public function getDiscountForCustomerAndProduct($customerPriceClassId, $itemPriceClassId, $quantity, $discountCodes)
    {
        Log::info("Starting discount calculation...");
        Log::info("Customer Price Class ID: $customerPriceClassId");
        Log::info("Item Price Class ID: $itemPriceClassId");
        Log::info("Quantity: $quantity");

        if (empty($discountCodes)) {
            Log::warning("No discount codes provided!");
            return 0;
        }

        Log::info("Discount Codes: " . json_encode($discountCodes));

        $bestDiscount = 0;

        foreach ($discountCodes as $index => $discountCode) {
            //Log::info("Processing discount code #$index: " . json_encode($discountCode));

            if (!$discountCode['active']) {
                //Log::info("Skipping inactive discount code.");
                continue;
            }

            // Check if customerPriceClasses match
            $customerMatch = collect($discountCode['customerPriceClasses'])
                ->contains(fn($class) => (string) $class['priceClassId'] === (string) $customerPriceClassId);
            //Log::info("Customer match: " . ($customerMatch ? "yes" : "no"));

            // Check if itemPriceClasses match
            $itemMatch = collect($discountCode['itemPriceClasses'])
                ->contains(fn($class) => (string) $class['priceClassId'] === (string) $itemPriceClassId);
            Log::info("Item match: " . ($itemMatch ? "yes" : "no"));

            if ($customerMatch && $itemMatch) {
                foreach ($discountCode['discountBreakpoints'] as $breakIndex => $breakpoint) {
                  //  Log::info("Evaluating breakpoint #$breakIndex: " . json_encode($breakpoint));

                    if (!$breakpoint['active']) {
                        Log::info("Skipping inactive breakpoint.");
                        continue;
                    }

                    if ($quantity >= $breakpoint['breakQuantity']) {
                        $discountPercent = $breakpoint['discountPercent'] ?? 0;
                      //  Log::info("Breakpoint applies. Discount Percent: $discountPercent");

                        if ($discountPercent > $bestDiscount) {
                           // Log::info("Updating best discount to: $discountPercent");
                            $bestDiscount = $discountPercent;
                        }
                    } else {
                       // Log::info("Quantity does not meet breakpoint requirement (needed: {$breakpoint['breakQuantity']}, provided: $quantity).");
                    }
                }
            } else {
              //  Log::info("No match for customer or item price class. Skipping this discount code.");
            }
        }

       // Log::info("Final best discount: $bestDiscount%");
        return $bestDiscount;
    }
}
