<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white leading-tight">
            {{ __('Perfil') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Información de perfil --}}
            <div class="bg-white shadow-sm rounded-2xl p-6 sm:p-8">
                <h3 class="font-display font-bold text-lg mb-4" style="color:#27594B;">
                    Información personal
                </h3>
                @include('profile.partials.update-profile-information-form')
            </div>

            {{-- Contraseña --}}
            <div class="bg-white shadow-sm rounded-2xl p-6 sm:p-8">
                <h3 class="font-display font-bold text-lg mb-4" style="color:#27594B;">
                    Contraseña
                </h3>
                @include('profile.partials.update-password-form')
            </div>

            {{-- Eliminar cuenta --}}
            <div class="bg-white shadow-sm rounded-2xl p-6 sm:p-8"
                 style="border-left:4px solid #F2B950;">
                <h3 class="font-display font-bold text-lg mb-4" style="color:#27594B;">
                    Zona de peligro
                </h3>
                @include('profile.partials.delete-user-form')
            </div>

        </div>
    </div>
</x-app-layout>
