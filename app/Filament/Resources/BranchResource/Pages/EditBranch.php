<?php

namespace App\Filament\Resources\BranchResource\Pages;

use App\Filament\Resources\BranchResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBranch extends EditRecord
{
    protected static string $resource = BranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Eliminación deshabilitada para sucursales
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
            ->title('Sucursal actualizada exitosamente')
            ->body("La sucursal '{$this->getRecord()->name}' ha sido actualizada correctamente.")
            ->icon('heroicon-o-check-circle');
    }
}
