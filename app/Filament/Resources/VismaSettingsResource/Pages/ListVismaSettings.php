<?php

namespace App\Filament\Resources\VismaSettingsResource\Pages;

use App\Filament\Resources\VismaSettingsResource;
use App\Models\VismaSettings;
use Filament\Resources\Pages\ListRecords;

class ListVismaSettings extends ListRecords
{
    protected static string $resource = VismaSettingsResource::class;

    public function mount(): void
    {
        // Ensure one row exists, then redirect to its edit page.
        $record = VismaSettings::query()->first() ?? VismaSettings::create([
            'environment'           => 'dev',
            'dev_finance_base_url'  => 'https://api.finance.visma.net/v1',
            'live_finance_base_url' => 'https://api.finance.visma.net/v1',
            'dev_sales_base_url'    => 'https://salesorder.visma.net/api/v3',
            'live_sales_base_url'   => 'https://salesorder.visma.net/api/v3',
            'scope_read'            => 'vismanet_erp_service_api:read',
            'scope_write'           => 'vismanet_erp_service_api:read visma.net.erp.salesorder:write',
            'default_currency'      => 'NOK',
            'default_order_type'    => 'BB',
            'http_debug'            => false,
            'use_finance_v1'        => true,
        ]);

        $this->redirect(VismaSettingsResource::getUrl('edit', [
            'record' => $record->getKey(),
        ]));
    }
}
