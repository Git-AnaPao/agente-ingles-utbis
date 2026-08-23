<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! collect($roles)->contains(fn (string $role): bool => $user->hasRole($role))) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
