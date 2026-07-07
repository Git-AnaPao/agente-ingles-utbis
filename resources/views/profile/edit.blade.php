<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white leading-tight tracking-tight">
            {{ __('Perfil') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Información de perfil --}}
            <div class="solid-card p-6 sm:p-8 animate-fade-up">
                <h3 class="font-display font-bold text-lg mb-6 flex items-center gap-2" style="color: var(--color-primary);">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center text-sm"
                          style="background: color-mix(in srgb, var(--color-primary) 12%, transparent);">👤</span>
                    Información personal
                </h3>
                @include('profile.partials.update-profile-information-form')
            </div>

            {{-- Contraseña --}}
            <div class="solid-card p-6 sm:p-8 animate-fade-up" style="animation-delay: 0.05s;">
                <h3 class="font-display font-bold text-lg mb-6 flex items-center gap-2" style="color: var(--color-primary);">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center text-sm"
                          style="background: color-mix(in srgb, var(--color-primary) 12%, transparent);">🔒</span>
                    Contraseña
                </h3>
                @include('profile.partials.update-password-form')
            </div>

            {{-- Apariencia --}}
            <div class="solid-card p-6 sm:p-8 animate-fade-up" style="animation-delay: 0.1s; border-left: 4px solid var(--color-warning);">
                <h3 class="font-display font-bold text-lg mb-6 flex items-center gap-2" style="color: var(--color-primary);">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center text-sm"
                          style="background: color-mix(in srgb, var(--color-warning) 20%, transparent);">🎨</span>
                    Apariencia
                </h3>
                <div class="space-y-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium" style="color: var(--color-text);">Modo oscuro</p>
                            <p class="text-xs mt-0.5" style="color: var(--color-text-secondary);">Cambiar entre tema claro y oscuro</p>
                        </div>
                        <button @click="toggleTheme"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-300 shrink-0"
                                :class="theme === 'dark' ? 'bg-amber-500' : 'bg-gray-300'"
                                role="switch"
                                :aria-checked="theme === 'dark'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-300"
                                  :class="theme === 'dark' ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t" style="border-color: var(--color-border);">
                        <div>
                            <p class="text-sm font-medium" style="color: var(--color-text);">Tonos grises</p>
                            <p class="text-xs mt-0.5" style="color: var(--color-text-secondary);">Reemplazar colores de marca por tonos neutros</p>
                        </div>
                        <button @click="toggleGrayscale"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-300 shrink-0"
                                :class="grayscale ? 'bg-gray-500' : 'bg-gray-300'"
                                role="switch"
                                :aria-checked="grayscale">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-300"
                                  :class="grayscale ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Eliminar cuenta --}}
            <div class="solid-card p-6 sm:p-8 animate-fade-up" style="animation-delay: 0.15s;">
                <h3 class="font-display font-bold text-lg mb-6 flex items-center gap-2" style="color: #DC2626;">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center text-sm"
                          style="background: color-mix(in srgb, #DC2626 12%, transparent);">⚠️</span>
                    Zona de peligro
                </h3>
                @include('profile.partials.delete-user-form')
            </div>

        </div>
    </div>
</x-app-layout>
