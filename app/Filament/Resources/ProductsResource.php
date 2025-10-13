<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductsResource\Pages;
use App\Models\Products;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Filament\Forms as Forms;


class ProductsResource extends Resource
{
    protected static ?string $model = Products::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Product Details')
                    ->tabs([
                        Tab::make('General')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('sku')
                                                    ->label('SKU')
                                                    ->required()
                                                    ->maxLength(255),
                                                TextInput::make('inventory_id')
                                                    ->label('Inventory ID')
                                                    ->maxLength(255),
                                                TextInput::make('name')
                                                    ->label('Name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->columnSpan(2),
                                                Textarea::make('description')
                                                    ->label('Description')
                                                    ->rows(3)
                                                    ->columnSpan(2),
                                                Textarea::make('body')
                                                    ->label('Body (HTML)')
                                                    ->rows(6)
                                                    ->columnSpan(2),
                                                TextInput::make('image')
                                                    ->label('Image URL')
                                                    ->maxLength(255)
                                                    ->columnSpan(2),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Classification')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('status')->maxLength(255),
                                                TextInput::make('product_type')->maxLength(255),
                                                TextInput::make('category')->maxLength(255),
                                                TextInput::make('brand')->maxLength(255),
                                                TextInput::make('short_description')->maxLength(255)->columnSpan(2),
                                                TextInput::make('item_class_id')->maxLength(255),
                                                TextInput::make('item_class_description')->maxLength(255),
                                                TextInput::make('item_price_class_id')->maxLength(255),
                                                TextInput::make('price_class_id')->maxLength(255),
                                                TextInput::make('price_class_description')->maxLength(255)->columnSpan(2),
                                                TextInput::make('vat_code_id')->label('VAT Code')->maxLength(255),
                                                TextInput::make('vat_code_description')->maxLength(255),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Pricing & Ratings')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('price')
                                                    ->numeric()
                                                    ->label('Price'),
                                                TextInput::make('recommended_price')
                                                    ->numeric()
                                                    ->label('Recommended Price'),
                                                TextInput::make('current_cost')
                                                    ->numeric()
                                                    ->label('Current Cost'),
                                                TextInput::make('last_cost')
                                                    ->numeric()
                                                    ->label('Last Cost'),
                                                TextInput::make('rating_rate')
                                                    ->numeric()
                                                    ->label('Rating Rate'),
                                                TextInput::make('rating_count')
                                                    ->numeric()
                                                    ->label('Rating Count'),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Inventory & Logistics')
                            ->schema([
                                Section::make('Units & Warehousing')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('base_unit')->maxLength(255),
                                                TextInput::make('sales_unit')->maxLength(255),
                                                TextInput::make('purchase_unit')->maxLength(255),
                                                TextInput::make('default_warehouse_id')->maxLength(255),
                                                TextInput::make('default_issue_from')->maxLength(255),
                                                TextInput::make('default_receipt_to')->maxLength(255),
                                            ]),
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('quantity_on_hand')->numeric(),
                                                TextInput::make('quantity_available')->numeric(),
                                                TextInput::make('quantity_available_for_shipment')->numeric(),
                                            ]),
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('weight')
                                                    ->numeric()
                                                    ->label('Weight'),
                                                TextInput::make('weight_uom')
                                                    ->label('Weight UOM')
                                                    ->maxLength(255),
                                                TextInput::make('volume_value')
                                                    ->numeric()
                                                    ->label('Volume'),
                                                TextInput::make('volume_uom')
                                                    ->label('Volume UOM')
                                                    ->maxLength(255),
                                                TextInput::make('country_of_origin')
                                                    ->label('Country of Origin')
                                                    ->maxLength(255),
                                                TextInput::make('supplementary_measure_unit')
                                                    ->label('Supplementary Unit')
                                                    ->maxLength(255),
                                            ]),
                                    ]),
                                Section::make('Flags')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Toggle::make('stock_item')->label('Stock Item'),
                                                Toggle::make('kit_item')->label('Kit Item'),
                                                Toggle::make('is_parent')->label('Is Parent'),
                                                Toggle::make('is_display_only')->label('Display Only'),
                                                Toggle::make('is_hazardous')->label('Hazardous'),
                                                Toggle::make('is_web_item')->label('Web Item'),
                                                Toggle::make('is_web_item_b2b')->label('Web Item B2B'),
                                                Toggle::make('is_web_item_b2c')->label('Web Item B2C'),
                                            ]),
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('volume')->maxLength(255),
                                                TextInput::make('color_code')->maxLength(255),
                                                TextInput::make('kasselov_code')->maxLength(255),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Integrations')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        DateTimePicker::make('last_modified_at')
                                            ->label('Visma Last Modified')
                                            ->seconds(false),
                                        TextInput::make('visma_timestamp')
                                            ->label('Visma Timestamp')
                                            ->maxLength(255),
                                        Textarea::make('attributes_data')
                                            ->label('Attributes JSON')
                                            ->rows(5)
                                            ->helperText('Raw JSON attribute data returned from Visma.')
                                            ->afterStateHydrated(fn(Textarea $component, $state) => $component->state($state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ''))
                                            ->disabled()
                                            ->dehydrated(false),
                                        Textarea::make('warehouse_details')
                                            ->label('Warehouse Details JSON')
                                            ->rows(5)
                                            ->helperText('Raw JSON warehouse data returned from Visma.')
                                            ->afterStateHydrated(fn(Textarea $component, $state) => $component->state($state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ''))
                                            ->disabled()
                                            ->dehydrated(false),
                                        Textarea::make('cross_references')
                                            ->label('Cross References JSON')
                                            ->rows(5)
                                            ->helperText('Raw JSON cross reference data returned from Visma.')
                                            ->afterStateHydrated(fn(Textarea $component, $state) => $component->state($state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ''))
                                            ->disabled()
                                            ->dehydrated(false),
                                    ])
                                    ->columns(1),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable()
                    ->limit(40),
                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('product_type')
                    ->label('Type')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Price')
                    ->sortable()
                    ->alignRight()
                    ->formatStateUsing(fn($state): string => $state === null ? '-' : number_format((float) $state, 2) . ' kr'),
                TextColumn::make('recommended_price')
                    ->label('Recommended')
                    ->alignRight()
                    ->toggleable()
                    ->formatStateUsing(fn($state): string => $state === null ? '-' : number_format((float) $state, 2) . ' kr'),
                TextColumn::make('quantity_on_hand')
                    ->label('On Hand')
                    ->sortable()
                    ->alignRight()
                    ->formatStateUsing(fn($state): string => $state === null ? '-' : number_format((float) $state, 2)),
                IconColumn::make('is_web_item')
                    ->label('Web')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_hazardous')
                    ->label('Hazardous')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('last_modified_at')
                    ->label('Last Synced')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('item_price_class_id')
                    ->label('Price Class')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                CreateAction::make(),

                Action::make('importVismaData')
                    ->label('Import Visma Data')
                    ->icon('heroicon-o-cloud-arrow-down')
                    ->modalHeading('Import products from Visma')
                    ->modalSubmitActionLabel('Run import')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'Active' => 'Active',
                                'Inactive' => 'Inactive',
                            ])
                            ->default('Active')
                            ->required(),
                        Forms\Components\TextInput::make('page_size')
                            ->label('Page size')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100) // Inventory API typically maxes at 100
                            ->default(100)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $status   = $data['status'] ?? 'Active';
                        $pageSize = (int) ($data['page_size'] ?? 100);

                        $exit = Artisan::call('import:visma-products', [
                            '--status'    => $status,
                            '--page-size' => $pageSize,
                        ]);

                        $output = trim(Artisan::output());

                        if ($exit !== 0) {
                            Notification::make()
                                ->title('Failed to import products from Visma')
                                ->danger()
                                ->body(Str::limit($output, 1000))
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('Products imported successfully from Visma')
                            ->success()
                            ->body(Str::limit($output, 1000))
                            ->send();
                    }),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProducts::route('/create'),
            'edit' => Pages\EditProducts::route('/{record}/edit'),
        ];
    }
}
