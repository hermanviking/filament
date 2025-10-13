<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importFromVisma')
                ->label('Import from Visma')
                ->icon('heroicon-m-arrow-down-tray')
                ->modalHeading('Import customers from Visma')
                ->modalSubmitActionLabel('Run import')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'Active' => 'Active',
                            'Inactive' => 'Inactive',
                        ])
                        ->default('Active'),
                    Forms\Components\TextInput::make('page_size')
                        ->label('Page size')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(500)
                        ->default(500),
                ])
                ->action(function (array $data) {
                    $status   = $data['status'] ?? 'Active';
                    $pageSize = (int)($data['page_size'] ?? 500);

                    $exit = Artisan::call('app:import-customers', [
                        '--status'     => $status,
                        '--page-size'  => $pageSize,
                    ]);
                    $output = trim(Artisan::output());

                    if ($exit === 0) {
                        Notification::make()
                            ->title('Import completed')
                            ->body($output !== '' ? mb_strimwidth($output, 0, 1000, '…') : 'Customers imported successfully.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Import failed')
                            ->body($output !== '' ? mb_strimwidth($output, 0, 1000, '…') : 'See application logs for details.')
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation(),
        ];
    }
}
