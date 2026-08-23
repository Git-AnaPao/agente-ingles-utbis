<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->user_status === 'active') {
            return $next($request);
        }

        try {
            Auth::guard()->logout();
        } catch (Throwable) {
            // The account status still blocks the request if token invalidation fails.
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Cuenta no disponible.'], 403);
        }

        return redirect()->route('login')->withErrors([
            'email' => 'La cuenta no esta disponible.',
        ]);
    }
}
