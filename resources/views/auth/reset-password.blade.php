<x-guest-layout title="Restablecer Contraseña">
    <div class="mb-7 text-center">
        <h1 class="font-display font-black text-2xl sm:text-3xl tracking-tight" style="color: var(--color-text);">
            Crea una Nueva Contraseña
        </h1>
        <p class="mt-1.5 text-xs sm:text-sm text-slate-400">
            Define una clave segura para proteger tu cuenta de estudiante o docente.
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        {{-- Email --}}
        <div>
            <x-input-label for="email" value="Correo Electrónico Institucional" />
            <x-text-input id="email" type="email" name="email"
                           :value="old('email', $request->email)"
                           :invalid="$errors->has('email')"
                           aria-describedby="email-error"
                           class="mt-1 block w-full rounded-2xl"
                           required autofocus autocomplete="username" />
            <x-input-error id="email-error" :messages="$errors->get('email')" class="mt-1" />
        </div>

        {{-- Nueva contraseña --}}
        <div>
            <x-input-label for="password" value="Nueva Contraseña" />
            <x-text-input id="password" type="password" name="password"
                           :invalid="$errors->has('password')"
                           aria-describedby="password-help password-error"
                           class="mt-1 block w-full rounded-2xl"
                           required autocomplete="new-password" />
            <p id="password-help" class="form-help text-[11px] text-slate-400 mt-1">Usa al menos 8 caracteres.</p>
            <x-input-error id="password-error" :messages="$errors->get('password')" class="mt-1" />
        </div>

        {{-- Confirmar contraseña --}}
        <div>
            <x-input-label for="password_confirmation" value="Confirmar Nueva Contraseña" />
            <x-text-input id="password_confirmation" type="password"
                           name="password_confirmation"
                           :invalid="$errors->has('password_confirmation')"
                           aria-describedby="password-confirmation-error"
                           class="mt-1 block w-full rounded-2xl"
                           required autocomplete="new-password" />
            <x-input-error id="password-confirmation-error" :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full btn-lumina btn-3d-green py-3.5 rounded-2xl font-bold text-sm shadow-md" data-loading-text="Guardando...">
                Actualizar y Guardar Contraseña
            </button>
        </div>
    </form>
</x-guest-layout>
