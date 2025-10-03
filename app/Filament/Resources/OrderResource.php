<?php
namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\Products;
use App\Models\Customer; // Import the Customer model here
use App\Models\OrderItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Card;
use Illuminate\Support\Facades\Log;
use App\Services\DiscountService;



class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Orders';
    protected static ?string $navigationGroup = 'Order Management';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            // Customer Selection Section
            Card::make()
                ->schema([
                    Select::make('customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'name')
                        ->required()
                        ->reactive()
                        ->searchable()
                        ->afterStateUpdated(function ($state, callable $set) {
                            // When a customer is selected, populate the address fields
                            $customer = Customer::find($state);
                            if ($customer) {
                                // Populate invoice address fields
                                $set('invoice_address', $customer->invoice_address_line1 ?? '');
                                $set('invoice_city', $customer->invoice_city ?? '');
                                $set('invoice_postal_code', $customer->invoice_postal_code ?? '');
                                $set('customer_price_class_id', $customer->customer_price_class_id ?? null);
                                \Log::info('Customer selected:', [
                                    'customer_id' => $state,
                                    'customer_price_class_id' => $customer->customer_price_class_id ?? null,
                                ]);


                                // Populate delivery address fields
                                $set('delivery_address', $customer->delivery_address_line1 ?? '');
                                $set('delivery_city', $customer->delivery_city ?? '');
                                $set('delivery_postal_code', $customer->delivery_postal_code ?? '');
                                $set('customer_price_class_id', $customer->customer_price_class_id ?? '');

                            }
                        }),
                ])
                ->columns(1)
                ->label('Customer Details'),

            // Invoice Address Section
            Card::make()
                ->schema([
                    TextInput::make('customer_price_class_id')
                  ->label('Customer Price Class')
                  ->required(),


                    TextInput::make('invoice_address')
                        ->label('Invoice Address')
                        ->required(),

                    TextInput::make('invoice_city')
                        ->label('Invoice City')
                        ->required(),

                    TextInput::make('invoice_postal_code')
                        ->label('Invoice Postal Code')
                        ->required(),
                ])
                ->columns(2)
                ->label('Invoice Address'),

            // Delivery Address Section
            Card::make()
                ->schema([
                    TextInput::make('delivery_address')
                        ->label('Delivery Address')
                        ->required(),

                    TextInput::make('delivery_city')
                        ->label('Delivery City')
                        ->required(),

                    TextInput::make('delivery_postal_code')
                        ->label('Delivery Postal Code')
                        ->required(),
                ])
                ->columns(2)
                ->label('Delivery Address'),

            // Order Items Section
            Card::make()
                ->schema([
                    Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Select::make('product_id')
                                ->label('Product')
                                ->searchable()
                                ->options(Products::all()->pluck('name', 'id'))
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    // Get the selected product's price and set the price field
                                    if ($state) {
                                        $product = Products::find($state);
                                        if ($product) {
                                            $set('price', $product->price);
                                            $set('item_price_class_id', $product->item_price_class_id);

                                        }
                                    }
                                }),

                                TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->default(1) // Set the default value to 1
                            ->required()
                            ->reactive()
                            ->debounce(300) // Reduce debounce time for faster updates

                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $discountService = app(DiscountService::class);

                                $discountCodes = json_decode(file_get_contents(storage_path('discounts.json')), true);
                               // $customerPriceClassId = ('FORHANDLER'); // Fetch customer price class ID
                                $itemPriceClassId = $get('item_price_class_id');
                                $customerPriceClassId = $get('../../customer_price_class_id');


                                $discount = $discountService->getDiscountForCustomerAndProduct(
                                    $customerPriceClassId,
                                    $itemPriceClassId,
                                    $state,
                                    $discountCodes
                                );

                                $price = $get('price') ?? 0;
                                $discountedPrice = $price - ($price * ($discount / 100));
                                $set('discounted_price', $discountedPrice);

                                \Log::info('Full form state:', $get());

                            }),
                            TextInput::make('customer_price_class_id')
    ->label('Customer Price Class ID')
    ->required()
    ->hidden(),// Ensure it is part of the form state but not visible

                    
                            TextInput::make('price')
                                ->label('Price')
                                ->numeric()
                                ->disabled(), // Original price automatically populated and should not be edited manually

                                TextInput::make('discounted_price')
            ->label('Discounted Price')
            ->numeric()
            ->disabled(), // Price after applying the discount

                                TextInput::make('item_price_class_id')
                                ->label('item_price_class_id')
                                ->required()
                                ->disabled(), // Automatically populated and should not be edited manuallyng the discount percentage, not applying it

                                
                        ])
                        ->columns(3)
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn ($state, callable $set, callable $get)
                        
                        => self::recalculateTotal($set, $get)),
                        
                        
                ])
                ->label('Order Items'),

            // Total Amount Section
            Card::make()
                ->schema([
                    TextInput::make('total_amount')
                        ->label('Total Amount')
                        ->numeric()
                        ->required()
                        ->disabled(), // Automatically calculated
                ])
                ->label('Total Amount'),
        ]);
}


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer Name')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function recalculateTotal(callable $set, callable $get)
    {
        $totalAmount = 0;
    
        $items = $get('items') ?? [];
    
        if (is_array($items)) {
            foreach ($items as $item) {
                $quantity = isset($item['quantity']) && is_numeric($item['quantity'])
                ? (float) $item['quantity']
                : 1; // Default to 1 if not provided
                                $discountedPrice = $item['discounted_price'] ?? 0;
    
                $totalAmount += $quantity * $discountedPrice;
            }
        }
    
        $set('total_amount', $totalAmount);
        \Log::info('Order Item Data:', [
            'product_id' => $item['product_id'] ?? null,
            'quantity' => $item['quantity'] ?? null,
            'price' => $item['price'] ?? null,
            'discounted_price' => $item['discounted_price']
        ]);
        
    }
    




    public static function getRelations(): array
    {
        return [
            //
        ];
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







