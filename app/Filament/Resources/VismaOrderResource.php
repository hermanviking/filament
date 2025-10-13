<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VismaOrderResource\Pages;
use App\Filament\Resources\VismaOrderResource\RelationManagers;
use App\Models\VismaOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\VismaOrderResource\RelationManagers\ItemsRelationManager;
use App\Models\Products;
use Filament\Tables\Columns\TextColumn;



class VismaOrderResource extends Resource
{
    protected static ?string $model = VismaOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Salg';
    protected static ?int $navigationSort = 2;
        protected static ?string $recordTitleAttribute = 'order_id';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
               Forms\Components\Section::make('Order')
                ->schema([
                    Forms\Components\TextInput::make('order_id')->label('Order #')->required()->maxLength(100),
                    Forms\Components\TextInput::make('type')->maxLength(50),
                    Forms\Components\TextInput::make('status')->maxLength(50),
                    Forms\Components\TextInput::make('currency')->maxLength(8),
                    Forms\Components\TextInput::make('order_total')->numeric()->step('0.0001'),
                    Forms\Components\TextInput::make('tax_total')->numeric()->step('0.0001'),
                    Forms\Components\DateTimePicker::make('date'),
                    Forms\Components\DateTimePicker::make('last_modified'),
                ])->columns(2),

            Forms\Components\Section::make('Customer')
                ->schema([
                    Forms\Components\TextInput::make('customer_id')->label('Customer ID')->maxLength(100),
                    Forms\Components\TextInput::make('customer_name')->label('Customer Name')->maxLength(255),
                ])->columns(2),

            Forms\Components\Section::make('Details')
                ->schema([
                    Forms\Components\TextInput::make('location'),
                    Forms\Components\TextInput::make('customer_order'),
                    Forms\Components\TextInput::make('customer_ref_no'),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Raw / JSON (read-only)')
                ->schema([
                    Forms\Components\KeyValue::make('totals')->label('Totals')->reorderable()->addButtonLabel('Add')->deleteButtonLabel('Delete')->columnSpanFull(),
                    Forms\Components\Textarea::make('raw_payload')->rows(8)->columnSpanFull()
                        ->helperText('Full API entry stored for reference.'),
                ])->collapsed(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
Tables\Columns\TextColumn::make('order_id')->label('Order #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->sortable(),
                Tables\Columns\TextColumn::make('status')->sortable(),
                Tables\Columns\TextColumn::make('customer_name')->searchable(),
                Tables\Columns\TextColumn::make('order_total')->numeric(4)->sortable(),
                Tables\Columns\TextColumn::make('currency')->sortable(),
                Tables\Columns\TextColumn::make('last_modified')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),            ])
            ->filters([
                 Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options(fn () => VismaOrder::query()
                        ->select('status')->whereNotNull('status')->distinct()->orderBy('status')->pluck('status', 'status')->all()
                    ),
                Tables\Filters\Filter::make('modified_since')
                    ->form([
                        Forms\Components\DateTimePicker::make('from')->label('Modified from'),
                        Forms\Components\DateTimePicker::make('to')->label('to'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $from) => $q->where('last_modified', '>=', $from))
                            ->when($data['to'] ?? null, fn ($q, $to) => $q->where('last_modified', '<=', $to));
                    }),
            ])
            ->actions([
                                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
                        ItemsRelationManager::class,


        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVismaOrders::route('/'),
            'create' => Pages\CreateVismaOrder::route('/create'),
            'edit' => Pages\EditVismaOrder::route('/{record}/edit'),
            'view' => Pages\ViewVismaOrder::route('/{record}'),

        ];
    }
}
