<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display font-bold text-2xl" style="color:#27594B;">
            Crea tu cuenta 🌱
        </h1>
        <p class="mt-1 text-sm" style="color:#6B7280; font-family:Inter,sans-serif;">
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
            <p class="mt-1 text-xs" style="color:#9CA3AF;">Solo se aceptan correos @utbispuebla.edu.mx</p>
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

    <p class="mt-6 text-center text-sm" style="color:#6B7280; font-family:Inter,sans-serif;">
        ¿Ya tienes cuenta?
        <a href="{{ route('login') }}"
           class="font-semibold transition hover:underline"
           style="color:#27594B;">
            {{ __('Iniciar sesión') }}
        </a>
    </p>
</x-guest-layout>
