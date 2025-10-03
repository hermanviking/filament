<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FakeStoreUsersResource\Pages;
use App\Filament\Resources\FakeStoreUsersResource\RelationManagers;
use App\Models\FakeStoreUsers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FakeStoreUsersResource extends Resource
{
    protected static ?string $model = FakeStoreUsers::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('username'),
                Forms\Components\TextInput::make('email'),
                Forms\Components\TextInput::make('password'),
                Forms\Components\TextInput::make('first_name'),
                Forms\Components\TextInput::make('last_name'),
                Forms\Components\TextInput::make('phone'),
                Forms\Components\TextInput::make('address'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')
                ->label('Username')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('email')
                ->label('Email')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('first_name')
                ->label('First Name')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('last_name')
                ->label('Last Name')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('phone')
                ->label('Phone')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('address')
                ->label('Address')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->label('Created At'),
            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()
                ->label('Updated At'),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFakeStoreUsers::route('/'),
            'create' => Pages\CreateFakeStoreUsers::route('/create'),
            'edit' => Pages\EditFakeStoreUsers::route('/{record}/edit'),
        ];
    }
}
