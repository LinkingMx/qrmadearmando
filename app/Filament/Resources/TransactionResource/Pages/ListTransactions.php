<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Exports\TransactionsExport;
use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Exportar')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    Forms\Components\Select::make('gift_card_id')
                        ->label('Tarjeta de Regalo')
                        ->options(\App\Models\GiftCard::pluck('legacy_id', 'id'))
                        ->searchable()
                        ->placeholder('Todas'),
                    Forms\Components\DatePicker::make('date_from')
                        ->label('Desde')
                        ->maxDate(now()),
                    Forms\Components\DatePicker::make('date_to')
                        ->label('Hasta')
                        ->maxDate(now())
                        ->default(now()),
                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->options([
                            'credit' => 'Carga',
                            'debit' => 'Descuento',
                            'adjustment' => 'Ajuste Manual',
                        ])
                        ->placeholder('Todos'),
                    Forms\Components\Select::make('branch_id')
                        ->label('Sucursal')
                        ->options(\App\Models\Branch::pluck('name', 'id'))
                        ->searchable()
                        ->placeholder('Todas'),
                    Forms\Components\Select::make('admin_user_id')
                        ->label('Usuario Admin')
                        ->options(\App\Models\User::pluck('name', 'id'))
                        ->searchable()
                        ->placeholder('Todos'),
                ])
                ->action(function (array $data) {
                    $giftCard = null;
                    if (!empty($data['gift_card_id'])) {
                        $giftCard = \App\Models\GiftCard::find($data['gift_card_id']);
                    }

                    $filename = 'transacciones_' .
                        ($giftCard ? $giftCard->legacy_id . '_' : '') .
                        now()->format('Y-m-d') . '.xlsx';

                    return Excel::download(
                        new TransactionsExport($giftCard, $data),
                        $filename
                    );
                }),
            Actions\CreateAction::make(),
        ];
    }
}
