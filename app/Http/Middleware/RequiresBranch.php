<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresBranch
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->branch_id === null) {
            return redirect()->route('dashboard')
                ->with('error', 'Debes tener una sucursal asignada para acceder al Scanner');
        }

        return $next($request);
    }
}
