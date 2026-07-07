<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white leading-tight">
            {{ __('Perfil') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Información de perfil --}}
            <div class="shadow-sm rounded-2xl p-6 sm:p-8" style="background-color: var(--color-card);">
                <h3 class="font-display font-bold text-lg mb-4" style="color: var(--color-primary);">
                    Información personal
                </h3>
                @include('profile.partials.update-profile-information-form')
            </div>

            {{-- Contraseña --}}
            <div class="shadow-sm rounded-2xl p-6 sm:p-8" style="background-color: var(--color-card);">
                <h3 class="font-display font-bold text-lg mb-4" style="color: var(--color-primary);">
                    Contraseña
                </h3>
                @include('profile.partials.update-password-form')
            </div>

            {{-- Apariencia --}}
            <div class="shadow-sm rounded-2xl p-6 sm:p-8" style="background-color: var(--color-card); border-left: 4px solid var(--color-warning);">
                <h3 class="font-display font-bold text-lg mb-4" style="color: var(--color-primary);">
                    Apariencia
                </h3>
                <div class="space-y-4">
                    {{-- Tema oscuro --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium" style="color: var(--color-text);">Modo oscuro</p>
                            <p class="text-xs" style="color: var(--color-text-secondary);">Cambiar entre tema claro y oscuro</p>
                        </div>
                        <button @click="toggleTheme"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200"
                                :class="theme === 'dark' ? 'bg-amber-500' : 'bg-gray-300'"
                                role="switch"
                                :aria-checked="theme === 'dark'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition duration-200"
                                  :class="theme === 'dark' ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>

                    {{-- Tonos grises --}}
                    <div class="flex items-center justify-between pt-3 border-t" style="border-color: var(--color-border);">
                        <div>
                            <p class="text-sm font-medium" style="color: var(--color-text);">Tonos grises</p>
                            <p class="text-xs" style="color: var(--color-text-secondary);">Reemplazar colores de marca por tonos neutros</p>
                        </div>
                        <button @click="toggleGrayscale"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200"
                                :class="grayscale ? 'bg-gray-500' : 'bg-gray-300'"
                                role="switch"
                                :aria-checked="grayscale">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition duration-200"
                                  :class="grayscale ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Eliminar cuenta --}}
            <div class="shadow-sm rounded-2xl p-6 sm:p-8"
                 style="background-color: var(--color-card); border-left:4px solid var(--color-warning);">
                <h3 class="font-display font-bold text-lg mb-4" style="color: var(--color-primary);">
                    Zona de peligro
                </h3>
                @include('profile.partials.delete-user-form')
            </div>

        </div>
    </div>
</x-app-layout>
