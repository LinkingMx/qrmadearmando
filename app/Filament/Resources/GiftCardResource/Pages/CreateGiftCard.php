<?php

namespace App\Filament\Resources\GiftCardResource\Pages;

use App\Filament\Resources\GiftCardResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateGiftCard extends CreateRecord
{
    protected static string $resource = GiftCardResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('QR Code creado exitosamente')
            ->body("El QR Code '{$this->getRecord()->legacy_id}' ha sido creado correctamente.")
            ->icon('heroicon-o-check-circle');
    }
}
