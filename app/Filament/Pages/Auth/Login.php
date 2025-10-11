<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $data = $this->form->getState();

            $user = User::where('email', $data['email'])->first();

            if (!$user || !Hash::check($data['password'], $user->password)) {
                $this->throwFailureValidationException();
            }

            // Validar si el usuario está activo
            if (!$user->is_active) {
                Notification::make()
                    ->title('Cuenta desactivada')
                    ->body('Tu cuenta ha sido desactivada. Contacta al administrador.')
                    ->danger()
                    ->send();

                $this->throwFailureValidationException();
            }

            Filament::auth()->login($user, $data['remember'] ?? false);

            session()->regenerate();

            return app(LoginResponse::class);
        } catch (ValidationException $exception) {
            throw $exception;
        }
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }
}
