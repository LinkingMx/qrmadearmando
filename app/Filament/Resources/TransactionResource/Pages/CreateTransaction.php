<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Models\GiftCard;
use App\Services\TransactionService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $giftCard = GiftCard::findOrFail($data['gift_card_id']);
        $transactionService = app(TransactionService::class);

        try {
            $transaction = match($data['type']) {
                'credit' => $transactionService->credit(
                    $giftCard,
                    $data['amount'],
                    $data['description'] ?? null,
                    auth()->id(),
                    $data['branch_id'] ?? null
                ),
                'debit' => $transactionService->debit(
                    $giftCard,
                    $data['amount'],
                    $data['description'] ?? null,
                    auth()->id(),
                    $data['branch_id'] ?? null
                ),
                'adjustment' => $transactionService->adjustment(
                    $giftCard,
                    $data['amount'],
                    $data['description'] ?? null,
                    auth()->id(),
                    $data['branch_id'] ?? null
                ),
            };

            // Redirigir y detener la creación normal ya que el servicio ya creó la transacción
            $this->halt();
            $this->redirect(static::getResource()::getUrl('index'));

            Notification::make()
                ->success()
                ->title('Transacción creada exitosamente')
                ->body("Nuevo saldo de {$giftCard->legacy_id}: \${$giftCard->fresh()->balance}")
                ->send();

            return [];
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error al crear transacción')
                ->body($e->getMessage())
                ->send();

            $this->halt();
            return [];
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null; // Ya enviamos la notificación personalizada
    }
}
