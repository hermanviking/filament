<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VismaSettingsResource\Pages;
use App\Models\VismaSettings;
use Filament\Resources\Resource;
use Filament\Forms\Components\Actions\Action as FormsAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;


class VismaSettingsResource extends Resource
{
    protected static ?string $model = VismaSettings::class;
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Visma';

    public static function canCreate(): bool
    {
        return false;
    }
    public static function canDelete($record): bool
    {
        return false;
    }

    // ⬇️ Make the nav link go straight to the single edit page with a record id
    public static function getNavigationUrl(): string
    {
        $record = VismaSettings::query()->first()
            ?? VismaSettings::create([
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

        return static::getUrl('edit', ['record' => $record->getKey()]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Mode')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('environment')
                        ->options(['dev' => 'DEV', 'live' => 'LIVE'])->required(),
                    Forms\Components\Toggle::make('http_debug')->label('HTTP debug to log'),
                    Forms\Components\Toggle::make('use_finance_v1')->label('Use Finance v1 API'),
                ]),
            Forms\Components\Section::make('Base URLs')->columns(2)->schema([
                Forms\Components\TextInput::make('dev_finance_base_url'),
                Forms\Components\TextInput::make('live_finance_base_url'),
                Forms\Components\TextInput::make('dev_sales_base_url'),
                Forms\Components\TextInput::make('live_sales_base_url'),
            ]),
            Forms\Components\Section::make('Tenant')->columns(2)->schema([
                Forms\Components\TextInput::make('dev_tenant_id'),
                Forms\Components\TextInput::make('live_tenant_id'),
            ]),
            Forms\Components\Section::make('OAuth Client')->columns(2)->schema([
                Forms\Components\TextInput::make('client_id'),
                Forms\Components\TextInput::make('client_secret')->password()->revealable(),
            ]),
            Forms\Components\Section::make('Scopes')->columns(2)->schema([
                Forms\Components\TextInput::make('finance_scope')
                    ->label('Finance API scope')
                    ->placeholder('vismanet_erp_service_api:read'),
                Forms\Components\TextInput::make('salesorder_scope')
                    ->label('SalesOrder API scope')
                    ->placeholder('visma.net.erp.salesorder:read visma.net.erp.salesorder:write')

            ]),
            Forms\Components\Section::make('Order defaults')->columns(4)->schema([
                Forms\Components\TextInput::make('default_terms_id')->placeholder('e.g. 14 or NET30'),
                Forms\Components\TextInput::make('default_location_id')->placeholder('Main'),
                Forms\Components\TextInput::make('default_currency')->placeholder('NOK'),
                Forms\Components\TextInput::make('default_order_type')->placeholder('BB'),
            ]),
            Forms\Components\Section::make('Webhooks')
                ->description('Generate secrets and copy the callback URLs to the Visma portal.')
                ->schema([
                    Forms\Components\TextInput::make('finance_webhook_secret')
                        ->label('Finance webhook secret')
                        ->password()
                        ->revealable()
                        ->live() // <- important: lets other fields react to changes
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('generateFinanceSecret')
                                ->label('Generate')
                                ->icon('heroicon-m-key')
                                ->action(fn(Set $set) => $set('finance_webhook_secret', Str::random(48)))
                        )
                        ->required(),

                    Forms\Components\TextInput::make('finance_webhook_url')
                        ->label('Finance webhook URL')
                        ->readOnly()
                        ->dehydrated(false) // not saved to DB
                        ->afterStateHydrated(function (Forms\Components\TextInput $component, Get $get) {
                            $base = rtrim(config('app.url') ?: url('/'), '/');
                            $component->state($base . '/api/webhooks/visma/inventory/' . ($get('finance_webhook_secret') ?: 'YOUR_SECRET'));
                        })
                        ->reactive() // recompute when live fields change
                        ->hint('Paste this URL in your Visma Inventory subscription.'),

                    // (Optional) SalesOrder webhook fields, same pattern:
                    Forms\Components\TextInput::make('sales_webhook_secret')
                        ->label('SalesOrder webhook secret')
                        ->password()
                        ->revealable()
                        ->live()
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('generateSalesSecret')
                                ->label('Generate')
                                ->icon('heroicon-m-key')
                                ->action(fn(Set $set) => $set('sales_webhook_secret', Str::random(48)))
                        ),

                    Forms\Components\TextInput::make('sales_webhook_url')
                        ->label('SalesOrder webhook URL')
                        ->readOnly()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Forms\Components\TextInput $component, Get $get) {
                            $base = rtrim(config('app.url') ?: url('https://phplaravel-1444943-5901022.cloudwaysapps.com/'), '/');
                            $component->state($base . '/api/webhooks/visma/sales/' . ($get('sales_webhook_secret') ?: 'YOUR_SECRET'));
                        })
                        ->reactive()
                        ->hint('Paste this URL in your SalesOrder v3 subscription.'),
                ])
                ->columns(1),
            Forms\Components\Section::make('Webhooks (Finance / Inventory)')
                ->schema([
                    Forms\Components\TextInput::make('finance_webhook_subscription_id')
                        ->label('Subscription ID')
                        ->helperText('From the Visma portal subscription.')
                        ->default('') // visible even if null
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('finance_webhook_shared_secret')
                        ->label('Shared Secret (from Visma)')
                        ->password()
                        ->revealable()
                        ->helperText('Used to verify webhook signatures (HMAC).')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('finance_webhook_url')
                        ->label('Finance webhook URL')
                        ->readOnly()
                        ->dehydrated(false)
                        ->reactive()
                        ->afterStateHydrated(function (Forms\Components\TextInput $component, Get $get) {
                            $base = rtrim(config('app.url') ?: url('/'), '/');
                            $component->state($base . '/api/webhooks/visma/inventory/' . ($get('finance_webhook_secret') ?: 'YOUR_SECRET'));
                        }),
                ])
                ->columns(1),

        ]);
    }

    public static function getPages(): array
    {
        return [
            // ⬇️ Use a route with {record} so EditRecord receives the param
            'index' => Pages\ListVismaSettings::route('/'),      // <- back

            'edit' => Pages\EditVismaSettings::route('/{record}'),
        ];
    }
}
