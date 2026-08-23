<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}" class="space-y-5">
    @csrf
    @method('patch')

    @if ($errors->any())
        <div class="rounded-xl px-4 py-3 text-sm font-semibold"
             style="background: var(--color-error-surface); color: var(--color-error-text);"
             role="alert" tabindex="-1">
            Revisa los campos marcados antes de guardar.
        </div>
    @endif

    @php($googleManagedEmail = $user->google_id !== null && blank($user->getAuthPassword()))

    <div>
        <x-input-label for="name" value="Nombre(s)" />
        <x-text-input id="name" name="name" type="text"
                      :value="old('name', $user->user_name)"
                      :invalid="$errors->has('name')"
                      aria-describedby="name-error"
                      required autofocus autocomplete="given-name" maxlength="255" />
        <x-input-error id="name-error" :messages="$errors->get('name')" />
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <x-input-label for="user_last_name" value="Primer apellido" />
            <x-text-input id="user_last_name" name="user_last_name" type="text"
                          :value="old('user_last_name', $user->user_last_name)"
                          :invalid="$errors->has('user_last_name')"
                          aria-describedby="user_last_name-error"
                          required autocomplete="family-name" maxlength="255" />
            <x-input-error id="user_last_name-error" :messages="$errors->get('user_last_name')" />
        </div>

        <div>
            <x-input-label for="user_middle_name" value="Segundo apellido (opcional)" />
            <x-text-input id="user_middle_name" name="user_middle_name" type="text"
                          :value="old('user_middle_name', $user->user_middle_name)"
                          :invalid="$errors->has('user_middle_name')"
                          aria-describedby="user_middle_name-error"
                          autocomplete="additional-name" maxlength="255" />
            <x-input-error id="user_middle_name-error" :messages="$errors->get('user_middle_name')" />
        </div>
    </div>

    <div>
        <x-input-label for="user_cel" value="Celular (opcional)" />
        <x-text-input id="user_cel" name="user_cel" type="tel"
                      :value="old('user_cel', $user->user_cel)"
                      :invalid="$errors->has('user_cel')"
                      aria-describedby="user_cel-help user_cel-error"
                      inputmode="numeric" autocomplete="tel" maxlength="12" />
        <p id="user_cel-help" class="form-help">Entre 7 y 12 dígitos, sin espacios.</p>
        <x-input-error id="user_cel-error" :messages="$errors->get('user_cel')" />
    </div>

    <div>
        <x-input-label for="email" value="Correo electrónico" />
        <x-text-input id="email" name="email" type="email"
                      :value="old('email', $user->user_email)"
                      :invalid="$errors->has('email')"
                      aria-describedby="email-error"
                      :readonly="$googleManagedEmail"
                      required autocomplete="username" maxlength="255" />
        <x-input-error id="email-error" :messages="$errors->get('email')" />

        @if ($googleManagedEmail)
            <p class="form-help">Este correo se administra desde tu cuenta de Google.</p>
        @endif

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <p class="mt-3 text-sm" style="color: var(--color-text);">
                Tu correo aún no está verificado.
                <button form="send-verification" class="font-semibold underline" style="color: var(--color-primary);">
                    Reenviar el correo de verificación
                </button>
            </p>

            @if (session('status') === 'verification-link-sent')
                <p class="mt-2 font-medium text-sm" style="color: var(--color-success-text);" role="status">
                    Se envió un nuevo enlace de verificación.
                </p>
            @endif
        @endif
    </div>

    <div class="flex flex-wrap items-center gap-4">
        <x-primary-button data-loading-text="Guardando...">Guardar información</x-primary-button>
    </div>
</form>
