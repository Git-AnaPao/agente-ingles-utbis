@php
    $currentUser = auth()->user();
    $roles = $currentUser?->roles?->pluck('role_name')->all() ?? [];
    $isStudent = in_array('student', $roles, true);
    $isAdmin = in_array('admin', $roles, true);
    $isProfessor = in_array('professor', $roles, true);

    $displayName = trim(implode(' ', array_filter([
        $currentUser?->user_name,
        $currentUser?->user_last_name,
    ])));

    $initial = strtoupper(substr($currentUser?->user_name ?? 'U', 0, 1));
    $homeRoute = $isAdmin
        ? route('admin.dashboard')
        : ($isProfessor ? route('professor.dashboard') : route('dashboard'));

    // Datos de gamificación en vivo para el usuario
    $gamificationService = app(\App\Services\GamificationService::class);
    $gamification = $currentUser ? $gamificationService->snapshot($currentUser) : [];
    $streak = $gamification['current_streak'] ?? 0;
    $totalXp = $gamification['total_xp'] ?? 0;
    $levelInfo = $gamification['level'] ?? ['level' => 1];
    
    // Nivel CEFR
    $placement = $currentUser ? \App\Models\StudentProgress::latestPlacementFor($currentUser) : null;
    $cefrLevel = $placement?->result_level ?? 'A1';

    $dashboardActive = request()->routeIs('dashboard');
    $learningActive = request()->routeIs('levels.*') || request()->routeIs('lessons.*');
    $listeningActive = request()->routeIs('listening.*');
    $chatActive = request()->routeIs('chat.*');
    $placementActive = request()->routeIs('placement.*');
    $professorActive = request()->routeIs('professor.*');
    $adminActive = request()->routeIs('admin.dashboard');
@endphp

{{-- ═════════════════════════════════════════════════════════════════
     1. SIDEBAR IZQUIERDO FIJO (DESKTOP ESTILO DUOLINGO)
     ═════════════════════════════════════════════════════════════════ --}}
<aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 z-50 border-r transition-colors duration-300"
       style="border-color: var(--color-border); background: var(--color-card);"
       aria-label="Navegación principal">
    
    {{-- Marca & Logotipo --}}
    <div class="h-20 flex items-center px-6 border-b" style="border-color: var(--color-border);">
        <a href="{{ $homeRoute }}" class="flex items-center gap-3 group">
            <div class="w-11 h-11 rounded-2xl flex items-center justify-center p-1.5 transition-transform duration-200 group-hover:scale-105"
                 style="background: color-mix(in srgb, var(--color-primary) 12%, var(--color-card));">
                <img src="{{ asset('img/buho.png') }}" alt="Búho UTBIS" class="w-full h-full object-contain">
            </div>
            <div>
                <span class="font-display font-black text-lg tracking-tight gradient-text block leading-none">
                    Agente Inglés
                </span>
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    UTBIS · AI Campus
                </span>
            </div>
        </a>
    </div>

    {{-- Enlaces Principales de Navegación Estilo Duolingo 3D --}}
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto no-scrollbar" aria-label="Secciones de aprendizaje">
        @if ($isStudent)
            {{-- Inicio / Dashboard --}}
            <a href="{{ route('dashboard') }}"
               class="duo-sidebar-link {{ $dashboardActive ? 'is-active' : '' }}"
               @if ($dashboardActive) aria-current="page" @endif>
                <x-icon name="home" class="w-5 h-5 shrink-0" />
                <span>Inicio</span>
            </a>

            {{-- Ruta de Aprendizaje CEFR --}}
            <a href="{{ route('levels.index') }}"
               class="duo-sidebar-link {{ $learningActive ? 'is-active' : '' }}"
               @if ($learningActive) aria-current="page" @endif>
                <x-icon name="map" class="w-5 h-5 shrink-0" />
                <span>Ruta CEFR</span>
            </a>

            {{-- Listening Lab --}}
            <a href="{{ route('listening.index') }}"
               class="duo-sidebar-link {{ $listeningActive ? 'is-active' : '' }}"
               @if ($listeningActive) aria-current="page" @endif>
                <x-icon name="headphones" class="w-5 h-5 shrink-0" />
                <span>Listening Lab</span>
            </a>

            {{-- Tutor IA Gemini --}}
            <a href="{{ route('chat.index') }}"
               class="duo-sidebar-link {{ $chatActive ? 'is-active' : '' }}"
               @if ($chatActive) aria-current="page" @endif>
                <x-icon name="bot" class="w-5 h-5 shrink-0 text-indigo-500" />
                <span>Tutor IA</span>
            </a>

            {{-- Test de Diagnóstico --}}
            <a href="{{ route('placement.index') }}"
               class="duo-sidebar-link {{ $placementActive ? 'is-active' : '' }}"
               @if ($placementActive) aria-current="page" @endif>
                <x-icon name="target" class="w-5 h-5 shrink-0" />
                <span>Diagnóstico</span>
            </a>
        @else
            {{-- Panel de Profesor --}}
            <a href="{{ route('professor.dashboard') }}"
               class="duo-sidebar-link {{ $professorActive ? 'is-active' : '' }}"
               @if ($professorActive) aria-current="page" @endif>
                <x-icon name="chart-bar" class="w-5 h-5 shrink-0 text-indigo-500" />
                <span>Seguimiento</span>
            </a>

            @if ($isAdmin)
                {{-- Panel de Administración --}}
                <a href="{{ route('admin.dashboard') }}"
                   class="duo-sidebar-link {{ $adminActive ? 'is-active' : '' }}"
                   @if ($adminActive) aria-current="page" @endif>
                    <x-icon name="settings" class="w-5 h-5 shrink-0 text-purple-500" />
                    <span>Consola Admin</span>
                </a>
                <a href="{{ route('admin.users') }}"
                   class="duo-sidebar-link {{ request()->routeIs('admin.users*') ? 'is-active' : '' }}">
                    <x-icon name="user" class="w-5 h-5 shrink-0 text-sky-500" />
                    <span>Usuarios</span>
                </a>
            @endif
        @endif

        {{-- Separador --}}
        <div class="pt-4 border-t my-2" style="border-color: var(--color-border);">
            <a href="{{ route('profile.edit') }}"
               class="duo-sidebar-link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}">
                <x-icon name="user" class="w-5 h-5 shrink-0" />
                <span>Mi Perfil</span>
            </a>
        </div>
    </nav>

    {{-- Pie del Sidebar: Usuario, Tema & Salida --}}
    <div class="p-4 border-t space-y-3" style="border-color: var(--color-border); background: var(--color-bg);">
        {{-- Tarjeta de Perfil Rápida --}}
        <div class="flex items-center justify-between gap-3 p-2.5 rounded-2xl border"
             style="background: var(--color-card); border-color: var(--color-border);">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl font-display font-extrabold text-xs text-white shadow-sm"
                      style="background: linear-gradient(135deg, #10B981, #059669);" aria-hidden="true">
                    {{ $initial }}
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-display font-bold truncate" style="color: var(--color-text);">{{ $displayName }}</p>
                    <p class="text-[10px] font-mono truncate" style="color: var(--color-text-secondary);">{{ $currentUser->user_email }}</p>
                </div>
            </div>

            {{-- Botón de Tema --}}
            <button type="button"
                    @click="toggleTheme()"
                    class="w-8 h-8 rounded-xl flex items-center justify-center border transition-all duration-150 hover:border-emerald-500 shrink-0"
                    style="background: var(--color-bg); border-color: var(--color-border);"
                    :title="theme === 'dark' ? 'Modo claro' : 'Modo oscuro'">
                <template x-if="theme === 'dark'">
                    <x-icon name="sun" class="w-4 h-4 text-amber-400" />
                </template>
                <template x-if="theme !== 'dark'">
                    <x-icon name="moon" class="w-4 h-4 text-indigo-500" />
                </template>
            </button>
        </div>

        <form method="POST" action="{{ route('logout') }}" data-clear-chat-history>
            @csrf
            <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-red-600 dark:text-red-400 hover:bg-red-500/10 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                <span>Cerrar sesión</span>
            </button>
        </form>
    </div>
</aside>

{{-- ═════════════════════════════════════════════════════════════════
     2. BARRA SUPERIOR STICKY DE GAMIFICACIÓN & ESTADÍSTICAS
     ═════════════════════════════════════════════════════════════════ --}}
<header class="sticky top-0 z-40 border-b backdrop-blur-xl transition-colors duration-300 lg:pl-64"
        style="border-color: var(--color-glass-border); background: var(--color-glass);"
        aria-label="Barra superior de estado y progreso">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 gap-3">
            
            {{-- Identidad Mobile (Logo visible solo en móviles) --}}
            <div class="flex items-center gap-3 lg:hidden min-w-0">
                <a href="{{ $homeRoute }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('img/buho.png') }}" alt="Búho" class="w-8 h-8 object-contain">
                    <span class="font-display font-black text-sm gradient-text truncate">Agente Inglés</span>
                </a>
            </div>

            {{-- Título de Contexto Desktop --}}
            <div class="hidden lg:flex items-center gap-2 text-xs font-semibold" style="color: var(--color-text-secondary);">
                <span>Universidad Tecnológica de Puebla</span>
                <span>·</span>
                <span class="font-mono text-emerald-600 dark:text-emerald-400 font-bold">UTBIS English Campus</span>
            </div>

            {{-- Estadísticas Gamificadas Duolingo + EF English --}}
            <div class="flex items-center gap-2 sm:gap-3">
                @if ($isStudent)
                    {{-- Racha Diaria 🔥 --}}
                    <div class="gamification-pill border-orange-500/30 text-orange-600 dark:text-orange-400"
                         title="Racha de estudio: {{ $streak }} días consecutivos">
                        <x-icon name="flame" class="w-4 h-4 text-orange-500 animate-pulse" />
                        <span class="font-mono font-black text-xs sm:text-sm">{{ $streak }}</span>
                        <span class="hidden sm:inline text-[11px] font-bold uppercase">días</span>
                    </div>

                    {{-- XP Total & Nivel ⚡ --}}
                    <div class="gamification-pill border-amber-500/30 text-amber-600 dark:text-amber-400"
                         title="Nivel {{ $levelInfo['level'] }} · {{ number_format($totalXp) }} XP acumulados">
                        <x-icon name="gem" class="w-4 h-4 text-amber-500" />
                        <span class="font-mono font-black text-xs sm:text-sm">{{ number_format($totalXp) }}</span>
                        <span class="hidden sm:inline text-[11px] font-bold uppercase">XP</span>
                    </div>

                    {{-- Nivel CEFR Asignado 🎯 --}}
                    <div class="gamification-pill border-emerald-500/30 text-emerald-600 dark:text-emerald-400 hidden sm:inline-flex"
                         title="Nivel CEFR de entrada: {{ $cefrLevel }}">
                        <x-icon name="target" class="w-4 h-4 text-emerald-500" />
                        <span class="font-mono font-black text-xs sm:text-sm">Nivel {{ $cefrLevel }}</span>
                    </div>
                @else
                    <div class="gamification-pill border-indigo-500/30 text-indigo-600 dark:text-indigo-400">
                        <x-icon name="user" class="w-4 h-4 text-indigo-500" />
                        <span class="font-bold text-xs">{{ $isAdmin ? 'Administrador' : 'Profesor' }}</span>
                    </div>
                @endif

                {{-- Selector de Modo Claro/Oscuro --}}
                <button type="button"
                        @click="toggleTheme()"
                        class="w-10 h-10 rounded-2xl flex items-center justify-center transition-all duration-200 border hover:border-emerald-500/50"
                        style="background: var(--color-card); border-color: var(--color-border); color: var(--color-text);"
                        :title="theme === 'dark' ? 'Modo claro' : 'Modo oscuro'">
                    <template x-if="theme === 'dark'">
                        <x-icon name="sun" class="w-4 h-4 text-amber-400" />
                    </template>
                    <template x-if="theme !== 'dark'">
                        <x-icon name="moon" class="w-4 h-4 text-indigo-500" />
                    </template>
                </button>

                {{-- Dropdown de Usuario --}}
                <x-dropdown id="top-user-menu" align="right" width="56">
                    <x-slot name="trigger">
                        <button type="button"
                                class="inline-flex items-center gap-2 p-1 rounded-2xl border transition hover:border-emerald-500"
                                style="background: var(--color-card); border-color: var(--color-border);"
                                aria-label="Menú de usuario">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl font-bold text-xs text-white shadow-sm"
                                  style="background: linear-gradient(135deg, #10B981, #059669);">
                                {{ $initial }}
                            </span>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 border-b min-w-0" style="border-color: var(--color-border);">
                            <p class="text-sm font-bold truncate" style="color: var(--color-text);">{{ $displayName }}</p>
                            <p class="text-xs truncate font-mono mt-0.5" style="color: var(--color-text-secondary);">{{ $currentUser->user_email }}</p>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')">
                            <span class="flex items-center gap-2.5">
                                <x-icon name="user" class="w-4 h-4 text-slate-400" />
                                Mi Perfil
                            </span>
                        </x-dropdown-link>

                        <x-dropdown-link as="button" type="button" @click="toggleGrayscale()">
                            <span class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" /></svg>
                                <span x-text="grayscale ? 'Modo Color' : 'Escala de Grises'"></span>
                            </span>
                        </x-dropdown-link>

                        <div class="border-t" style="border-color: var(--color-border);">
                            <form method="POST" action="{{ route('logout') }}" data-clear-chat-history>
                                @csrf
                                <x-dropdown-link as="button" type="submit" class="text-red-500">
                                    <span class="flex items-center gap-2.5">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                        Cerrar sesión
                                    </span>
                                </x-dropdown-link>
                            </form>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</header>

{{-- ═════════════════════════════════════════════════════════════════
     3. BARRA DE NAVEGACIÓN INFERIOR TÁCTIL (MOBILE ESTILO DUOLINGO)
     ═════════════════════════════════════════════════════════════════ --}}
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-50 border-t backdrop-blur-2xl shadow-2xl transition-colors duration-300"
     style="border-color: var(--color-border); background: var(--color-glass);"
     aria-label="Navegación móvil inferior">
    <div class="grid grid-cols-5 h-16 items-center px-2">
        @if ($isStudent)
            {{-- Inicio --}}
            <a href="{{ route('dashboard') }}"
               class="flex flex-col items-center justify-center gap-1 py-1 transition-transform active:scale-90 {{ $dashboardActive ? 'text-emerald-600 dark:text-emerald-400 font-extrabold' : 'text-slate-400' }}"
               aria-label="Inicio">
                <x-icon name="home" class="w-5 h-5" />
                <span class="text-[10px] uppercase font-bold tracking-tight">Inicio</span>
            </a>

            {{-- Ruta CEFR --}}
            <a href="{{ route('levels.index') }}"
               class="flex flex-col items-center justify-center gap-1 py-1 transition-transform active:scale-90 {{ $learningActive ? 'text-emerald-600 dark:text-emerald-400 font-extrabold' : 'text-slate-400' }}"
               aria-label="Ruta CEFR">
                <x-icon name="map" class="w-5 h-5" />
                <span class="text-[10px] uppercase font-bold tracking-tight">Ruta</span>
            </a>

            {{-- Listening --}}
            <a href="{{ route('listening.index') }}"
               class="flex flex-col items-center justify-center gap-1 py-1 transition-transform active:scale-90 {{ $listeningActive ? 'text-emerald-600 dark:text-emerald-400 font-extrabold' : 'text-slate-400' }}"
               aria-label="Listening">
                <x-icon name="headphones" class="w-5 h-5" />
                <span class="text-[10px] uppercase font-bold tracking-tight">Audio</span>
            </a>

            {{-- Tutor IA --}}
            <a href="{{ route('chat.index') }}"
               class="flex flex-col items-center justify-center gap-1 py-1 transition-transform active:scale-90 {{ $chatActive ? 'text-indigo-600 dark:text-indigo-400 font-extrabold' : 'text-slate-400' }}"
               aria-label="Tutor IA">
                <x-icon name="bot" class="w-5 h-5" />
                <span class="text-[10px] uppercase font-bold tracking-tight">Tutor IA</span>
            </a>

            {{-- Perfil --}}
            <a href="{{ route('profile.edit') }}"
               class="flex flex-col items-center justify-center gap-1 py-1 transition-transform active:scale-90 {{ request()->routeIs('profile.*') ? 'text-emerald-600 dark:text-emerald-400 font-extrabold' : 'text-slate-400' }}"
               aria-label="Perfil">
                <x-icon name="user" class="w-5 h-5" />
                <span class="text-[10px] uppercase font-bold tracking-tight">Perfil</span>
            </a>
        @else
            {{-- Docente / Admin --}}
            <a href="{{ route('professor.dashboard') }}"
               class="flex flex-col items-center justify-center gap-1 py-1 transition-transform active:scale-90 {{ $professorActive ? 'text-indigo-600 dark:text-indigo-400 font-extrabold' : 'text-slate-400' }}"
               aria-label="Seguimiento">
                <x-icon name="chart-bar" class="w-5 h-5" />
                <span class="text-[10px] uppercase font-bold tracking-tight">Panel</span>
            </a>

            @if ($isAdmin)
                <a href="{{ route('admin.dashboard') }}"
                   class="flex flex-col items-center justify-center gap-1 py-1 transition-transform active:scale-90 {{ $adminActive ? 'text-purple-600 dark:text-purple-400 font-extrabold' : 'text-slate-400' }}"
                   aria-label="Admin">
                    <x-icon name="settings" class="w-5 h-5" />
                    <span class="text-[10px] uppercase font-bold tracking-tight">Admin</span>
                </a>
                <a href="{{ route('admin.users') }}"
                   class="flex flex-col items-center justify-center gap-1 py-1 transition-transform active:scale-90 {{ request()->routeIs('admin.users*') ? 'text-sky-600 dark:text-sky-400 font-extrabold' : 'text-slate-400' }}"
                   aria-label="Usuarios">
                    <x-icon name="user" class="w-5 h-5" />
                    <span class="text-[10px] uppercase font-bold tracking-tight">Usuarios</span>
                </a>
            @endif

            <a href="{{ route('profile.edit') }}"
               class="flex flex-col items-center justify-center gap-1 py-1 transition-transform active:scale-90 {{ request()->routeIs('profile.*') ? 'text-emerald-600 dark:text-emerald-400 font-extrabold' : 'text-slate-400' }}"
               aria-label="Perfil">
                <x-icon name="user" class="w-5 h-5" />
                <span class="text-[10px] uppercase font-bold tracking-tight">Perfil</span>
            </a>
        @endif
    </div>
</nav>
