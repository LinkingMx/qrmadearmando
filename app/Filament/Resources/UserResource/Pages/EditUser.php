<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }


    /**
     * Mutar los datos del formulario antes de guardar.
     * Esto es necesario para manejar la actualización de la contraseña.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Si password tiene valor, hashearlo
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            // Si está vacío, eliminarlo del array para no actualizarlo
            unset($data['password']);
        }

        // Siempre remover password_confirmation (no se guarda en BD)
        unset($data['password_confirmation']);

        return $data;
    }

    /**
     * Configurar redirección y notificación después de editar
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Usuario actualizado')
            ->body('Los datos del usuario han sido modificados correctamente.')
            ->icon('heroicon-o-pencil-square');
    }
}
