<div class="space-y-5">
    <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary);">
        Al eliminar tu cuenta se borrarán permanentemente tus avances y datos asociados. Esta acción no se puede deshacer.
    </p>

    <x-danger-button type="button"
                     x-data
                     x-on:click="$dispatch('open-modal', 'confirm-user-deletion')">
        Eliminar cuenta
    </x-danger-button>

    <x-modal name="confirm-user-deletion"
             :show="$errors->userDeletion->isNotEmpty()"
             maxWidth="lg"
             titleId="confirm-user-deletion-title"
             focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-5 sm:p-6">
            @csrf
            @method('delete')

            <h2 id="confirm-user-deletion-title" class="text-lg font-bold" style="color: var(--color-text);">
                ¿Eliminar tu cuenta permanentemente?
            </h2>

            <p class="mt-2 text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                Escribe tu contraseña para confirmar. Se cerrará tu sesión y se eliminarán los datos de la cuenta.
            </p>

            <div class="mt-6">
                <x-input-label for="delete_account_password" value="Contraseña" />
                <x-text-input id="delete_account_password"
                              name="password"
                              type="password"
                              :invalid="$errors->userDeletion->has('password')"
                              aria-describedby="delete-password-error"
                              required autocomplete="current-password" />
                <x-input-error id="delete-password-error" :messages="$errors->userDeletion->get('password')" />
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-danger-button data-loading-text="Eliminando...">Eliminar definitivamente</x-danger-button>
            </div>
        </form>
    </x-modal>
</div>
