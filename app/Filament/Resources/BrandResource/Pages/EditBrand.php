<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBrand extends EditRecord
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(fn () => $this->getRecord()->branches()->exists() || $this->getRecord()->giftCards()->exists()),
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
            ->title('Marca actualizada exitosamente')
            ->body("La marca '{$this->getRecord()->name}' ha sido actualizada correctamente.")
            ->icon('heroicon-o-check-circle');
    }
}
