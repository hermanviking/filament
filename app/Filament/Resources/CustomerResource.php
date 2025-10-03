<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Artisan;
use Filament\Facades\Filament; // Correct import for Filament facade
use Filament\Notifications\Notification;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Customers';
    protected static ?string $navigationGroup = 'Customer Management';

    // Define the form layout
    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                self::generalInformationSection(),
                self::mainAddressSection(),
                self::invoiceAddressSection(),
                self::deliveryAddressSection(),
                self::mainContactSection(),
                self::invoiceContactSection(),
                self::deliveryContactSection(),
            ]);
    }

    // General Information Section
    private static function generalInformationSection(): Card
    {
        return Card::make()
            ->schema([
                TextInput::make('number')
                    ->label('Customer Number')
                    ->required()
                    ->unique(Customer::class, 'number'),

                TextInput::make('name')
                    ->label('Customer Name')
                    ->required(),
            ]);
    }

    // Main Address Section
    private static function mainAddressSection(): Card
    {
        return Card::make()
            ->schema([
                TextInput::make('corporateId')
                    ->label('Corporate ID')
                    ->maxLength(50)
                    ->required(),

                TextInput::make('main_address_line1')
                    ->label('Main Address Line 1')
                    ->required(),

                TextInput::make('main_postal_code')
                    ->label('Main Postal Code')
                    ->required(),

                TextInput::make('main_city')
                    ->label('Main City')
                    ->required(),

                TextInput::make('main_country')
                    ->label('Main Country')
                    ->required(),

                TextInput::make('main_county')
                    ->label('Main County')
                    ->required(),
            ]);
    }

    // Invoice Address Section
    private static function invoiceAddressSection(): Card
    {
        return Card::make()
            ->schema([
                TextInput::make('invoice_address_line1')
                    ->label('Invoice Address Line 1')
                    ->required(),

                TextInput::make('invoice_postal_code')
                    ->label('Invoice Postal Code')
                    ->required(),

                TextInput::make('invoice_city')
                    ->label('Invoice City')
                    ->required(),

                TextInput::make('invoice_country')
                    ->label('Invoice Country')
                    ->required(),

                TextInput::make('invoice_county')
                    ->label('Invoice County')
                    ->required(),
            ]);
    }

    // Delivery Address Section
    private static function deliveryAddressSection(): Card
    {
        return Card::make()
            ->schema([
                TextInput::make('delivery_address_line1')
                    ->label('Delivery Address Line 1')
                    ->required(),

                TextInput::make('delivery_postal_code')
                    ->label('Delivery Postal Code')
                    ->required(),

                TextInput::make('delivery_city')
                    ->label('Delivery City')
                    ->required(),

                TextInput::make('delivery_country')
                    ->label('Delivery Country')
                    ->required(),

                TextInput::make('delivery_county')
                    ->label('Delivery County')
                    ->required(),
            ]);
    }

    // Main Contact Section
    private static function mainContactSection(): Card
    {
        return Card::make()
            ->schema([
                TextInput::make('main_contact_name')
                    ->label('Main Contact Name')
                    ->required(),

                TextInput::make('main_contact_attention')
                    ->label('Main Contact Attention')
                    ->required(),

                TextInput::make('main_contact_email')
                    ->label('Main Contact Email')
                    ->required()
                    ->email(),

                TextInput::make('main_contact_phone')
                    ->label('Main Contact Phone')
                    ->required(),
            ]);
    }

    // Invoice Contact Section
    private static function invoiceContactSection(): Card
    {
        return Card::make()
            ->schema([
                TextInput::make('invoice_contact_name')
                    ->label('Invoice Contact Name')
                    ->required(),

                TextInput::make('invoice_contact_attention')
                    ->label('Invoice Contact Attention')
                    ->required(),

                TextInput::make('invoice_contact_email')
                    ->label('Invoice Contact Email')
                    ->required()
                    ->email(),

                TextInput::make('invoice_contact_phone')
                    ->label('Invoice Contact Phone')
                    ->required(),
            ]);
    }

    // Delivery Contact Section (Optional)
    private static function deliveryContactSection(): Card
    {
        return Card::make()
            ->schema([
                TextInput::make('delivery_contact_name')
                    ->label('Delivery Contact Name')
                    ->required(),

                TextInput::make('delivery_contact_attention')
                    ->label('Delivery Contact Attention')
                    ->required(),

                TextInput::make('delivery_contact_email')
                    ->label('Delivery Contact Email')
                    ->required()
                    ->email(),

                TextInput::make('delivery_contact_phone')
                    ->label('Delivery Contact Phone')
                    ->required(),
                TextInput::make('customer_price_class_id	')
                    ->label('Price class')
                    ->required(),

            ]);
    }

    // Define the table layout
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Customer Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('number')
                    ->label('Customer Number')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('main_address_line1')
                    ->label('Main Address')
                    ->sortable(),

                TextColumn::make('main_contact_name')
                    ->label('Main Contact Name')
                    ->sortable(),

                TextColumn::make('invoice_contact_name')
                    ->label('Invoice Contact Name')
                    ->sortable(),
                TextColumn::make('customer_price_class_id')
                    ->label('Costumer Price Class')
                    ->sortable(),
            ])
            ->filters([
                // Add filters if needed
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
                Tables\Actions\CreateAction::make(),
                Action::make('importVismaData')
                    ->label('Import Visma Data')
                    ->action(function () {
                        try {
                            Artisan::call('app:import-customers');
                            Notification::make()
                                ->title('Customers imported successfully from Visma.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to import customers from Visma.')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    })
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Define relations if applicable
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
