<form method="post" action="{{ route('password.update') }}" class="space-y-5">
    @csrf
    @method('put')

    <div>
        <x-input-label for="update_password_current_password" value="Contraseña actual" />
        <x-text-input id="update_password_current_password" name="current_password" type="password"
                      :invalid="$errors->updatePassword->has('current_password')"
                      aria-describedby="current-password-error"
                      required autocomplete="current-password" />
        <x-input-error id="current-password-error" :messages="$errors->updatePassword->get('current_password')" />
    </div>

    <div>
        <x-input-label for="update_password_password" value="Nueva contraseña" />
        <x-text-input id="update_password_password" name="password" type="password"
                      :invalid="$errors->updatePassword->has('password')"
                      aria-describedby="new-password-help new-password-error"
                      required autocomplete="new-password" />
        <p id="new-password-help" class="form-help">Usa al menos 8 caracteres.</p>
        <x-input-error id="new-password-error" :messages="$errors->updatePassword->get('password')" />
    </div>

    <div>
        <x-input-label for="update_password_password_confirmation" value="Confirmar nueva contraseña" />
        <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password"
                      :invalid="$errors->updatePassword->has('password_confirmation')"
                      aria-describedby="password-confirmation-error"
                      required autocomplete="new-password" />
        <x-input-error id="password-confirmation-error" :messages="$errors->updatePassword->get('password_confirmation')" />
    </div>

    <x-primary-button data-loading-text="Actualizando...">Actualizar contraseña</x-primary-button>
</form>
