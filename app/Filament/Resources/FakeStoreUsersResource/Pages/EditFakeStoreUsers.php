<?php

namespace App\Filament\Resources\FakeStoreUsersResource\Pages;

use App\Filament\Resources\FakeStoreUsersResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFakeStoreUsers extends EditRecord
{
    protected static string $resource = FakeStoreUsersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
