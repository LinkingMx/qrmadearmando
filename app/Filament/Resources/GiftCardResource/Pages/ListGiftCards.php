<?php

namespace App\Filament\Resources\GiftCardResource\Pages;

use App\Exports\BalanceReportExport;
use App\Exports\GiftCardsExport;
use App\Filament\Resources\GiftCardResource;
use App\Imports\BalanceImport;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListGiftCards extends ListRecords
{
    protected static string $resource = GiftCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_balance_template')
                ->label('Descargar Plantilla')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->outlined()
                ->url(route('download.balance-template'))
                ->openUrlInNewTab(),
            Actions\Action::make('import_balances')
                ->label('Carga Masiva de Saldos')
                ->icon('heroicon-o-banknotes')
                ->color('primary')
                ->form([
                    Forms\Components\Placeholder::make('instructions')
                        ->label('')
                        ->content('Sube un archivo Excel con los saldos a cargar o descontar. Para CARGAR use números positivos (500 o +500). Para DESCONTAR use números negativos (-200).')
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('excel')
                        ->label('Archivo Excel')
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required()
                        ->helperText('Formato: uuid, monto (+/-), descripcion, sucursal')
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('allow_multiple')
                        ->label('Permitir múltiples cargas al mismo QR')
                        ->helperText('Si está activado, permite procesar varias filas para el mismo UUID')
                        ->default(true),
                    Forms\Components\Placeholder::make('note')
                        ->label('')
                        ->content('⚠️ Importante: Para cargar use números positivos (500 o +500). Para descontar use números negativos (-200). Los descuentos requieren especificar la sucursal.')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    try {
                        // Import balances from Excel
                        $import = new BalanceImport(auth()->id(), $data['allow_multiple']);
                        Excel::import($import, storage_path('app/public/'.$data['excel']));

                        // Get statistics
                        $stats = $import->getStats();
                        $processed = $import->getProcessed();
                        $errors = $import->getErrors();

                        // Generate detailed report
                        $reportFilename = 'reporte_carga_saldos_'.now()->format('Y-m-d_His').'.xlsx';
                        $reportPath = storage_path('app/public/temp/'.$reportFilename);

                        if (! file_exists(dirname($reportPath))) {
                            mkdir(dirname($reportPath), 0755, true);
                        }

                        Excel::store(
                            new BalanceReportExport($stats, $processed, $errors),
                            'temp/'.$reportFilename,
                            'public'
                        );

                        // Build notification message
                        $message = "✅ Procesados: {$stats['processed']}\n";
                        $message .= '💰 Total Cargado: $'.number_format($stats['total_credited'], 2)."\n";
                        $message .= '💸 Total Descontado: $'.number_format($stats['total_debited'], 2)."\n";
                        $message .= '📊 Cambio Neto: $'.number_format($stats['net_change'], 2);

                        if ($stats['errors'] > 0) {
                            $message .= "\n❌ Errores: {$stats['errors']}";
                        }

                        // Show notification
                        if ($stats['errors'] > 0) {
                            Notification::make()
                                ->warning()
                                ->title('Importación completada con errores')
                                ->body($message)
                                ->persistent()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('download_report')
                                        ->button()
                                        ->label('Descargar Reporte Completo')
                                        ->url(route('download.balance-report', ['file' => $reportFilename]))
                                        ->openUrlInNewTab(),
                                ])
                                ->send();
                        } else {
                            Notification::make()
                                ->success()
                                ->title('¡Carga masiva exitosa!')
                                ->body($message)
                                ->persistent()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('download_report')
                                        ->button()
                                        ->label('Descargar Reporte Completo')
                                        ->url(route('download.balance-report', ['file' => $reportFilename]))
                                        ->openUrlInNewTab(),
                                ])
                                ->send();
                        }

                        // Refresh the page to show updated balances
                        $this->redirect(static::getUrl());

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error en la importación')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            Actions\Action::make('export_qr')
                ->label('Exportar QR a Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'active' => 'Activos',
                            'inactive' => 'Inactivos',
                        ])
                        ->placeholder('Todos')
                        ->native(false),
                    Forms\Components\Select::make('has_balance')
                        ->label('Saldo')
                        ->options([
                            'yes' => 'Con saldo',
                            'no' => 'Sin saldo',
                        ])
                        ->placeholder('Todos')
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $filename = 'qr_empleados_'.now()->format('Y-m-d_His').'.xlsx';

                    return Excel::download(
                        new GiftCardsExport($data),
                        $filename
                    );
                }),
            Actions\CreateAction::make()
                ->color('primary'),
        ];
    }
}
