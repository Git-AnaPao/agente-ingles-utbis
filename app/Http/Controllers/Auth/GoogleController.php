<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleController extends Controller
{
    private const ALLOWED_DOMAIN = 'utbispuebla.edu.mx';

    private const ACCOUNT_CONFLICT = 'oauth_account_conflict';

    private const ACCOUNT_INACTIVE = 'oauth_account_inactive';

    /**
     * Redirect the user to Google's OAuth consent screen.
     */
    public function redirect(): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return $this->loginError('El acceso con Google no está configurado en este momento.');
        }

        return Socialite::driver('google')
            ->with([
                'hd' => self::ALLOWED_DOMAIN,
                'prompt' => 'select_account',
            ])
            ->redirect();
    }

    /**
     * Handle the callback from Google after authentication.
     */
    public function callback(): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return $this->loginError('El acceso con Google no está configurado en este momento.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return $this->loginError('No se pudo autenticar con Google. Por favor, intente de nuevo.');
        }

        $email = Str::lower(trim((string) $googleUser->getEmail()));
        $googleId = trim((string) $googleUser->getId());
        $rawUser = is_array($googleUser->user ?? null) ? $googleUser->user : [];
        $verifiedEmail = filter_var(
            $rawUser['email_verified'] ?? $rawUser['verified_email'] ?? false,
            FILTER_VALIDATE_BOOL,
        );

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || strlen($email) > 255
            || $googleId === ''
            || strlen($googleId) > 255
            || ! $verifiedEmail) {
            return $this->loginError('Google no proporciono una identidad institucional verificada.');
        }

        if (Str::afterLast($email, '@') !== self::ALLOWED_DOMAIN) {
            return $this->loginError(
                'Solo se permite el acceso con correos institucionales @'.self::ALLOWED_DOMAIN.'.'
            );
        }

        try {
            [$user, $created] = DB::transaction(function () use ($email, $googleId, $googleUser): array {
                $userByGoogleId = User::where('google_id', $googleId)->lockForUpdate()->first();
                $userByEmail = User::where('user_email', $email)->lockForUpdate()->first();

                if ($userByGoogleId && $userByEmail && ! $userByGoogleId->is($userByEmail)) {
                    throw new \RuntimeException(self::ACCOUNT_CONFLICT);
                }

                if ($userByGoogleId && ! hash_equals(Str::lower($userByGoogleId->user_email), $email)) {
                    throw new \RuntimeException(self::ACCOUNT_CONFLICT);
                }

                $user = $userByGoogleId ?? $userByEmail;

                if ($user && $user->user_status !== 'active') {
                    throw new \RuntimeException(self::ACCOUNT_INACTIVE);
                }

                if ($user && $user->google_id !== null && ! hash_equals((string) $user->google_id, $googleId)) {
                    throw new \RuntimeException(self::ACCOUNT_CONFLICT);
                }

                if (! $user) {
                    $rawUser = is_array($googleUser->user ?? null) ? $googleUser->user : [];
                    $displayName = trim((string) $googleUser->getName());

                    $user = User::create([
                        'user_email' => $email,
                        'google_id' => $googleId,
                        'user_name' => Str::limit((string) ($rawUser['given_name'] ?? $displayName), 255, ''),
                        'user_last_name' => Str::limit((string) ($rawUser['family_name'] ?? ''), 255, ''),
                        'user_middle_name' => '',
                        'user_status' => 'active',
                        'email_verified_at' => now(),
                    ]);

                    $studentRole = Role::firstOrCreate(
                        ['role_name' => 'student'],
                        ['role_description' => 'Estudiante']
                    );
                    $user->roles()->attach($studentRole->role_id);

                    return [$user, true];
                }

                $identityUpdates = [];

                if ($user->google_id === null) {
                    $identityUpdates['google_id'] = $googleId;
                }

                if (! $user->hasVerifiedEmail()) {
                    $identityUpdates['email_verified_at'] = now();
                }

                if ($identityUpdates !== []) {
                    $user->forceFill($identityUpdates)->save();
                }

                if ($user->roles()->doesntExist()) {
                    $studentRole = Role::firstOrCreate(
                        ['role_name' => 'student'],
                        ['role_description' => 'Estudiante']
                    );
                    $user->roles()->attach($studentRole->role_id);
                }

                return [$user, false];
            }, 3);
        } catch (Throwable $exception) {
            if ($exception->getMessage() === self::ACCOUNT_INACTIVE) {
                return $this->loginError('La cuenta no esta disponible.');
            }

            if ($exception->getMessage() === self::ACCOUNT_CONFLICT) {
                return $this->loginError('No se pudo vincular la cuenta de Google de forma segura.');
            }

            return $this->loginError('No se pudo completar el acceso con Google. Por favor, intente de nuevo.');
        }

        if ($created) {
            event(new Registered($user));
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function loginError(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors([
            'email' => $message,
        ]);
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
