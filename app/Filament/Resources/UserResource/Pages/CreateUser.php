<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Mutar los datos del formulario antes de crear el registro.
     * Esto es necesario para manejar el hash de la contraseña.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validar que la contraseña esté presente al crear
        if (empty($data['password'])) {
            throw new \Exception('La contraseña es requerida para crear un usuario.');
        }

        // Hashear la password al crear usuario
        $data['password'] = Hash::make($data['password']);

        // Remover password_confirmation
        unset($data['password_confirmation']);

        return $data;
    }

    /**
     * Configurar redirección y notificación después de crear
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Usuario creado')
            ->body('El usuario ha sido registrado exitosamente en el sistema.')
            ->icon('heroicon-o-user-plus');
    }
}
