<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Services\VismaOrderService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Throwable;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function afterCreate(): void
    {
        if (!$this->record) {
            return;
        }

        try {
            $order = app(VismaOrderService::class)->pushOrderToVisma(
                $this->record->fresh(['items.product', 'customer'])
            );

            $this->record = $order;

            Notification::make()
                ->title('Order sent to Visma')
                ->body('Sales order ' . $order->visma_sales_order_number . ' synced successfully.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Order created, but Visma sync failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
