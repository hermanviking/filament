<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Services\VismaOrderService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refreshFromVisma')
                ->label('Refresh from Visma')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => filled($this->record?->visma_sales_order_number))
                ->action(function (): void {
                    if (!$this->record?->visma_sales_order_number) {
                        return;
                    }

                    try {
                        $order = app(VismaOrderService::class)->syncOrderFromVisma($this->record->visma_sales_order_number);

                        $this->record = $order;
                        $this->fillForm();

                        Notification::make()
                            ->title('Order refreshed from Visma')
                            ->body('Sales order ' . $order->visma_sales_order_number . ' has been synchronised.')
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Failed to refresh order from Visma')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('sendToVisma')
                ->label('Send to Visma')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->action(function (): void {
                    if (!$this->record) {
                        return;
                    }

                    try {
                        $order = app(VismaOrderService::class)->pushOrderToVisma(
                            $this->record->fresh(['items.product', 'customer'])
                        );

                        $this->record = $order;
                        $this->fillForm();

                        Notification::make()
                            ->title('Order sent to Visma')
                            ->body('Sales order ' . $order->visma_sales_order_number . ' synced successfully.')
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Failed to send order to Visma')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
