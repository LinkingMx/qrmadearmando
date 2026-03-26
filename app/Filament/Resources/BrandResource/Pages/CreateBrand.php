<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Marca creada exitosamente')
            ->body("La marca '{$this->getRecord()->name}' ha sido creada correctamente.")
            ->icon('heroicon-o-check-circle');
    }
}
