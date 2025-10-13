<?php

namespace App\Filament\Resources\VismaSettingsResource\Pages;

use App\Filament\Resources\VismaSettingsResource;
use App\Models\VismaSettings;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Http;

class EditVismaSettings extends EditRecord
{
    protected static string $resource = VismaSettingsResource::class;

    protected ?string $maxContentWidth = '7xl'; // or 'full'
    protected static ?string $title = 'Visma Settings';

    protected function getHeaderActions(): array
    {
        return [
            //Finance test
            Actions\Action::make('testFinanceConnection')
                ->label('Test finance connection')
                ->icon('heroicon-m-bolt')
                ->action(function () {
                    /** @var VismaSettings $s */
                    $s = $this->record;

                    $r = Http::asForm()->timeout(20)->post('https://connect.visma.com/connect/token', [
                        'grant_type'    => 'client_credentials',
                        'client_id'     => $s->client_id,
                        'client_secret' => $s->client_secret,
                        'tenant_id'     => $s->tenantId(),
                        'scope'         => $s->financeScope(),
                    ]);

                    if ($r->failed()) {
                        Notification::make()->title('Connection failed')
                            ->body("Token error ({$r->status()}): " . (string) $r->body())
                            ->danger()->send();
                        return;
                    }

                    $token = (string) data_get($r->json(), 'access_token');
                    $probe = Http::withToken($token)->acceptJson()->timeout(15)
                        ->get($s->financeBaseUrl() . 'customer', ['pageSize' => 1]);

                    if ($probe->failed()) {
                        Notification::make()->title('Token OK, API call failed')
                            ->body("GET /customer error ({$probe->status()}): " . (string) $probe->body())
                            ->warning()->send();
                        return;
                    }

                    Notification::make()->title('Connection OK')
                        ->body('Token and GET /customer succeeded.')
                        ->success()->send();
                }),
            //Sales order test
            Actions\Action::make('testSalesOrderConnection')
                ->label('Test sales order connection')
                ->icon('heroicon-m-bolt')
                ->action(function () {
                    /** @var VismaSettings $s */
                    $s = $this->record;

                    $r = Http::asForm()->timeout(20)->post('https://connect.visma.com/connect/token', [
                        'grant_type'    => 'client_credentials',
                        'client_id'     => $s->client_id,
                        'client_secret' => $s->client_secret,
                        'tenant_id'     => $s->tenantId(),
                        'scope'         => $s->salesorderScope(),
                    ]);

                    if ($r->failed()) {
                        Notification::make()->title('Connection failed')
                            ->body("Token error ({$r->status()}): " . (string) $r->body())
                            ->danger()->send();
                        return;
                    }

                    $token = (string) data_get($r->json(), 'access_token');
                    $probe = Http::withToken($token)->acceptJson()->timeout(15)
                        ->get($s->salesBaseUrl() . '/Customers?pageSize=1&pageIndex=0');

                    if ($probe->failed()) {
                        Notification::make()->title('Token OK, API call failed')
                            ->body("GET /customers error ({$probe->status()}): " . (string) $probe->body())
                            ->warning()->send();
                        return;
                    }

                    Notification::make()->title('Connection OK')
                        ->body('Token and GET /customers succeeded.')
                        ->success()->send();
                })

        ];
    }
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record->getKey()]);
    }
}
