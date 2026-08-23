<x-guest-layout title="Crear Cuenta">
    @php
        $googleAuthEnabled = filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    @endphp

    <div class="mb-7 text-center">
        <h1 class="font-display font-black text-2xl sm:text-3xl tracking-tight" style="color: var(--color-text);">
            Crea tu Cuenta UTBIS
        </h1>
        <p class="mt-1.5 text-xs sm:text-sm text-slate-400">
            Comienza tu entrenamiento de inglés adaptativo impulsado por IA.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <x-input-label for="name" value="Nombre(s)" />
                <x-text-input id="name" type="text" name="name"
                              :value="old('name')" :invalid="$errors->has('name')"
                              aria-describedby="name-error"
                              class="mt-1 block w-full rounded-2xl"
                              required autofocus autocomplete="given-name" maxlength="255" />
                <x-input-error id="name-error" :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="user_last_name" value="Primer Apellido" />
                <x-text-input id="user_last_name" type="text" name="user_last_name"
                              :value="old('user_last_name')" :invalid="$errors->has('user_last_name')"
                              aria-describedby="user-last-name-error"
                              class="mt-1 block w-full rounded-2xl"
                              required autocomplete="family-name" maxlength="255" />
                <x-input-error id="user-last-name-error" :messages="$errors->get('user_last_name')" class="mt-1" />
            </div>
        </div>

        <div>
            <x-input-label for="email" value="Correo Electrónico Institucional" />
            <x-text-input id="email" type="email" name="email"
                          :value="old('email')" :invalid="$errors->has('email')"
                          aria-describedby="register-email-help register-email-error"
                          class="mt-1 block w-full rounded-2xl"
                          required autocomplete="username" maxlength="255"
                          placeholder="t24xx-xxxx@utbispuebla.edu.mx" />
            <p id="register-email-help" class="form-help text-[11px] text-slate-400 mt-1">Exclusivo correos @utbispuebla.edu.mx</p>
            <x-input-error id="register-email-error" :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="user_cel" value="Teléfono Celular (opcional)" />
            <x-text-input id="user_cel" type="tel" name="user_cel"
                          :value="old('user_cel')" :invalid="$errors->has('user_cel')"
                          aria-describedby="user-cel-error"
                          class="mt-1 block w-full rounded-2xl"
                          required inputmode="numeric" autocomplete="tel" maxlength="12" placeholder="7xxxxxxx" />
            <x-input-error id="user-cel-error" :messages="$errors->get('user_cel')" class="mt-1" />
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <x-input-label for="password" value="Contraseña" />
                <x-text-input id="password" type="password" name="password"
                              :invalid="$errors->has('password')" aria-describedby="register-password-help register-password-error"
                              class="mt-1 block w-full rounded-2xl"
                              required autocomplete="new-password" />
                <p id="register-password-help" class="form-help text-[11px] text-slate-400 mt-1">Mínimo 8 caracteres.</p>
                <x-input-error id="register-password-error" :messages="$errors->get('password')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="Confirmar Contraseña" />
                <x-text-input id="password_confirmation" type="password"
                              name="password_confirmation" :invalid="$errors->has('password_confirmation')"
                              aria-describedby="register-password-confirmation-error"
                              class="mt-1 block w-full rounded-2xl"
                              required autocomplete="new-password" />
                <x-input-error id="register-password-confirmation-error" :messages="$errors->get('password_confirmation')" class="mt-1" />
            </div>
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full btn-lumina btn-3d-green py-3.5 rounded-2xl font-bold text-sm shadow-md" data-loading-text="Creando cuenta...">
                Completar Registro
            </button>
        </div>
    </form>

    @if ($googleAuthEnabled)
        <div class="flex items-center gap-3 my-6">
            <div class="flex-1 h-px bg-slate-800"></div>
            <span class="text-xs font-mono text-slate-500 uppercase">o con Google</span>
            <div class="flex-1 h-px bg-slate-800"></div>
        </div>

        <a href="{{ route('auth.google') }}"
           class="google-auth-button w-full flex items-center justify-center gap-3 py-3 px-4 rounded-2xl border transition duration-200 text-xs font-bold shadow-sm hover:scale-[1.01]"
           style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text);">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.28 1.48-1.13 2.73-2.4 3.58v2.98h3.88c2.27-2.09 3.54-5.17 3.54-8.8z"/>
                <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.92l-3.88-2.98c-1.08.72-2.45 1.15-4.05 1.15-3.11 0-5.75-2.1-6.69-4.92H1.3v3.09C3.26 21.3 7.31 24 12 24z"/>
                <path fill="#FBBC05" d="M5.31 14.33c-.24-.72-.38-1.49-.38-2.28s.14-1.56.38-2.28V6.68H1.3A11.97 11.97 0 0 0 0 12.05c0 1.94.46 3.77 1.3 5.37l4.01-3.09z"/>
                <path fill="#EA4335" d="M12 4.75c1.76 0 3.35.61 4.6 1.8l3.44-3.44C17.94 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.3 6.68l4.01 3.09C6.25 6.85 8.89 4.75 12 4.75z"/>
            </svg>
            <span>Registrarme con Google Institucional</span>
        </a>
    @endif

    <p class="mt-6 text-center text-xs text-slate-400">
        ¿Ya tienes una cuenta registrada?
        <a href="{{ route('login') }}" class="font-bold text-emerald-500 hover:underline">
            Iniciar sesión
        </a>
    </p>
</x-guest-layout>
