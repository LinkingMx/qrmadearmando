<?php

namespace App\Filament\Resources\GiftCardResource\Pages;

use App\Filament\Resources\GiftCardResource;
use App\Services\TransactionService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditGiftCard extends EditRecord
{
    protected static string $resource = GiftCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('credit_balance')
                ->label('Cargar Saldo')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('current_balance')
                        ->label('Saldo Actual')
                        ->disabled()
                        ->prefix('$')
                        ->default(fn () => $this->record->balance ?? 0),
                    Forms\Components\TextInput::make('amount')
                        ->label('Monto a Cargar')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0.01)
                        ->step(0.01),
                    Forms\Components\Textarea::make('description')
                        ->label('Descripción (opcional)')
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    try {
                        $transactionService = app(TransactionService::class);
                        $transactionService->credit(
                            $this->record,
                            $data['amount'],
                            $data['description'] ?? null,
                            auth()->id()
                        );

                        Notification::make()
                            ->success()
                            ->title('Saldo cargado exitosamente')
                            ->body("Se cargaron \${$data['amount']} a la tarjeta {$this->record->legacy_id}. Nuevo saldo: \${$this->record->fresh()->balance}")
                            ->send();

                        $this->refreshFormData(['balance']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error al cargar saldo')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            Actions\Action::make('debit_balance')
                ->label('Descontar Saldo')
                ->icon('heroicon-o-minus-circle')
                ->color('danger')
                ->form([
                    Forms\Components\TextInput::make('current_balance')
                        ->label('Saldo Actual')
                        ->disabled()
                        ->prefix('$')
                        ->default(fn () => $this->record->balance ?? 0),
                    Forms\Components\Select::make('branch_id')
                        ->label('Sucursal')
                        ->options(\App\Models\Branch::pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->helperText('La sucursal es requerida para descuentos'),
                    Forms\Components\TextInput::make('amount')
                        ->label('Monto a Descontar')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0.01)
                        ->step(0.01),
                    Forms\Components\Textarea::make('description')
                        ->label('Descripción (opcional)')
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    try {
                        $transactionService = app(TransactionService::class);
                        $transactionService->debit(
                            $this->record,
                            $data['amount'],
                            $data['description'] ?? null,
                            auth()->id(),
                            $data['branch_id']
                        );

                        Notification::make()
                            ->success()
                            ->title('Saldo descontado exitosamente')
                            ->body("Se descontaron \${$data['amount']} de la tarjeta {$this->record->legacy_id}. Nuevo saldo: \${$this->record->fresh()->balance}")
                            ->send();

                        $this->refreshFormData(['balance']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error al descontar saldo')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            Actions\Action::make('adjustment_balance')
                ->label('Ajuste Manual')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('warning')
                ->form([
                    Forms\Components\TextInput::make('current_balance')
                        ->label('Saldo Actual')
                        ->disabled()
                        ->prefix('$')
                        ->default(fn () => $this->record->balance ?? 0),
                    Forms\Components\TextInput::make('amount')
                        ->label('Monto del Ajuste')
                        ->helperText('Use números positivos para aumentar y negativos para disminuir')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->step(0.01)
                        ->reactive(),
                    Forms\Components\Select::make('branch_id')
                        ->label('Sucursal')
                        ->options(\App\Models\Branch::pluck('name', 'id'))
                        ->searchable()
                        ->required(fn (Forms\Get $get) => $get('amount') < 0)
                        ->visible(fn (Forms\Get $get) => $get('amount') < 0)
                        ->helperText('La sucursal es requerida solo cuando se reduce el saldo'),
                    Forms\Components\Textarea::make('description')
                        ->label('Descripción (requerida para ajustes)')
                        ->required()
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    try {
                        $transactionService = app(TransactionService::class);
                        $transactionService->adjustment(
                            $this->record,
                            $data['amount'],
                            $data['description'],
                            auth()->id(),
                            $data['branch_id'] ?? null
                        );

                        $actionType = $data['amount'] > 0 ? 'aumentó' : 'disminuyó';
                        $absAmount = abs($data['amount']);

                        Notification::make()
                            ->success()
                            ->title('Ajuste realizado exitosamente')
                            ->body("Se {$actionType} \${$absAmount} en la tarjeta {$this->record->legacy_id}. Nuevo saldo: \${$this->record->fresh()->balance}")
                            ->send();

                        $this->refreshFormData(['balance']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error al realizar ajuste')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('QR Empleado actualizado exitosamente')
            ->body("El QR Empleado '{$this->getRecord()->legacy_id}' ha sido actualizado correctamente.")
            ->icon('heroicon-o-check-circle');
    }
}
