<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\DiscountService;

class CalculateDiscountCommand extends Command
{
    protected $signature = 'discount:calculate 
                            {customerPriceClassId : The customer price class ID} 
                            {itemPriceClassId : The item price class ID} 
                            {quantity : The quantity of the product}';

    protected $description = 'Calculate the best discount for a customer and product based on the discounts JSON file';

    private $discountService;

    public function __construct(DiscountService $discountService)
    {
        parent::__construct();
        $this->discountService = $discountService;
    }

    public function handle()
    {
        $customerPriceClassId = $this->argument('customerPriceClassId');
        $itemPriceClassId = $this->argument('itemPriceClassId');
        $quantity = (int) $this->argument('quantity');

        $discountsPath = storage_path('discounts.json');
        if (!file_exists($discountsPath)) {
            Log::error('Discount file not found at: ' . $discountsPath);
            $this->error('Discount file not found.');
            return Command::FAILURE;
        }

        $discountCodes = json_decode(file_get_contents($discountsPath), true);
        if (!$discountCodes) {
            Log::error('Failed to parse discounts JSON: ' . json_last_error_msg());
            $this->error('Failed to parse discounts JSON.');
            return Command::FAILURE;
        }

        $discount = $this->discountService->getDiscountForCustomerAndProduct(
            $customerPriceClassId,
            $itemPriceClassId,
            $quantity,
            $discountCodes
        );

        $this->info("The applicable discount for quantity $quantity is: $discount%");
        return Command::SUCCESS;
    }
}
