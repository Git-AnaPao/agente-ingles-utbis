<x-app-layout title="Mi Perfil">
    @php($hasLocalPassword = filled($user->getAuthPassword()))

    <div class="py-8 sm:py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            {{-- Header de Perfil --}}
            <header class="glass-card p-6 sm:p-8 flex items-center justify-between border"
                    style="border-color: var(--color-glass-border);">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl font-bold font-display shrink-0 shadow-sm"
                         style="background: color-mix(in srgb, var(--color-primary) 12%, transparent); border: 1px solid color-mix(in srgb, var(--color-primary) 30%, transparent); color: var(--color-primary);">
                        {{ strtoupper(substr($user->user_name, 0, 1)) }}
                    </div>
                    <div>
                        <span class="text-xs font-bold font-mono uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Configuración de Cuenta</span>
                        <h1 class="font-display font-black text-2xl sm:text-3xl tracking-tight" style="color: var(--color-text);">
                            Mi Perfil
                        </h1>
                        <p class="text-xs sm:text-sm mt-0.5" style="color: var(--color-text-secondary);">Gestiona tus datos personales, accesibilidad y credenciales de seguridad.</p>
                    </div>
                </div>
            </header>

            @if (session('error'))
                <div class="rounded-2xl px-5 py-3.5 text-xs font-semibold border shadow-sm"
                     style="background: var(--color-error-surface); border-color: var(--color-error-border); color: var(--color-error-text);"
                     role="alert">
                    {{ session('error') }}
                </div>
            @elseif (session('status') === 'profile-updated' || session('status') === 'password-updated')
                <div class="rounded-2xl px-5 py-3.5 text-xs font-semibold border shadow-sm"
                     style="background: var(--color-success-surface); border-color: var(--color-success-border); color: var(--color-success-text);"
                     role="status">
                    {{ session('status') === 'profile-updated' ? 'Información de perfil guardada con éxito.' : 'Contraseña actualizada correctamente.' }}
                </div>
            @endif

            {{-- 1. Información Personal --}}
            <section class="glass-card p-6 sm:p-9 border shadow-xl animate-fade-up" style="border-color: var(--color-glass-border);" aria-labelledby="profile-information-title">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm" style="background: color-mix(in srgb, var(--color-primary) 12%, transparent); color: var(--color-primary);" aria-hidden="true">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <h2 id="profile-information-title" class="font-display font-black text-lg" style="color: var(--color-text);">
                        Información Personal
                    </h2>
                </div>
                <p class="mb-6 text-xs sm:text-sm text-slate-400">Actualiza tus nombres, número de teléfono y revisa tu correo institucional registrado.</p>
                @include('profile.partials.update-profile-information-form')
            </section>

            {{-- 2. Seguridad & Contraseña --}}
            @if ($hasLocalPassword)
                <section class="glass-card p-6 sm:p-9 border shadow-xl animate-fade-up" style="border-color: var(--color-glass-border);" aria-labelledby="password-settings-title">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm text-amber-500 bg-amber-500/10" aria-hidden="true">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </div>
                        <h2 id="password-settings-title" class="font-display font-black text-lg" style="color: var(--color-text);">
                            Seguridad & Contraseña
                        </h2>
                    </div>
                    <p class="mb-6 text-xs sm:text-sm text-slate-400">Utiliza una contraseña robusta de al menos 8 caracteres para proteger tu cuenta.</p>
                    @include('profile.partials.update-password-form')
                </section>
            @else
                <section class="glass-card p-6 sm:p-9 border shadow-xl animate-fade-up" style="border-color: var(--color-glass-border);" aria-labelledby="google-account-title">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-bold bg-sky-500/10 text-sky-500" aria-hidden="true">
                            G
                        </div>
                        <h2 id="google-account-title" class="font-display font-black text-lg" style="color: var(--color-text);">
                            Cuenta administrada con Google
                        </h2>
                    </div>
                    <p class="mt-2 text-xs sm:text-sm leading-relaxed text-slate-400">
                        Esta cuenta está vinculada y protegida mediante el inicio de sesión único de Google (@utbispuebla.edu.mx).
                    </p>
                </section>
            @endif

            {{-- 3. Preferencias de Apariencia & Accesibilidad --}}
            <section class="glass-card p-6 sm:p-9 border shadow-xl animate-fade-up" style="border-color: var(--color-glass-border);" aria-labelledby="appearance-title">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm text-indigo-500 bg-indigo-500/10" aria-hidden="true">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" /></svg>
                    </div>
                    <h2 id="appearance-title" class="font-display font-black text-lg" style="color: var(--color-text);">
                        Apariencia & Accesibilidad
                    </h2>
                </div>
                
                <div class="space-y-5 divide-y" style="border-color: var(--color-border);">
                    <div class="flex items-center justify-between gap-4 pt-2">
                        <div>
                            <p id="dark-mode-label" class="text-sm font-bold" style="color: var(--color-text);">Modo Oscuro (Dark Theme)</p>
                            <p id="dark-mode-description" class="text-xs text-slate-400 mt-0.5">Alterna entre interfaz de alto contraste oscuro o claro.</p>
                        </div>
                        <button type="button"
                                @click="toggleTheme()"
                                class="relative inline-flex h-8 w-14 items-center rounded-full transition-colors duration-300 shrink-0 border"
                                :class="theme === 'dark' ? 'bg-emerald-600 border-emerald-500' : 'bg-slate-700 border-slate-600'"
                                role="switch"
                                :aria-checked="(theme === 'dark').toString()"
                                aria-labelledby="dark-mode-label"
                                aria-describedby="dark-mode-description">
                            <span class="inline-block h-6 w-6 transform rounded-full bg-white shadow-md transition duration-300" :class="theme === 'dark' ? 'translate-x-7' : 'translate-x-1'"></span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between gap-4 pt-5">
                        <div>
                            <p id="grayscale-label" class="text-sm font-bold" style="color: var(--color-text);">Modo Escala de Grises</p>
                            <p id="grayscale-description" class="text-xs text-slate-400 mt-0.5">Sustituye los colores de marca por tonos monocromáticos para accesibilidad visual.</p>
                        </div>
                        <button type="button"
                                @click="toggleGrayscale()"
                                class="relative inline-flex h-8 w-14 items-center rounded-full transition-colors duration-300 shrink-0 border"
                                :class="grayscale ? 'bg-slate-500 border-slate-400' : 'bg-slate-700 border-slate-600'"
                                role="switch"
                                :aria-checked="grayscale.toString()"
                                aria-labelledby="grayscale-label"
                                aria-describedby="grayscale-description">
                            <span class="inline-block h-6 w-6 transform rounded-full bg-white shadow-md transition duration-300" :class="grayscale ? 'translate-x-7' : 'translate-x-1'"></span>
                        </button>
                    </div>
                </div>
            </section>

            {{-- 4. Zona de Peligro --}}
            @if ($hasLocalPassword)
                <section class="glass-card p-6 sm:p-9 border border-red-500/25 shadow-xl animate-fade-up" aria-labelledby="danger-zone-title">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-bold text-red-500 bg-red-500/10" aria-hidden="true">
                            !
                        </div>
                        <h2 id="danger-zone-title" class="font-display font-black text-lg text-red-500">
                            Zona de Peligro
                        </h2>
                    </div>
                    @include('profile.partials.delete-user-form')
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
