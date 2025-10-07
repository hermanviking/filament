<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class import extends Command
{
    protected $signature = 'app:import';

    protected $description = 'Proxy command that calls the Visma product importer.';

    public function handle(): int
    {
        $this->warn('`app:import` is deprecated. Forwarding to `import:visma-products`.');

        return $this->call('import:visma-products');
    }
}
