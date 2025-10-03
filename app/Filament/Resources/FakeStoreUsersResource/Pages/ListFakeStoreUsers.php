<?php

namespace App\Filament\Resources\FakeStoreUsersResource\Pages;

use App\Filament\Resources\FakeStoreUsersResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFakeStoreUsers extends ListRecords
{
    protected static string $resource = FakeStoreUsersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
