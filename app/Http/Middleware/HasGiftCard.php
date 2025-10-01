<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HasGiftCard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            // Check if user has a gift card assigned
            $hasGiftCard = \App\Models\GiftCard::where('user_id', auth()->id())->exists();

            if (!$hasGiftCard) {
                return redirect()->route('dashboard')
                    ->with('error', 'No tienes una tarjeta QR asignada. Contacta al administrador.');
            }
        }

        return $next($request);
    }
}
