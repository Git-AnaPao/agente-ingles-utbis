<x-guest-layout title="Recuperar Contraseña">
    <div class="mb-7 text-center">
        <h1 class="font-display font-black text-2xl sm:text-3xl tracking-tight" style="color: var(--color-text);">
            Recupera tu Contraseña
        </h1>
        <p class="mt-1.5 text-xs sm:text-sm text-slate-400">
            Ingresa tu correo institucional y te enviaremos un enlace seguro de restablecimiento.
        </p>
    </div>

    {{-- Estado de sesión --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        {{-- Email --}}
        <div>
            <x-input-label for="email" value="Correo Electrónico Institucional" />
            <x-text-input id="email" type="email" name="email"
                           :value="old('email')"
                           :invalid="$errors->has('email')"
                           aria-describedby="email-error"
                           class="mt-1 block w-full rounded-2xl"
                           placeholder="ejemplo@utbispuebla.edu.mx"
                           required autofocus autocomplete="email" />
            <x-input-error id="email-error" :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full btn-lumina btn-3d-green py-3.5 rounded-2xl font-bold text-sm shadow-md" data-loading-text="Enviando...">
                Enviar Enlace de Recuperación
            </button>
        </div>
    </form>

    <div class="mt-5 rounded-2xl border p-4 text-xs leading-relaxed"
         style="border-color: var(--color-border); background: var(--color-bg); color: var(--color-text-secondary);">
        Por seguridad, si la cuenta fue creada exclusivamente mediante Google SSO, el acceso debe gestionarse directamente desde Google.
    </div>

    <p class="mt-6 text-center text-xs text-slate-400">
        <a href="{{ route('login') }}" class="font-bold text-emerald-500 hover:underline">
            ← Volver a Iniciar Sesión
        </a>
    </p>
</x-guest-layout>
