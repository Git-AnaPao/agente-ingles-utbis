<x-guest-layout>
    {{-- Estado de sesión --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    {{-- Encabezado — Plus Jakarta Sans, Verde Oscuro --}}
    <div class="mb-6">
        <h1 class="font-display font-bold text-2xl" style="color:#27594B;">
            ¡Bienvenido de vuelta! 👋
        </h1>
        <p class="mt-1 text-sm" style="color:#6B7280; font-family:Inter,sans-serif;">
            Ingresa tus datos para continuar aprendiendo.
        </p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" type="email" name="email"
                          :value="old('email')"
                          required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        {{-- Contraseña --}}
        <div>
            <x-input-label for="password" :value="__('Contraseña')" />
            <x-text-input id="password" type="password" name="password"
                          required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        {{-- Recuérdame + Olvidé contraseña --}}
        <div class="flex items-center justify-between gap-4 text-sm">
            <label class="flex items-center gap-2 cursor-pointer" style="color:#6B7280; font-family:Inter,sans-serif;">
                <input id="remember_me" type="checkbox" name="remember"
                       class="h-4 w-4 rounded border-gray-300 focus:ring-2"
                       style="color:#F28729; accent-color:#F28729;">
                {{ __('Recordarme') }}
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   class="font-medium transition hover:underline"
                   style="color:#F28729; font-family:Inter,sans-serif;">
                    {{ __('Olvidé mi contraseña') }}
                </a>
            @endif
        </div>

        {{-- Botón principal — Naranja Acción --}}
        <x-primary-button class="w-full">
            {{ __('Iniciar sesión') }}
        </x-primary-button>
    </form>

    {{-- Registro --}}
    <p class="mt-6 text-center text-sm" style="color:#6B7280; font-family:Inter,sans-serif;">
        ¿No tienes cuenta?
        <a href="{{ route('register') }}"
           class="font-semibold transition hover:underline"
           style="color:#27594B;">
            {{ __('Crear cuenta') }}
        </a>
    </p>
</x-guest-layout>
