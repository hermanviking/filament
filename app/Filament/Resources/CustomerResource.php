<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Customers';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Tabs::make('Customer')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Core')->schema([
                            Forms\Components\TextInput::make('number')->required()->unique(ignoreRecord: true),
                            Forms\Components\TextInput::make('name')->required()->columnSpanFull(),
                            Forms\Components\TextInput::make('corporateId')->label('Org nr'),
                            Forms\Components\Select::make('status')->options([
                                'Active' => 'Active',
                                'Inactive' => 'Inactive',
                            ])->nullable(),
                            Forms\Components\TextInput::make('language_id')->label('Language'),
                            Forms\Components\TextInput::make('currency_id')->label('Currency'),
                            Forms\Components\TextInput::make('sales_person_id')->label('Salesperson'),
                            Forms\Components\TextInput::make('branch_id')->label('Branch'),
                            Forms\Components\TextInput::make('default_location_id')->label('Default location'),
                        ])->columns(4),

                        Forms\Components\Tabs\Tab::make('Classes & Meta')->schema([
                            Forms\Components\TextInput::make('customer_class_id')->label('Customer class'),
                            Forms\Components\TextInput::make('customer_class_description')->columnSpanFull(),
                            Forms\Components\TextInput::make('price_class_id')->label('Price class'),
                            Forms\Components\TextInput::make('price_class_description')->columnSpanFull(),
                        ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Finance')->schema([
                            Forms\Components\Toggle::make('credit_hold'),
                            Forms\Components\TextInput::make('credit_limit')->numeric(),
                            Forms\Components\TextInput::make('balance')->numeric()->disabled(),
                            Forms\Components\TextInput::make('overdue_balance')->numeric()->disabled(),
                            Forms\Components\TextInput::make('terms_id')->label('Terms'),
                            Forms\Components\TextInput::make('payment_method_id')->label('Payment method'),
                            Forms\Components\TextInput::make('cash_discount_id')->label('Cash discount'),
                        ])->columns(3),

                        Forms\Components\Tabs\Tab::make('Tax & VAT')->schema([
                            Forms\Components\TextInput::make('tax_zone_id')->label('Tax zone'),
                            Forms\Components\TextInput::make('vat_code_id')->label('VAT code'),
                            Forms\Components\TextInput::make('vat_registration_id')->label('VAT registration id'),
                            Forms\Components\Toggle::make('vat_exempt')->inline(false),
                        ])->columns(3),

                        Forms\Components\Tabs\Tab::make('Shipping')->schema([
                            Forms\Components\TextInput::make('ship_via_id')->label('Ship via'),
                            Forms\Components\TextInput::make('delivery_terms_id')->label('Delivery terms'),
                        ])->columns(2),

                        Forms\Components\Tabs\Tab::make('E-invoice / EDI')->schema([
                            Forms\Components\TextInput::make('einvoice_participant_id')->label('Participant ID'),
                            Forms\Components\TextInput::make('einvoice_address')->label('E-invoice address'),
                            Forms\Components\TextInput::make('einvoice_operator')->label('Operator'),
                            Forms\Components\TextInput::make('edoc_email')->email()->label('eDoc Email'),
                            Forms\Components\Toggle::make('edoc_enabled')->label('eDoc enabled'),
                        ])->columns(3),

                        Forms\Components\Tabs\Tab::make('Addresses')->schema([
                            Forms\Components\Fieldset::make('Main')->schema([
                                Forms\Components\TextInput::make('main_address_line1'),
                                Forms\Components\TextInput::make('main_address_line2'),
                                Forms\Components\TextInput::make('main_postal_code'),
                                Forms\Components\TextInput::make('main_city'),
                                Forms\Components\TextInput::make('main_country'),
                            ])->columns(5),
                            Forms\Components\Fieldset::make('Invoice')->schema([
                                Forms\Components\TextInput::make('invoice_address_line1'),
                                Forms\Components\TextInput::make('invoice_address_line2'),
                                Forms\Components\TextInput::make('invoice_postal_code'),
                                Forms\Components\TextInput::make('invoice_city'),
                                Forms\Components\TextInput::make('invoice_country'),
                            ])->columns(5),
                            Forms\Components\Fieldset::make('Delivery')->schema([
                                Forms\Components\TextInput::make('delivery_address_line1'),
                                Forms\Components\TextInput::make('delivery_address_line2'),
                                Forms\Components\TextInput::make('delivery_postal_code'),
                                Forms\Components\TextInput::make('delivery_city'),
                                Forms\Components\TextInput::make('delivery_country'),
                            ])->columns(5),
                        ])->columns(1),

                        Forms\Components\Tabs\Tab::make('Contacts')->schema([
                            Forms\Components\Fieldset::make('Main')->schema([
                                Forms\Components\TextInput::make('main_contact_name'),
                                Forms\Components\TextInput::make('main_contact_attention'),
                                Forms\Components\TextInput::make('main_contact_email')->email(),
                                Forms\Components\TextInput::make('main_contact_phone'),
                                Forms\Components\TextInput::make('main_contact_phone2'),
                            ])->columns(5),
                            Forms\Components\Fieldset::make('Invoice')->schema([
                                Forms\Components\TextInput::make('invoice_contact_name'),
                                Forms\Components\TextInput::make('invoice_contact_attention'),
                                Forms\Components\TextInput::make('invoice_contact_email')->email(),
                                Forms\Components\TextInput::make('invoice_contact_phone'),
                            ])->columns(4),
                            Forms\Components\Fieldset::make('Delivery')->schema([
                                Forms\Components\TextInput::make('delivery_contact_name'),
                                Forms\Components\TextInput::make('delivery_contact_attention'),
                                Forms\Components\TextInput::make('delivery_contact_email')->email(),
                                Forms\Components\TextInput::make('delivery_contact_phone'),
                            ])->columns(4),
                        ])->columns(1),

                        Forms\Components\Tabs\Tab::make('Raw JSON (read-only)')
                            ->columns(2)
                            ->schema([
                                Forms\Components\Textarea::make('payment_settings')
                                    ->label('Payment settings')
                                    ->rows(8)
                                    ->formatStateUsing(
                                        static fn($state) => $state
                                            ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                            : ''
                                    )
                                    ->dehydrated(false)
                                    ->disabled()
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('financial_information')
                                    ->label('Financial information')
                                    ->rows(8)
                                    ->formatStateUsing(
                                        static fn($state) => $state
                                            ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                            : ''
                                    )
                                    ->dehydrated(false)
                                    ->disabled()
                                    ->columnSpanFull(),


                                Forms\Components\KeyValue::make('attributes_data')
                                    ->label('Attributes data')
                                    ->formatStateUsing(static function ($state) {
                                        if (!is_array($state)) return [];
                                        if (array_is_list($state)) {               // list of {id,value}
                                            $flat = [];
                                            foreach ($state as $row) {
                                                $k = data_get($row, 'id');
                                                $v = data_get($row, 'value');
                                                if ($k !== null) {
                                                    $flat[(string) $k] = is_scalar($v)
                                                        ? (string) $v
                                                        : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                                }
                                            }
                                            return $flat;
                                        }
                                        return $state; // already a map
                                    })
                                    ->disableAddingRows()
                                    ->disableEditingKeys()
                                    ->disableEditingValues()
                                    ->disableDeletingRows()

                                    ->dehydrated(false),   // <- don’t save the flattened view back


                                Forms\Components\Textarea::make('main_address_json')
                                    ->label('Main address json')
                                    ->rows(6)
                                    ->formatStateUsing(
                                        static fn($state) => $state
                                            ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                            : ''
                                    )
                                    ->dehydrated(false)
                                    ->disabled(),

                                Forms\Components\Textarea::make('invoice_address_json')
                                    ->label('Invoice address json')
                                    ->rows(6)
                                    ->formatStateUsing(
                                        static fn($state) => $state
                                            ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                            : ''
                                    )
                                    ->dehydrated(false)
                                    ->disabled(),

                                Forms\Components\Textarea::make('delivery_address_json')
                                    ->label('Delivery address json')
                                    ->rows(6)
                                    ->formatStateUsing(
                                        static fn($state) => $state
                                            ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                            : ''
                                    )
                                    ->dehydrated(false)
                                    ->disabled(),

                                Forms\Components\Textarea::make('main_contact_json')
                                    ->label('Main contact json')
                                    ->rows(6)
                                    ->formatStateUsing(
                                        static fn($state) => $state
                                            ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                            : ''
                                    )
                                    ->dehydrated(false)
                                    ->disabled(),

                                Forms\Components\Textarea::make('invoice_contact_json')
                                    ->label('Invoice contact json')
                                    ->rows(6)
                                    ->formatStateUsing(
                                        static fn($state) => $state
                                            ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                            : ''
                                    )
                                    ->dehydrated(false)
                                    ->disabled(),

                                Forms\Components\Textarea::make('delivery_contact_json')
                                    ->label('Delivery contact json')
                                    ->rows(6)
                                    ->formatStateUsing(
                                        static fn($state) => $state
                                            ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                            : ''
                                    )
                                    ->dehydrated(false)
                                    ->disabled(),

                                Forms\Components\KeyValue::make('custom_fields')
                                    ->label('Custom fields')
                                    ->formatStateUsing(static function ($state) {
                                        if (!is_array($state)) return [];
                                        if (array_is_list($state)) {
                                            $flat = [];
                                            foreach ($state as $row) {
                                                $k = data_get($row, 'id', data_get($row, 'name'));
                                                $v = data_get($row, 'value', data_get($row, 'name'));
                                                if ($k !== null) {
                                                    $flat[(string) $k] = is_scalar($v)
                                                        ? (string) $v
                                                        : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                                }
                                            }
                                            return $flat;
                                        }
                                        return $state;
                                    })
                                    ->disableAddingRows()
                                    ->disableEditingKeys()
                                    ->disableEditingValues()
                                    ->disableDeletingRows()
                                    ->dehydrated(false),


                                Forms\Components\Textarea::make('raw_payload')
                                    ->label('Raw payload')
                                    ->rows(12)
                                    ->formatStateUsing(
                                        static fn($state) => $state
                                            ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                            : ''
                                    )
                                    ->dehydrated(false)
                                    ->disabled()
                            ])
                            ->columns(2),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpan(1),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->searchable()->sortable()->label('Number'),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('corporateId')->label('Org nr')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('customer_class_id')->label('Class')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('price_class_id')->label('Price class')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('currency_id')->label('Currency')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('credit_hold')->boolean()->label('Hold'),
                Tables\Columns\TextColumn::make('credit_limit')->numeric(2)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('balance')->numeric(2)->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('overdue_balance')->numeric(2)->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('vat_registration_id')->label('VAT reg.')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tax_zone_id')->label('Tax zone')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('main_city')->label('Main city')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('invoice_city')->label('Invoice city')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('delivery_city')->label('Delivery city')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sales_person_id')->label('Salesperson')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('branch_id')->label('Branch')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->since()->label('Updated'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit'  => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
