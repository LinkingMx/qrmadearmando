<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && !Auth::user()->is_active) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Detectar si es una ruta de Filament
            $isFilamentRoute = str_starts_with($request->path(), 'admin');

            if ($isFilamentRoute) {
                return redirect()->to('/admin/login')->with('status', 'Tu cuenta ha sido desactivada. Contacta al administrador.');
            }

            return redirect()->route('login')->with('status', 'Tu cuenta ha sido desactivada. Contacta al administrador.');
        }

        return $next($request);
    }
}
