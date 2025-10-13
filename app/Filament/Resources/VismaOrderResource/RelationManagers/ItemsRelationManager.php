<?php

namespace App\Filament\Resources\VismaOrderResource\RelationManagers;

use App\Models\Products;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    // Keep this as you had it (match your VismaOrder->Items() relation name)
    protected static string $relationship = 'Items';

    protected static ?string $recordTitleAttribute = 'inventory_id';

    public function form(Form $form): Form
    {
        // tiny calculator to keep extended_price in sync
        $recalc = function (callable $get, callable $set) {
            $qty   = (float) ($get('quantity') ?? 0);
            $price = (float) ($get('unit_price') ?? 0);
            $disc  = (float) ($get('discount_percent') ?? 0);
            $set('extended_price', round($qty * $price * (1 - $disc / 100), 4));
        };

        return $form->schema([
            TextInput::make('line_id')->numeric(),

            // Search products but store inventory_id
            Select::make('inventory_id')
                ->label('Product / Inventory ID')
                ->required()
                ->searchable()
                ->getSearchResultsUsing(function (string $query): array {
                    return Products::query()
                        ->where(function ($q) use ($query) {
                            $q->where('name', 'like', "%{$query}%")
                              ->orWhere('sku', 'like', "%{$query}%")
                              ->orWhere('inventory_id', 'like', "%{$query}%");
                        })
                        ->limit(50)
                        ->get(['inventory_id', 'name', 'sku'])
                        ->mapWithKeys(fn ($p) => [
                            $p->inventory_id => trim($p->name . ' (' . $p->sku . ')'),
                        ])
                        ->toArray();
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    if (!$value) return null;
                    $p = Products::query()
                        ->where('inventory_id', $value)
                        ->first(['name', 'sku']);
                    return $p ? trim($p->name . ' (' . $p->sku . ')') : (string) $value;
                })
                ->live()
                ->afterStateUpdated(function ($state, callable $set, callable $get) use ($recalc) {
                    if (!$state) return;

                    $p = Products::query()
                        ->where('inventory_id', $state)
                        ->first();
                    if (!$p) return;

                    // fill dependent fields from product
                    $set('inventory_description', $p->name);
                    $set('unit_of_measure', $p->sales_unit ?? $p->base_unit ?? 'STK');
                    $set('unit_price', $p->price);

                    if (!$get('quantity')) {
                        $set('quantity', 1);
                    }

                    $recalc($get, $set);
                }),

            TextInput::make('inventory_description'),

            TextInput::make('unit_of_measure')
                ->label('UoM')
                ->maxLength(50),

            TextInput::make('quantity')
                ->numeric()
                ->step('0.0001')
                ->required()
                ->live()
                ->afterStateUpdated(fn ($state, $set, $get) => $recalc($get, $set)),

            TextInput::make('unit_price')
                ->numeric()
                ->step('0.0001')
                ->live()
                ->afterStateUpdated(fn ($state, $set, $get) => $recalc($get, $set)),

            TextInput::make('discount_percent')
                ->numeric()
                ->step('0.0001')
                ->default(0)
                ->live()
                ->afterStateUpdated(fn ($state, $set, $get) => $recalc($get, $set)),

            TextInput::make('extended_price')
                ->numeric()
                ->step('0.0001')
                ->disabled()
                ->dehydrated(false),

            Textarea::make('description')->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('inventory_id')
            ->columns([
                Tables\Columns\TextColumn::make('line_id')->label('Line#')->sortable(),
                Tables\Columns\TextColumn::make('inventory_id')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('inventory_description')->limit(40)->toggleable(),
                Tables\Columns\TextColumn::make('unit_of_measure')->label('UoM')->toggleable(),
                Tables\Columns\TextColumn::make('quantity')->numeric(4)->sortable(),
                Tables\Columns\TextColumn::make('unit_price')->numeric(4)->sortable(),
                Tables\Columns\TextColumn::make('discount_percent')->numeric(4)->toggleable(),
                Tables\Columns\TextColumn::make('extended_price')->numeric(4)->toggleable(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
