<nav x-data="{ open: false }" class="border-b" style="background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark)); border-color: rgba(255,255,255,0.06);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- Logo + enlaces --}}
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="shrink-0 flex items-center gap-2.5 me-8 group"
                   aria-label="Inicio Agente Inglés">
                    <span class="text-2xl transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6" role="img" aria-label="Búho tutor">🦉</span>
                    <span class="font-display font-bold text-white text-sm hidden sm:block tracking-tight">
                        Agente Inglés
                    </span>
                </a>

                {{-- Desktop nav --}}
                <div class="hidden space-x-0.5 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 me-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        {{ __('Inicio') }}
                    </x-nav-link>

                    <x-nav-link :href="route('dashboard')" :active="false">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 me-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                        {{ __('Chat IA') }}
                    </x-nav-link>

                    <x-nav-link :href="route('levels.index')" :active="request()->routeIs('levels.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 me-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 13l5.447-2.724A1 1 0 0021 16.382V5.618a1 1 0 00-.553-.894L15 4m0 0V4m0 0V4" />
                        </svg>
                        {{ __('Avance') }}
                    </x-nav-link>

                    @if (Auth::user()->isProfessor() || Auth::user()->isAdmin())
                        <x-nav-link :href="route('professor.dashboard')" :active="request()->routeIs('professor.*')">
                            📚 {{ __('Profesor') }}
                        </x-nav-link>
                    @endif

                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                            ⚙️ {{ __('Admin') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            {{-- Dropdown usuario --}}
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium text-white/85 hover:text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/25 transition duration-200"
                                aria-label="Menú de usuario">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full font-bold text-xs shadow-inner"
                                  style="background: linear-gradient(135deg, var(--color-accent), var(--color-warning)); color: white;">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            <span class="hidden lg:inline">{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 text-white/60" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 border-b" style="border-color: var(--color-border);">
                            <p class="text-sm font-semibold" style="color: var(--color-text);">{{ Auth::user()->name }}</p>
                            <p class="text-xs truncate" style="color: var(--color-text-secondary);">{{ Auth::user()->email }}</p>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')">
                            <span class="flex items-center gap-2.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ __('Perfil') }}
                            </span>
                        </x-dropdown-link>

                        <button @click="toggleTheme" class="w-full text-start">
                            <x-dropdown-link>
                                <span class="flex items-center gap-2.5">
                                    <template x-if="theme === 'dark'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    </template>
                                    <template x-if="theme !== 'dark'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                                    </template>
                                    <span x-text="theme === 'dark' ? 'Modo claro' : 'Modo oscuro'"></span>
                                </span>
                            </x-dropdown-link>
                        </button>

                        <div class="border-t" style="border-color: var(--color-border);">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    <span class="flex items-center gap-2.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        {{ __('Cerrar sesión') }}
                                    </span>
                                </x-dropdown-link>
                            </form>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- Hamburger (móvil) --}}
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                        class="inline-flex items-center justify-center p-2 rounded-xl text-white/70 hover:text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/30 transition duration-200"
                        :aria-expanded="open.toString()"
                        aria-label="Abrir menú">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path :class="{'hidden': open, 'inline-flex': !open }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Menú responsive --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t" style="border-color: rgba(255,255,255,0.06); background: var(--color-primary-dark);">
        <div class="pt-2 pb-3 space-y-1 px-3">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                🏠 {{ __('Inicio') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('dashboard')" :active="false">
                💬 {{ __('Chat IA') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('levels.index')" :active="request()->routeIs('levels.*')">
                🗺️ {{ __('Avance') }}
            </x-responsive-nav-link>
            @if (Auth::user()->isProfessor() || Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('professor.dashboard')" :active="request()->routeIs('professor.*')">
                    📚 {{ __('Profesor') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                    ⚙️ {{ __('Admin') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="pt-3 pb-4 border-t px-3" style="border-color: rgba(255,255,255,0.08);">
            <div class="flex items-center gap-3 mb-3 px-1">
                <span class="flex h-9 w-9 items-center justify-center rounded-full font-bold text-white text-xs shadow-inner"
                      style="background: linear-gradient(135deg, var(--color-accent), var(--color-warning));">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </span>
                <div>
                    <div class="font-semibold text-sm text-white">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-white/60">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    👤 {{ __('Perfil') }}
                </x-responsive-nav-link>

                <button @click="toggleTheme" class="w-full text-start">
                    <x-responsive-nav-link>
                        <span class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                            <span x-text="theme === 'dark' ? 'Modo claro' : 'Modo oscuro'"></span>
                        </span>
                    </x-responsive-nav-link>
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        🚪 {{ __('Cerrar sesión') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
