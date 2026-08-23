<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('throttle:3,10', only: ['store'])];
    }

    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::where('user_email', $request->email)->first();

        if ($user && $user->user_status === 'active' && filled($user->getAuthPassword())) {
            Password::sendResetLink(['user_email' => $user->user_email]);
        }

        return back()
            ->withInput($request->only('email'))
            ->with('status', __('passwords.sent_if_exists'));
    }
}
