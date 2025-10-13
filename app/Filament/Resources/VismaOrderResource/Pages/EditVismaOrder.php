<?php

// App/Filament/Resources/VismaOrderResource/Pages/EditVismaOrder.php

namespace App\Filament\Resources\VismaOrderResource\Pages;

use App\Filament\Resources\VismaOrderResource;
use App\Services\VismaOrderService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditVismaOrder extends EditRecord
{
    protected static string $resource = VismaOrderResource::class;

    protected function getHeaderActions(): array
{
    return [
        Actions\Action::make('pushToVisma')
            ->label('Push to Visma')
            ->icon('heroicon-m-cloud-arrow-up')
            ->requiresConfirmation()
            ->action(function () {
                try {
                    app(\App\Services\VismaOrderService::class)->pushVismaOrder($this->record);
                    Notification::make()->title('Order pushed to Visma.')->success()->send();
                    $this->fillForm(); // refresh form with latest data
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Failed to push order')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            }),
        Actions\DeleteAction::make(),
    ];
}
}
