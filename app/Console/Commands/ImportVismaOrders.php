<?php

namespace App\Console\Commands;

use App\Services\VismaOrderService;
use Illuminate\Console\Command;

class ImportVismaOrders extends Command
{
    protected $signature = 'visma:import-orders 
        {--top=50 : Page size} 
        {--max-pages= : Stop after N pages} 
        {--filter= : OData $filter, e.g. lastModified ge 2025-10-01T00:00:00Z}';

    protected $description = 'Import orders (and lines) from Visma list endpoint, following nextPage.';

    public function handle(VismaOrderService $svc): int
    {
        $top = (int) $this->option('top');
        $max = $this->option('max-pages') !== null ? (int) $this->option('max-pages') : null;
        $filter = $this->option('filter') ?: null;

        $count = $svc->importAllOrders(top: $top, maxPages: $max, filter: $filter);
        $this->info("Imported {$count} orders.");
        return self::SUCCESS;
    }
}
