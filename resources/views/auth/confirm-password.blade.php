<x-guest-layout title="Confirmar contraseña">
    {{-- Encabezado --}}
    <div class="mb-6">
        <h1 class="font-display font-bold text-2xl" style="color: var(--color-primary);">
            Confirma tu contraseña
        </h1>
        <p class="mt-1 text-sm" style="color: var(--color-text-secondary); font-family:Inter,sans-serif;">
            Esta es un área segura. Confirma la contraseña local de tu cuenta antes de continuar.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Contraseña')" />
            <x-text-input id="password" type="password" name="password"
                           :invalid="$errors->has('password')"
                           aria-describedby="password-error"
                           required autocomplete="current-password" />
            <x-input-error id="password-error" :messages="$errors->get('password')" />
        </div>

        <x-primary-button class="w-full" data-loading-text="Confirmando...">
            {{ __('Confirmar') }}
        </x-primary-button>
    </form>

    <p class="mt-4 text-sm leading-relaxed" style="color: var(--color-text-secondary);">
        Este paso solo aplica a cuentas con contraseña local. Si accedes únicamente con Google, administra tus credenciales desde Google.
    </p>

    <p class="mt-5 text-center text-sm">
        <a href="{{ route('profile.edit') }}" class="font-semibold hover:underline" style="color: var(--color-primary);">
            Volver al perfil
        </a>
    </p>
</x-guest-layout>
