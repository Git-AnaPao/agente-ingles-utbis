<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    private const ALLOWED_DOMAIN = 'utbispuebla.edu.mx';

    /**
     * Redirect the user to Google's OAuth consent screen.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->with(['hd' => self::ALLOWED_DOMAIN])
            ->redirect();
    }

    /**
     * Handle the callback from Google after authentication.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'No se pudo autenticar con Google. Por favor, intente de nuevo.',
            ]);
        }

        $email = Str::lower($googleUser->getEmail());

        if (!Str::endsWith($email, '@'.self::ALLOWED_DOMAIN)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Solo se permite el acceso con correos institucionales @'.self::ALLOWED_DOMAIN.'.',
            ]);
        }

        $user = User::where('user_email', $email)
            ->orWhere('google_id', $googleUser->getId())
            ->first();

        if ($user) {
            if (!$user->google_id) {
                $user->forceFill(['google_id' => $googleUser->getId()])->save();
            }
        } else {
            $user = User::create([
                'user_email' => $email,
                'google_id' => $googleUser->getId(),
                'user_name' => $googleUser->user['given_name'] ?? $googleUser->getName(),
                'user_last_name' => $googleUser->user['family_name'] ?? '',
                'user_middle_name' => '',
                'user_status' => 'active',
            ]);

            $studentRole = Role::where('role_name', 'student')->first();
            $user->roles()->attach($studentRole->role_id);

            event(new Registered($user));
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
