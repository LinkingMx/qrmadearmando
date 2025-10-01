<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Exports\BranchClosureExport;
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
            Actions\Action::make('branch_closure')
                ->label('Corte de Lote')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->form([
                    Forms\Components\Select::make('branch_id')
                        ->label('Sucursal')
                        ->options(\App\Models\Branch::pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->helperText('Seleccione la sucursal para generar el corte de lote'),
                    Forms\Components\DatePicker::make('date')
                        ->label('Fecha')
                        ->default(now())
                        ->maxDate(now())
                        ->required()
                        ->native(false),
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TimePicker::make('time_from')
                                ->label('Hora inicio')
                                ->default('00:00')
                                ->seconds(false)
                                ->required(),
                            Forms\Components\TimePicker::make('time_to')
                                ->label('Hora fin')
                                ->default('23:59')
                                ->seconds(false)
                                ->required(),
                        ]),
                    Forms\Components\Select::make('type')
                        ->label('Tipo de Transacción')
                        ->options([
                            'credit' => 'Carga',
                            'debit' => 'Descuento',
                            'adjustment' => 'Ajuste Manual',
                        ])
                        ->placeholder('Todos')
                        ->native(false),
                    Forms\Components\Select::make('admin_user_id')
                        ->label('Usuario Admin')
                        ->options(\App\Models\User::pluck('name', 'id'))
                        ->searchable()
                        ->placeholder('Todos'),
                ])
                ->action(function (array $data) {
                    $branch = \App\Models\Branch::find($data['branch_id']);

                    if (!$branch) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body('Sucursal no encontrada')
                            ->send();
                        return;
                    }

                    // Format branch name for filename (remove spaces and special chars)
                    $branchSlug = str_replace([' ', ',', '.'], '_', $branch->name);
                    $date = \Carbon\Carbon::parse($data['date'])->format('Y-m-d');

                    $filename = "corte_lote_{$branchSlug}_{$date}.xlsx";

                    return Excel::download(
                        new BranchClosureExport($branch, $data),
                        $filename
                    );
                }),
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
