<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresBranch
{
    /**
     * Handle an incoming request.
     * Ensures the user has a branch assigned and the BranchTerminal role.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Verify user has a branch assigned
        if ($user->branch_id === null) {
            return redirect()->route('dashboard')
                ->with('error', 'Debes tener una sucursal asignada para acceder al Scanner');
        }

        // Verify user has the BranchTerminal role
        if (! $user->hasRole('BranchTerminal')) {
            return redirect()->route('dashboard')
                ->with('error', 'Solo usuarios tipo Terminal de Sucursal pueden acceder al Scanner');
        }

        return $next($request);
    }
}
