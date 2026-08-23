<!DOCTYPE html>
<html lang="es">
    <head>
        @php
            $routeName = request()->route()?->getName();
            $defaultTitle = match (true) {
                $routeName === 'dashboard' => 'Inicio',
                $routeName === 'chat.index' => 'Tutor IA',
                str_starts_with((string) $routeName, 'profile.') => 'Perfil',
                $routeName === 'admin.dashboard' => 'Panel de administración',
                $routeName === 'admin.users' => 'Gestión de usuarios',
                $routeName === 'admin.users.create' => 'Nuevo usuario',
                $routeName === 'admin.users.edit' => 'Editar usuario',
                $routeName === 'professor.dashboard' => 'Panel del profesor',
                $routeName === 'professor.student-progress' => 'Progreso del estudiante',
                $routeName === 'levels.index' => 'Ruta de aprendizaje',
                $routeName === 'lessons.learn' => 'Lección de inglés',
                $routeName === 'listening.index' => 'Catálogo de listening',
                $routeName === 'listening.show' => 'Práctica de listening',
                default => 'Plataforma de aprendizaje',
            };
            $pageTitle = $attributes->get('title') ?: $defaultTitle;
            $pageDescription = match (true) {
                $routeName === 'dashboard' => 'Resumen de avance y accesos a la ruta de aprendizaje de Agente Inglés UTBIS.',
                $routeName === 'levels.index' => 'Ruta de aprendizaje de inglés organizada por niveles CEFR.',
                str_starts_with((string) $routeName, 'listening.') => 'Actividades de comprensión auditiva de Agente Inglés UTBIS.',
                $routeName === 'chat.index' => 'Tutor de inglés con orientación adaptada al nivel registrado.',
                default => 'Plataforma institucional de aprendizaje de inglés de UTBIS.',
            };
            $statusMessages = [
                'profile-updated' => 'Tu perfil se actualizó correctamente.',
                'password-updated' => 'Tu contraseña se actualizó correctamente.',
            ];
            $statusKey = is_string(session('status')) ? session('status') : null;
            $globalMessage = request()->query('verified') === '1'
                ? 'Tu correo se verificó correctamente.'
                : (session('success') ?? ($statusKey ? ($statusMessages[$statusKey] ?? null) : null));
            $globalInfo = session('info');
            $globalError = session('error');
            $viewHandlesFlash = request()->routeIs('levels.*');
        @endphp

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ $pageDescription }}">
        <meta name="application-name" content="{{ config('app.name', 'Agente Inglés UTBIS') }}">
        <meta name="robots" content="noindex, nofollow, noarchive">
        <meta name="referrer" content="strict-origin-when-cross-origin">
        <meta name="color-scheme" content="light dark">

        <title>{{ $pageTitle }} | {{ config('app.name', 'Agente Inglés UTBIS') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('img/buho.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('img/buho.png') }}">

        <script>
            (function() {
                var theme = null;
                var grayscale = false;

                try {
                    theme = localStorage.getItem('theme');
                    grayscale = localStorage.getItem('grayscale') === 'true';
                } catch (error) {
                    // Storage can be unavailable in strict privacy modes.
                }

                if (theme !== 'light' && theme !== 'dark') {
                    theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }

                document.documentElement.setAttribute('data-theme', theme);
                document.documentElement.style.colorScheme = theme;
                document.documentElement.setAttribute('data-grayscale', grayscale ? 'true' : 'false');
            })();
        </script>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased relative min-h-screen selection:bg-emerald-500 selection:text-white"
          x-data="theme"
          data-chat-storage-key="agente-ingles:chat:{{ Auth::id() }}">
        
        {{-- Ambient Glow Backdrops --}}
        <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-40 left-1/4 w-96 h-96 bg-emerald-500/10 dark:bg-emerald-500/8 rounded-full blur-3xl"></div>
            <div class="absolute top-1/3 -right-20 w-80 h-80 bg-indigo-500/10 dark:bg-indigo-500/8 rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 left-10 w-96 h-96 bg-amber-500/8 dark:bg-amber-500/5 rounded-full blur-3xl"></div>
        </div>

        <a href="#main-content" class="skip-link">Saltar al contenido principal</a>

        <div class="min-h-screen relative flex flex-col justify-between">
            <div>
                @include('layouts.navigation')

                @if (! $viewHandlesFlash && ($globalMessage || $globalInfo || $globalError))
                    <div class="mx-auto mt-4 max-w-7xl space-y-3 px-4 sm:px-6 lg:px-8 lg:pl-64 animate-fade-up">
                        @if ($globalMessage)
                            <div class="flash-message flash-success flex items-center gap-3" role="status" aria-live="polite" tabindex="-1">
                                <svg class="w-5 h-5 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span>{{ $globalMessage }}</span>
                            </div>
                        @endif

                        @if ($globalInfo)
                            <div class="flash-message flex items-center gap-3" role="status" aria-live="polite" tabindex="-1"
                                 style="background: color-mix(in srgb, var(--color-blue) 10%, var(--color-card)); border-color: color-mix(in srgb, var(--color-blue) 35%, transparent); color: var(--color-text);">
                                <svg class="w-5 h-5 shrink-0 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span>{{ $globalInfo }}</span>
                            </div>
                        @endif

                        @if ($globalError)
                            <div class="flash-message flash-error flex items-center gap-3" role="alert" tabindex="-1">
                                <svg class="w-5 h-5 shrink-0 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span>{{ $globalError }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                <main id="main-content" class="page-enter lg:pl-64 pb-20 lg:pb-8" tabindex="-1">
                    {{ $slot }}
                </main>
            </div>

            {{-- Minimalist Institutional Footer --}}
            <footer class="mt-8 py-6 border-t text-center text-xs lg:pl-64 pb-24 lg:pb-6" style="border-color: var(--color-border); color: var(--color-text-secondary);">
                <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="font-display font-bold" style="color: var(--color-text);">Agente Inglés</span>
                        <span>·</span>
                        <span>Universidad Tecnológica de Puebla (UTBIS)</span>
                    </div>
                    <div class="font-mono text-[11px]">
                        Powered by Google Gemini 2.0 Flash AI
                    </div>
                </div>
            </footer>
        </div>

        @stack('scripts')
    </body>
</html>
