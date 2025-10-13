<?php

namespace App\Filament\Resources\VismaOrderResource\Pages;

use App\Filament\Resources\VismaOrderResource;
use App\Models\Customer;
use App\Models\Products;
use App\Services\VismaOrderService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\HtmlString;

class CreateVismaOrder extends CreateRecord
{
    protected static string $resource = VismaOrderResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            // ---- Order header ----
            Forms\Components\Section::make('Order')
                ->schema([
                    TextInput::make('order_id')
                        ->label('Order #')
                        ->disabled()
                        ->dehydrated(false)
                        ->hiddenOn('create')
                        ->maxLength(100),

                    TextInput::make('type')
                        ->maxLength(50)
                        ->default('BB')
                        ->live(debounce: 300),

                    TextInput::make('status')
                        ->maxLength(50)
                        ->default('Open')
                        ->live(debounce: 300),

                    TextInput::make('currency')
                        ->maxLength(8)
                        ->default('NOK')
                        ->live(debounce: 300),

                    TextInput::make('order_total')
                        ->numeric()
                        ->step('0.0001')
                        ->helperText('Optional – Visma recalculates totals.')
                        ->live(debounce: 300),

                    TextInput::make('tax_total')
                        ->numeric()
                        ->step('0.0001')
                        ->helperText('Optional – Visma recalculates totals.')
                        ->live(debounce: 300),

                    Forms\Components\DateTimePicker::make('date')->live(debounce: 300),
                    Forms\Components\DateTimePicker::make('last_modified')->live(debounce: 300),
                ])->columns(2),

            // ---- Customer (search) ----
            Forms\Components\Section::make('Customer')
                ->schema([
                    Select::make('customer_id')
                        ->label('Customer')
                        ->required()
                        ->searchable()
                        ->live(debounce: 300)
                        ->getSearchResultsUsing(function (string $query): array {
                            return Customer::query()
                                ->where(function ($q) use ($query) {
                                    $q->where('name', 'like', "%{$query}%")
                                      ->orWhere('number', 'like', "%{$query}%");
                                })
                                ->orderBy('number')
                                ->limit(50)
                                ->get(['number', 'name'])
                                ->mapWithKeys(fn ($c) => [$c->number => "{$c->number} — {$c->name}"])
                                ->toArray();
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            if (!$value) return null;
                            $c = Customer::where('number', $value)->first(['number','name']);
                            return $c ? "{$c->number} — {$c->name}" : (string) $value;
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!$state) return;
                            if ($c = Customer::where('number', $state)->first(['name'])) {
                                $set('customer_name', $c->name);
                            }
                        }),

                    TextInput::make('customer_name')
                        ->label('Customer Name')
                        ->disabled(),

                    // Optional: customer location (falls back to 'Main')
                    TextInput::make('location')
                        ->label('Customer Location')
                        ->placeholder('Main')
                        ->live(debounce: 300),
                ])->columns(2),

            // ---- Details ----
            Forms\Components\Section::make('Details')
                ->schema([
                    TextInput::make('customer_order')->live(debounce: 300),
                    TextInput::make('customer_ref_no')->live(debounce: 300),
                    Textarea::make('description')->columnSpanFull()->live(debounce: 300),
                ])->columns(2),

            // ---- Items (one-row-per-item) ----
            Forms\Components\Section::make('Items')
                ->schema([
                    Repeater::make('items')
                        ->relationship('items') // hasMany
                        ->defaultItems(0)
                        ->addActionLabel('Add item')
                        ->collapsible()
                        ->live(debounce: 300)    // ensure preview refreshes on item changes
                        ->schema([
   Select::make('inventory_id')
    ->label('Product')
    ->required()
    ->searchable()
    ->columnSpan(3)

    // Async search — KEY must be inventory_id so the value is what Visma needs
    ->getSearchResultsUsing(function (string $query): array {
        return \App\Models\Products::query()
            ->where(fn ($q) => $q
                ->where('name', 'like', "%{$query}%")
                ->orWhere('sku', 'like', "%{$query}%")
                ->orWhere('inventory_id', 'like', "%{$query}%"))
            ->limit(50)
            ->get(['id','inventory_id','name','sku'])
            ->mapWithKeys(fn ($p) => [
                (string) $p->inventory_id => trim($p->name . ($p->sku ? " ({$p->sku})" : '')),
            ])
            ->toArray();
    })

    // Resolve label for an already-selected value
    ->getOptionLabelUsing(function ($value): ?string {
        if (!filled($value)) return null;

        // allow both inventory_id and accidental product id
        $p = \App\Models\Products::query()
            ->where('inventory_id', $value)
            ->orWhere('id', $value)
            ->first(['inventory_id','name','sku']);

        return $p
            ? trim($p->name . ($p->sku ? " ({$p->sku})" : ''))
            : (string) $value;
    })

    ->live()
    ->afterStateUpdated(function ($state, callable $set, callable $get) {
        if (!$state) return;

        // Normalize: if a product DB id was emitted, convert it to inventory_id
        $p = \App\Models\Products::query()
            ->where('inventory_id', $state)
            ->orWhere('id', $state)
            ->first();

        if (!$p) return;

        // Force the field to the INVENTORY ID so preview & save use the right value
        if ((string) $state !== (string) $p->inventory_id) {
            $set('inventory_id', (string) $p->inventory_id);
        }

        // Fill the rest of the line from the product
        $set('inventory_description', $p->name);
        $set('unit_of_measure', $p->sales_unit ?? $p->base_unit ?? 'STK');
        $set('unit_price', $p->price);

        if (! $get('quantity')) {
            $set('quantity', 1);
        }

        $qty   = (float) ($get('quantity') ?? 0);
        $price = (float) ($get('unit_price') ?? 0);
        $disc  = (float) ($get('discount_percent') ?? 0);
        $set('extended_price', round($qty * $price * (1 - $disc/100), 4));
    })

    // Extra safety: before saving to DB, coerce any accidental product id to inventory_id
    ->mutateDehydratedStateUsing(function ($state) {
        if (!filled($state)) return $state;

        $byInv = \App\Models\Products::where('inventory_id', $state)->value('inventory_id');
        if ($byInv) return (string) $byInv;

        $byId = \App\Models\Products::where('id', $state)->value('inventory_id');
        return $byId ? (string) $byId : (string) $state;
    }),



                            TextInput::make('quantity')
                                ->numeric()
                                ->step('1')
                                ->required()
                                ->columnSpan(1)
                                ->minValue(0)
                                ->type('text')         // avoid browser number quirks
                                ->inputMode('decimal')
                                ->rules(['numeric','min:0'])
                                ->live(debounce: 300)
                                ->extraAttributes([
                                    'x-on:focus'   => '$nextTick(() => $el.select())',
                                    'x-on:click'   => '$nextTick(() => $el.select())',
                                    'x-on:mouseup' => 'event.preventDefault()',
                                ])
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $qty   = (float) ($state ?? 0);
                                    $price = (float) ($get('unit_price') ?? 0);
                                    $disc  = (float) ($get('discount_percent') ?? 0);
                                    $set('extended_price', round($qty * $price * (1 - $disc/100), 4));
                                }),

                            TextInput::make('unit_price')
                                ->numeric()
                                ->columnSpan(1)
                                ->live(debounce: 300)
                                ->extraAttributes([
                                    'x-on:focus' => '$event.target.select()',
                                ])
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $qty   = (float) ($get('quantity') ?? 0);
                                    $price = (float) ($state ?? 0);
                                    $disc  = (float) ($get('discount_percent') ?? 0);
                                    $set('extended_price', round($qty * $price * (1 - $disc/100), 4));
                                }),

                            TextInput::make('discount_percent')
                                ->label('Discount %')
                                ->default(0)
                                ->type('text')
                                ->inputMode('decimal')
                                ->rules(['numeric','min:0','max:100'])
                                ->live(debounce: 300)
                                ->extraAttributes([
                                    'x-on:focus' => '$event.target.select()',
                                ])
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $disc  = (float) str_replace(',', '.', (string) $state);
                                    $qty   = (float) ($get('quantity') ?? 0);
                                    $price = (float) ($get('unit_price') ?? 0);
                                    $set('extended_price', round($qty * $price * (1 - $disc / 100), 4));
                                }),

                            TextInput::make('unit_of_measure')
                                ->label('UoM')
                                ->maxLength(50)
                                ->columnSpan(1)
                                ->live(debounce: 300),

                            TextInput::make('extended_price')
                                ->numeric()
                                ->step('0.0001')
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(1),

                            // Hidden / persisted fields
                            TextInput::make('inventory_description')->hidden()->dehydrated(),
                            TextInput::make('line_id')->numeric()->hidden(),
                            Textarea::make('description')->hidden(),
                        ])
                        ->columns(8),
                ]),

            // ---- Live JSON Preview ----
            Forms\Components\Section::make('Outgoing JSON (preview)')
                ->description('This is what will be POSTed to Visma.')
                ->schema([
                    Placeholder::make('payload_preview')
                        ->label('')
                        ->content(function (Get $get): HtmlString {
                            $payload = self::buildVismaPayloadPreview($get);

                            $needs = [];
                            if (empty(data_get($payload, 'customer.id')))         $needs[] = 'customer.id';
                            if (empty(data_get($payload, 'customer.locationId'))) $needs[] = 'customer.locationId';
                            if (empty(data_get($payload, 'termsId')))             $needs[] = 'termsId';
                            if (empty(data_get($payload, 'orderLines')))          $needs[] = 'at least one line';

                            $badgeHtml = empty($needs)
        ? '<div class="mb-2 inline-block rounded bg-green-100 text-green-800 px-2 py-1 text-xs">OK</div>'
        : '<div class="mb-2 inline-block rounded bg-amber-100 text-amber-800 px-2 py-1 text-xs">Missing: '
            . e(implode(', ', $needs)) . '</div>';

                            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                            $html = $badgeHtml
                                . '<pre class="text-xs whitespace-pre-wrap p-3 rounded">'
                                . e($json)
                                . '</pre>';

                            return new HtmlString($html);
                        })
                        ->live(debounce: 300) // <- force refresh as state changes
                        ->columnSpanFull(),
                ]),
        ])->columns(2);
    }

    protected function afterCreate(): void
    {
        $order = $this->record->load('items');

        try {
            if (! $order->customer_id) {
                throw new \RuntimeException('Customer is required before pushing to Visma.');
            }
            if (! $order->items->whereNotNull('inventory_id')->where('quantity', '>', 0)->count()) {
                throw new \RuntimeException('Add at least one item with inventory and quantity > 0.');
            }

            /** @var VismaOrderService $svc */
            $svc = app(VismaOrderService::class);
            $svc->createOrderInVisma($order);

            Notification::make()
                ->title('Order sent to Visma')
                ->success()
                ->body('The order was created in Visma and synced back.')
                ->send();

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Visma push failed')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * Build the exact payload that will be POSTed to Visma (for preview).
     */
    private static function buildVismaPayloadPreview(Get $get): array
    {
        $payload = [
            'type'        => $get('type') ?: 'BB',
            'customer'    => [
                'id'         => $get('customer_id'),                // e.g. "10000"
                'locationId' => $get('location') ?: 'Main',         // ensure this exists on the customer
            ],
            // satisfy Visma server-side requirement if provided
            'termsId'     => '30' ?: env('VISMA_DEFAULT_TERMS_ID'),
            'currency'    => $get('currency') ?: 'NOK',
            'description' => $get('description'),
        ];

        $lines = [];
        foreach ((array) $get('items') as $row) {
            if (empty($row['inventory_id']) || empty($row['quantity'])) {
                continue;
                   // 💥 Add this safety check to resolve correct inventory_id:
    $product = \App\Models\Products::query()
        ->where('inventory_id', $row['inventory_id'])
        ->orWhere('id', $row['inventory_id'])
        ->first(['inventory_id']);

    if (! $product) {
        continue; // skip invalid items
    }
            }
            $lines[] = [
                'inventoryId'     => (string) $row['inventory_id'],
                'quantity'        => (float)  ($row['quantity'] ?? 0),
                'unitPrice'       => isset($row['unit_price']) ? (float) $row['unit_price'] : null,
                'discountPercent' => isset($row['discount_percent']) ? (float) $row['discount_percent'] : null,
                'unitOfMeasure'   => $row['unit_of_measure'] ?? null,
                'description'     => $row['description'] ?? null,
            ];
        }
        if ($lines) {
            $payload['orderLines'] = $lines;
            // Dump raw item data for debugging
$payload['rawItems'] = $get('items');
 
        }

        return $payload;
    }
}
