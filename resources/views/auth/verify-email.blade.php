<x-guest-layout title="Verificar correo">
    <div class="mb-6 text-center">
        <span class="text-5xl" role="img" aria-label="{{ $verified ? 'Correo verificado' : 'Correo enviado' }}">{{ $verified ? '✓' : '📬' }}</span>
        <h1 class="mt-3 font-display font-bold text-2xl" style="color: var(--color-primary);">
            {{ $verified ? 'Correo verificado' : 'Verifica tu correo' }}
        </h1>
        <p class="mt-2 text-sm" style="color: var(--color-text-secondary); font-family:Inter,sans-serif;">
            @if ($verified)
                Tu dirección quedó verificada correctamente. Ya puedes continuar a la plataforma.
            @else
                Enviamos un enlace a <strong class="break-all" style="color: var(--color-text);">{{ $email }}</strong>. Revisa tu bandeja de entrada para activar tu cuenta.
            @endif
        </p>
    </div>

    @if ($verified)
        <div class="mb-4 rounded-2xl px-4 py-3 text-sm font-medium"
             style="background: var(--color-success-surface); color: var(--color-success-text);"
             role="status">
            Verificación completada para {{ $email }}.
        </div>
    @elseif (session('status') === 'verification-link-sent')
        <div class="mb-4 flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-medium"
             style="background: color-mix(in srgb, var(--color-primary) 10%, transparent); color: var(--color-primary); font-family:Inter,sans-serif;"
             role="status">
            <span aria-hidden="true">✓</span>
            {{ __('Se envió un nuevo enlace de verificación a tu correo.') }}
        </div>
    @endif

    <div class="flex flex-col gap-3">
        @if ($verified)
            <a href="{{ route('dashboard') }}" class="btn-3d btn-3d-green inline-flex min-h-11 w-full items-center justify-center rounded-2xl px-5 py-2.5 text-sm font-bold">
                Continuar a la plataforma
            </a>
        @else
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-primary-button class="w-full" data-loading-text="Enviando...">
                    {{ __('Reenviar correo de verificación') }}
                </x-primary-button>
            </form>

            <a href="{{ route('profile.edit') }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-2xl border px-5 py-2.5 text-sm font-semibold"
               style="color: var(--color-primary); border-color: var(--color-border);">
                Corregir la dirección en mi perfil
            </a>
        @endif

        <form method="POST" action="{{ route('logout') }}" data-clear-chat-history>
            @csrf
            <button type="submit"
                    class="w-full rounded-2xl px-5 py-2.5 text-sm font-medium transition duration-150 focus:outline-none"
                    style="color: var(--color-text-secondary); border: 1px solid var(--color-border); font-family:Inter,sans-serif;">
                {{ __('Cerrar sesión') }}
            </button>
        </form>
    </div>
</x-guest-layout>
