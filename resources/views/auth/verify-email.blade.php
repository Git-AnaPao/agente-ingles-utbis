<x-guest-layout>
    {{-- Encabezado --}}
    <div class="mb-6 text-center">
        <span class="text-5xl" role="img" aria-label="Correo enviado">📬</span>
        <h1 class="mt-3 font-display font-bold text-2xl" style="color:#27594B;">
            Verifica tu correo
        </h1>
        <p class="mt-2 text-sm" style="color:#6B7280; font-family:Inter,sans-serif;">
            Te enviamos un enlace de verificación. Revisa tu bandeja de entrada y haz clic en el enlace para activar tu cuenta.
        </p>
    </div>

    {{-- Confirmación de reenvío --}}
    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-medium"
             style="background-color:#EAF5EA; color:#518C4F; font-family:Inter,sans-serif;"
             role="alert">
            <span aria-hidden="true">✅</span>
            {{ __('Se envió un nuevo enlace de verificación a tu correo.') }}
        </div>
    @endif

    <div class="flex flex-col gap-3">
        {{-- Reenviar --}}
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full">
                {{ __('Reenviar correo de verificación') }}
            </x-primary-button>
        </form>

        {{-- Cerrar sesión --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full rounded-2xl px-5 py-2.5 text-sm font-medium transition duration-150 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200"
                    style="color:#6B7280; font-family:Inter,sans-serif;">
                {{ __('Cerrar sesión') }}
            </button>
        </form>
    </div>
</x-guest-layout>
