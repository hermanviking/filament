<?php

namespace App\Filament\Resources\VismaOrderResource\Pages;

use App\Filament\Resources\VismaOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListVismaOrders extends ListRecords
{
    protected static string $resource = VismaOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
 Actions\Action::make('importFromVisma')
                ->label('Import from Visma')
                ->action(function () {
                    $count = app(\App\Services\VismaOrderService::class)
                        ->importAllOrders(top: 100, maxPages: 20);

                    Notification::make()
                        ->title('Import complete')
                        ->body("Imported {$count} orders (including lines).")
                        ->success()
                        ->send();

                    // refresh the table after import
                    $this->dispatch('refresh');
                })
                ->requiresConfirmation(),   ];
    }
}
