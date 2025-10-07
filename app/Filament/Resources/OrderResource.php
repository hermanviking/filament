<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Products;
use App\Services\DiscountService;
use App\Services\VismaOrderService;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Orders';
    protected static ?string $navigationGroup = 'Order Management';

    private static ?array $cachedDiscountCodes = null;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Card::make()
                ->label('Customer')
                ->schema([
                    Select::make('customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'name')
                        ->required()
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                            $customer = Customer::find($state);

                            if ($customer) {
                                $set('invoice_address', $customer->invoice_address_line1 ?? '');
                                $set('invoice_city', $customer->invoice_city ?? '');
                                $set('invoice_postal_code', $customer->invoice_postal_code ?? '');
                                $set('delivery_address', $customer->delivery_address_line1 ?? '');
                                $set('delivery_city', $customer->delivery_city ?? '');
                                $set('delivery_postal_code', $customer->delivery_postal_code ?? '');
                                $set('customer_price_class_id', $customer->customer_price_class_id ?? null);
                            } else {
                                $set('invoice_address', null);
                                $set('invoice_city', null);
                                $set('invoice_postal_code', null);
                                $set('delivery_address', null);
                                $set('delivery_city', null);
                                $set('delivery_postal_code', null);
                                $set('customer_price_class_id', null);
                            }

                            self::updateDiscountsForAllItems($set, $get);
                        }),
                    TextInput::make('customer_price_class_id')
                        ->label('Customer Price Class')
                        ->required(),
                ])
                ->columns(2),
            Card::make()
                ->label('Invoice Address')
                ->schema([
                    TextInput::make('invoice_address')
                        ->label('Address')
                        ->required(),
                    TextInput::make('invoice_city')
                        ->label('City')
                        ->required(),
                    TextInput::make('invoice_postal_code')
                        ->label('Postal Code')
                        ->required(),
                ])
                ->columns(3),
            Card::make()
                ->label('Delivery Address')
                ->schema([
                    TextInput::make('delivery_address')
                        ->label('Address')
                        ->required(),
                    TextInput::make('delivery_city')
                        ->label('City')
                        ->required(),
                    TextInput::make('delivery_postal_code')
                        ->label('Postal Code')
                        ->required(),
                ])
                ->columns(3),
            Card::make()
                ->label('Order Lines')
                ->schema([
                    Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Select::make('product_id')
                                ->label('Product')
                                ->options(fn () => Products::query()->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                    if ($state) {
                                        $product = Products::find($state);
                                        $set('price', $product?->price);
                                        $set('item_price_class_id', $product?->item_price_class_id);
                                    } else {
                                        $set('price', null);
                                        $set('item_price_class_id', null);
                                    }

                                    self::applyDiscountToItem($set, $get);
                                    self::recalculateTotal($set, $get);
                                }),
                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                    self::applyDiscountToItem($set, $get);
                                    self::recalculateTotal($set, $get);
                                }),
                            TextInput::make('price')
                                ->label('Unit Price')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(true),
                            TextInput::make('discount_percent')
                                ->label('Discount %')
                                ->numeric()
                                ->disabled()
                                ->default(0)
                                ->dehydrated(true),
                            TextInput::make('discount_amount')
                                ->label('Discount Amount')
                                ->numeric()
                                ->disabled()
                                ->default(0)
                                ->dehydrated(true),
                            TextInput::make('discounted_price')
                                ->label('Discounted Unit Price')
                                ->numeric()
                                ->disabled()
                                ->default(0)
                                ->dehydrated(true),
                            TextInput::make('item_price_class_id')
                                ->label('Item Price Class')
                                ->disabled()
                                ->dehydrated(false),
                        ])
                        ->columns(6)
                        ->defaultItems(0)
                        ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::recalculateTotal($set, $get)),
                ]),
            Card::make()
                ->label('Totals')
                ->schema([
                    TextInput::make('total_amount')
                        ->label('Total Amount')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(true),
                ]),
            Card::make()
                ->label('Visma')
                ->schema([
                    TextInput::make('visma_sales_order_number')
                        ->label('Visma Order #')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('visma_status')
                        ->label('Visma Status')
                        ->disabled()
                        ->dehydrated(false),
                    DateTimePicker::make('visma_last_synced_at')
                        ->label('Last Synced')
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->sortable()
                    ->money('NOK'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->sortable(),
                Tables\Columns\TextColumn::make('visma_sales_order_number')
                    ->label('Visma Order #')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('visma_status')
                    ->label('Visma Status')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('visma_last_synced_at')
                    ->label('Visma Synced')
                    ->dateTime()
                    ->toggleable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('refreshFromVisma')
                    ->label('Refresh from Visma')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (Order $record): bool => filled($record->visma_sales_order_number))
                    ->action(function (Order $record): void {
                        try {
                            $updatedOrder = app(VismaOrderService::class)->syncOrderFromVisma($record->visma_sales_order_number);

                            Notification::make()
                                ->title('Order refreshed from Visma')
                                ->body('Sales order ' . $updatedOrder->visma_sales_order_number . ' has been synchronised.')
                                ->success()
                                ->send();
                        } catch (\Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('Failed to refresh order from Visma')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('sendToVisma')
                    ->label('Send to Visma')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->action(function (Order $record): void {
                        try {
                            $updatedOrder = app(VismaOrderService::class)->pushOrderToVisma($record);

                            Notification::make()
                                ->title('Order sent to Visma')
                                ->body('Sales order ' . $updatedOrder->visma_sales_order_number . ' synced successfully.')
                                ->success()
                                ->send();
                        } catch (\Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('Failed to send order to Visma')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function applyDiscountToItem(callable $set, callable $get): void
    {
        $quantity = max((float) ($get('quantity') ?? 1), 1);
        $price = (float) ($get('price') ?? 0);
        $customerPriceClassId = $get('../../customer_price_class_id');
        $itemPriceClassId = $get('item_price_class_id');

        $discountPercent = self::resolveDiscountPercent($customerPriceClassId, $itemPriceClassId, $quantity);
        $discountedPrice = self::calculateDiscountedUnitPrice($price, $discountPercent);
        $discountAmount = round(($price - $discountedPrice) * $quantity, 2);

        $set('discount_percent', $discountPercent);
        $set('discount_amount', $discountAmount);
        $set('discounted_price', $discountedPrice);
    }

    private static function updateDiscountsForAllItems(callable $set, callable $get): void
    {
        $items = $get('items') ?? [];

        if (!is_array($items)) {
            self::recalculateTotal($set, $get);

            return;
        }

        foreach ($items as $index => $item) {
            $quantity = max((float) ($item['quantity'] ?? 1), 1);
            $price = (float) ($item['price'] ?? 0);
            $itemPriceClassId = $item['item_price_class_id'] ?? null;
            $customerPriceClassId = $get('customer_price_class_id');

            $discountPercent = self::resolveDiscountPercent($customerPriceClassId, $itemPriceClassId, $quantity);
            $discountedPrice = self::calculateDiscountedUnitPrice($price, $discountPercent);
            $discountAmount = round(($price - $discountedPrice) * $quantity, 2);

            $set("items.$index.discount_percent", $discountPercent);
            $set("items.$index.discount_amount", $discountAmount);
            $set("items.$index.discounted_price", $discountedPrice);
        }

        self::recalculateTotal($set, $get);
    }

    private static function resolveDiscountPercent(?string $customerPriceClassId, ?string $itemPriceClassId, float $quantity): float
    {
        if (!filled($customerPriceClassId) || !filled($itemPriceClassId)) {
            return 0.0;
        }

        $discountCodes = self::getDiscountCodes();

        if ($discountCodes === []) {
            return 0.0;
        }

        /** @var DiscountService $discountService */
        $discountService = app(DiscountService::class);

        return (float) $discountService->getDiscountForCustomerAndProduct(
            $customerPriceClassId,
            $itemPriceClassId,
            $quantity,
            $discountCodes
        );
    }

    private static function calculateDiscountedUnitPrice(float $price, float $discountPercent): float
    {
        if ($discountPercent <= 0) {
            return round($price, 2);
        }

        $discounted = $price - ($price * ($discountPercent / 100));

        return round(max($discounted, 0), 2);
    }

    private static function getDiscountCodes(): array
    {
        if (self::$cachedDiscountCodes !== null) {
            return self::$cachedDiscountCodes;
        }

        $path = storage_path('discounts.json');

        if (!file_exists($path)) {
            return self::$cachedDiscountCodes = [];
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            return self::$cachedDiscountCodes = [];
        }

        $decoded = json_decode($contents, true);

        return self::$cachedDiscountCodes = is_array($decoded) ? $decoded : [];
    }

    private static function recalculateTotal(callable $set, callable $get): void
    {
        $items = $get('items') ?? [];

        if (!is_array($items) || $items === []) {
            $set('total_amount', 0);

            return;
        }

        $total = 0.0;

        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 1);
            $unitPrice = (float) ($item['discounted_price'] ?? $item['price'] ?? 0);

            $total += $quantity * $unitPrice;
        }

        $set('total_amount', round($total, 2));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
