<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BundleResource\Pages;
use App\Models\Bundle;
use App\Models\Products;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class BundleResource extends Resource
{
    protected static ?string $model = Bundle::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                    ->schema([
                        TextInput::make('sku')
                            ->label('SKU')
                            ->required(),

                        TextInput::make('name')
                            ->label('Bundle Name')
                            ->required(),

                        TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->required(),

                        TextInput::make('description')
                            ->label('Description')
                            ->nullable(),
                    ])
                    ->columns(2),
                Card::make()
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items') // Specifies the relationship for Repeater
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->options(Products::all()->pluck('name', 'id'))
                                    ->required()
                                    ->reactive()
                                    ->searchable()
                                ,

                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),

                                TextInput::make('price')
                                    ->label('price')
                                    ->numeric()
                            ])
                            ->columns(3)
                            ->label('Bundle Products')
                            ->reactive()

                    ])
                    ->columns(1),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')->label('SKU'),
                TextColumn::make('name')->label('Bundle Name'),
                TextColumn::make('price')->label('Price'),
                TextColumn::make('items.product.name')
                    ->label('Products')
                    ->limit(3), // Limits the number of products displayed
                TextColumn::make('dynamic_price')
                    ->label('Calculated Price')
                    ->getStateUsing(fn($record) => $record->calculatePrice()),
            ])
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
            'index' => Pages\ListBundles::route('/'),
            'create' => Pages\CreateBundle::route('/create'),
            'edit' => Pages\EditBundle::route('/{record}/edit'),
        ];
    }

}
