<x-guest-layout>
    {{-- Encabezado --}}
    <div class="mb-6">
        <h1 class="font-display font-bold text-2xl" style="color: var(--color-primary);">
            Recupera tu contraseña 🔑
        </h1>
        <p class="mt-1 text-sm" style="color: var(--color-text-secondary); font-family:Inter,sans-serif;">
            No hay problema. Ingresa tu correo y te enviaremos un enlace para crear una nueva.
        </p>
    </div>

    {{-- Estado de sesión --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" type="email" name="email"
                          :value="old('email')"
                          required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <x-primary-button class="w-full">
            {{ __('Enviar enlace de recuperación') }}
        </x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm" style="color: var(--color-text-secondary); font-family:Inter,sans-serif;">
        <a href="{{ route('login') }}"
           class="font-semibold transition hover:underline"
           style="color: var(--color-primary);">
            ← {{ __('Volver al inicio de sesión') }}
        </a>
    </p>
</x-guest-layout>
