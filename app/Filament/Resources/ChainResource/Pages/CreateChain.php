<?php

namespace App\Filament\Resources\ChainResource\Pages;

use App\Filament\Resources\ChainResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateChain extends CreateRecord
{
    protected static string $resource = ChainResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Cadena creada exitosamente')
            ->body("La cadena '{$this->getRecord()->name}' ha sido creada correctamente.")
            ->icon('heroicon-o-check-circle');
    }
}
