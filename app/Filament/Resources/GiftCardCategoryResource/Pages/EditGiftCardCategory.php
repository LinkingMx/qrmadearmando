<?php

namespace App\Filament\Resources\GiftCardCategoryResource\Pages;

use App\Filament\Resources\GiftCardCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditGiftCardCategory extends EditRecord
{
    protected static string $resource = GiftCardCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(fn () => $this->getRecord()->giftCards()->exists()),
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
            ->title('Categoría actualizada exitosamente')
            ->body("La categoría '{$this->getRecord()->name}' ha sido actualizada correctamente.")
            ->icon('heroicon-o-check-circle');
    }
}
