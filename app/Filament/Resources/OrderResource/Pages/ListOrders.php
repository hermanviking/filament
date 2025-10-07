<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Services\VismaOrderService;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importFromVisma')
                ->label('Import from Visma')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    TextInput::make('order_number')
                        ->label('Visma Order Number')
                        ->required()
                        ->maxLength(100),
                ])
                ->action(function (array $data): void {
                    try {
                        $order = app(VismaOrderService::class)->syncOrderFromVisma($data['order_number']);

                        Notification::make()
                            ->title('Order imported from Visma')
                            ->body('Sales order ' . $order->visma_sales_order_number . ' has been synchronised.')
                            ->success()
                            ->send();

                        $this->refreshTable();
                    } catch (\Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Failed to import order from Visma')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\CreateAction::make(),
        ];
    }
}
