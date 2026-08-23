<x-app-layout title="Panel de Administración">
    <div class="py-8 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Header Admin --}}
            <header class="glass-card p-6 sm:p-8 flex items-center justify-between border"
                    style="border-color: var(--color-glass-border);">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shrink-0 shadow-sm"
                         style="background: linear-gradient(135deg, rgba(2, 132, 199, 0.15), rgba(2, 132, 199, 0.05)); border: 1px solid rgba(2, 132, 199, 0.3); color: #0284C7;">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold font-mono uppercase tracking-wider text-sky-600 dark:text-sky-400">Control & Operaciones</span>
                        <h1 class="font-display text-2xl sm:text-3xl font-black tracking-tight" style="color: var(--color-text);">
                            Panel de Administración
                        </h1>
                        <p class="text-xs sm:text-sm mt-0.5" style="color: var(--color-text-secondary);">Gobierno de usuarios, roles institucionales y parámetros de la plataforma.</p>
                    </div>
                </div>

                <a href="{{ route('admin.users.create') }}" class="btn-lumina btn-3d-green text-xs px-4 py-2.5 hidden sm:inline-flex items-center gap-1.5 shadow-md">
                    <span>+ Nuevo Usuario</span>
                </a>
            </header>

            {{-- Métricas Bento de Usuarios --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 animate-fade-up">
                <div class="solid-card p-6 text-center relative overflow-hidden group">
                    <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-emerald-400 to-emerald-600"></div>
                    <span class="block text-3xl sm:text-4xl font-extrabold font-display" style="color: var(--color-primary);">{{ $totalUsers }}</span>
                    <span class="text-xs font-bold uppercase tracking-wider mt-1 block" style="color: var(--color-text-secondary);">Usuarios Totales</span>
                </div>
                <div class="solid-card p-6 text-center relative overflow-hidden group">
                    <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-sky-400 to-blue-600"></div>
                    <span class="block text-3xl sm:text-4xl font-extrabold font-display text-sky-500">{{ $students }}</span>
                    <span class="text-xs font-bold uppercase tracking-wider mt-1 block" style="color: var(--color-text-secondary);">Estudiantes</span>
                </div>
                <div class="solid-card p-6 text-center relative overflow-hidden group">
                    <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-amber-400 to-orange-600"></div>
                    <span class="block text-3xl sm:text-4xl font-extrabold font-display text-amber-500">{{ $professors }}</span>
                    <span class="text-xs font-bold uppercase tracking-wider mt-1 block" style="color: var(--color-text-secondary);">Profesores</span>
                </div>
                <div class="solid-card p-6 text-center relative overflow-hidden group">
                    <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-indigo-400 to-purple-600"></div>
                    <span class="block text-3xl sm:text-4xl font-extrabold font-display text-indigo-500">{{ $admins }}</span>
                    <span class="text-xs font-bold uppercase tracking-wider mt-1 block" style="color: var(--color-text-secondary);">Administradores</span>
                </div>
            </div>

            {{-- Hub de Accesos Rápidos Admin --}}
            <div class="grid gap-4 sm:grid-cols-2 animate-fade-up">
                <a href="{{ route('admin.users') }}"
                   class="glass-card p-7 flex items-start gap-5 border transition-all duration-300 hover:border-emerald-500/50 hover:shadow-glow-sm group">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shrink-0 transition duration-300 group-hover:scale-110"
                         style="background: color-mix(in srgb, var(--color-primary) 12%, transparent); color: var(--color-primary);">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                    <div>
                        <h2 class="font-display font-bold text-lg group-hover:text-emerald-500 transition duration-200" style="color: var(--color-text);">Directorio de Usuarios</h2>
                        <p class="text-xs sm:text-sm mt-1 leading-relaxed" style="color: var(--color-text-secondary);">Crear, buscar, modificar roles y dar de baja cuentas de estudiantes, docentes y administradores.</p>
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 dark:text-emerald-400 mt-4">
                            <span>Gestionar cuentas</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </span>
                    </div>
                </a>

                <a href="{{ route('levels.index') }}"
                   class="glass-card p-7 flex items-start gap-5 border transition-all duration-300 hover:border-indigo-500/50 hover:shadow-glow-ai group">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shrink-0 transition duration-300 group-hover:scale-110"
                         style="background: color-mix(in srgb, var(--color-indigo) 12%, transparent); color: var(--color-indigo);">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                    </div>
                    <div>
                        <h2 class="font-display font-bold text-lg group-hover:text-indigo-500 transition duration-200" style="color: var(--color-text);">Mapa Curricular CEFR</h2>
                        <p class="text-xs sm:text-sm mt-1 leading-relaxed" style="color: var(--color-text-secondary);">Supervisar las rutas académicas, subniveles disponibles y catálogo de listening institucional.</p>
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 dark:text-indigo-400 mt-4">
                            <span>Explorar ruta formativa</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </span>
                    </div>
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
