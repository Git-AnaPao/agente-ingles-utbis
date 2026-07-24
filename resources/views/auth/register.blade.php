<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display font-bold text-2xl" style="color: var(--color-primary);">
            Crea tu cuenta 🌱
        </h1>
        <p class="mt-1 text-sm" style="color: var(--color-text-secondary); font-family:Inter,sans-serif;">
            Empieza tu camino hacia el inglés con UTBIS.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="name" :value="__('Nombre')" />
                <x-text-input id="name" type="text" name="name"
                              :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" />
            </div>
            <div>
                <x-input-label for="user_last_name" :value="__('Apellido')" />
                <x-text-input id="user_last_name" type="text" name="user_last_name"
                              :value="old('user_last_name')" required />
                <x-input-error :messages="$errors->get('user_last_name')" />
            </div>
        </div>

        <div>
            <x-input-label for="user_cel" :value="__('Celular')" />
            <x-text-input id="user_cel" type="text" name="user_cel"
                          :value="old('user_cel')" required placeholder="7xxxxxxx" />
            <x-input-error :messages="$errors->get('user_cel')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" type="email" name="email"
                          :value="old('email')" required autocomplete="username"
                          placeholder="t24xx-xxxx@utbispuebla.edu.mx" />
            <p class="mt-1 text-xs" style="color: var(--color-text-secondary);">Solo se aceptan correos @utbispuebla.edu.mx</p>
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Contraseña')" />
            <x-text-input id="password" type="password" name="password"
                          required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
            <x-text-input id="password_confirmation" type="password"
                          name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-primary-button class="w-full">
            {{ __('Registrarme') }}
        </x-primary-button>
    </form>

    {{-- Separador --}}
    <div class="flex items-center gap-3 my-6">
        <div class="flex-1 h-px bg-gray-200"></div>
        <span class="text-xs" style="color: var(--color-text-secondary); font-family:Inter,sans-serif;">o</span>
        <div class="flex-1 h-px bg-gray-200"></div>
    </div>

    {{-- Google --}}
    <a href="{{ route('auth.google') }}"
       class="w-full flex items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium transition hover:bg-gray-50"
       style="color: var(--color-text-secondary); font-family:Inter,sans-serif;">
        <svg class="h-5 w-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.28 1.48-1.13 2.73-2.4 3.58v2.98h3.88c2.27-2.09 3.54-5.17 3.54-8.8z"/>
            <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.92l-3.88-2.98c-1.08.72-2.45 1.15-4.05 1.15-3.11 0-5.75-2.1-6.69-4.92H1.3v3.09C3.26 21.3 7.31 24 12 24z"/>
            <path fill="#FBBC05" d="M5.31 14.33c-.24-.72-.38-1.49-.38-2.28s.14-1.56.38-2.28V6.68H1.3A11.97 11.97 0 0 0 0 12.05c0 1.94.46 3.77 1.3 5.37l4.01-3.09z"/>
            <path fill="#EA4335" d="M12 4.75c1.76 0 3.35.61 4.6 1.8l3.44-3.44C17.94 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.3 6.68l4.01 3.09C6.25 6.85 8.89 4.75 12 4.75z"/>
        </svg>
        {{ __('Continuar con Google') }}
    </a>

    <p class="mt-6 text-center text-sm" style="color: var(--color-text-secondary); font-family:Inter,sans-serif;">
        ¿Ya tienes cuenta?
        <a href="{{ route('login') }}"
           class="font-semibold transition hover:underline"
           style="color: var(--color-primary);">
            {{ __('Iniciar sesión') }}
        </a>
    </p>
</x-guest-layout>
