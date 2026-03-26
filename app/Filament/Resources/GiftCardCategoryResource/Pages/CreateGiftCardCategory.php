<?php

namespace App\Filament\Resources\GiftCardCategoryResource\Pages;

use App\Filament\Resources\GiftCardCategoryResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateGiftCardCategory extends CreateRecord
{
    protected static string $resource = GiftCardCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Categoría creada exitosamente')
            ->body("La categoría '{$this->getRecord()->name}' con prefijo '{$this->getRecord()->prefix}' ha sido creada correctamente.")
            ->icon('heroicon-o-check-circle');
    }
}
