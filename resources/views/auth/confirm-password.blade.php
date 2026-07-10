<x-guest-layout>
    {{-- Encabezado --}}
    <div class="mb-6">
        <h1 class="font-display font-bold text-2xl" style="color: var(--color-primary);">
            Confirma tu contraseña 🔒
        </h1>
        <p class="mt-1 text-sm" style="color: var(--color-text-secondary); font-family:Inter,sans-serif;">
            Esta es un área segura. Por favor confirma tu contraseña antes de continuar.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Contraseña')" />
            <x-text-input id="password" type="password" name="password"
                          required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <x-primary-button class="w-full">
            {{ __('Confirmar') }}
        </x-primary-button>
    </form>
</x-guest-layout>
