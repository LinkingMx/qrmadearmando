<?php

namespace App\Filament\Resources\GiftCardResource\Pages;

use App\Filament\Resources\GiftCardResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

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
            ->title('QR Empleado creado exitosamente')
            ->body("El QR Empleado '{$this->getRecord()->legacy_id}' ha sido creado correctamente.")
            ->icon('heroicon-o-check-circle');
    }
}
