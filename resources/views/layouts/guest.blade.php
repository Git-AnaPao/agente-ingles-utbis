<!DOCTYPE html>
<html lang="es">
    <head>
        @php
            $routeName = request()->route()?->getName();
            $defaultTitle = match ($routeName) {
                'login' => 'Iniciar sesión',
                'register' => 'Crear cuenta',
                'password.request' => 'Recuperar contraseña',
                'password.reset' => 'Restablecer contraseña',
                'password.confirm' => 'Confirmar contraseña',
                'verification.notice' => 'Verificar correo',
                default => 'Acceso',
            };
            $pageTitle = $attributes->get('title') ?: $defaultTitle;
        @endphp

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Acceso institucional a la plataforma de aprendizaje de inglés de UTBIS.">
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
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased relative min-h-screen selection:bg-emerald-500 selection:text-white"
          x-data="theme"
          @auth data-chat-storage-key="agente-ingles:chat:{{ Auth::id() }}" @endauth
          style="background-color: var(--color-bg);">

        {{-- Ambient Glow Backdrops --}}
        <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[34rem] h-[34rem] bg-emerald-500/15 dark:bg-emerald-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-10 w-80 h-80 bg-indigo-500/10 dark:bg-indigo-500/8 rounded-full blur-3xl"></div>
        </div>

        <a href="#auth-content" class="skip-link">Saltar al formulario</a>

        <main id="auth-content" class="min-h-screen flex flex-col justify-center items-center py-10 px-4 sm:px-6" tabindex="-1">

            {{-- Theme toggle --}}
            <button type="button"
                    @click="toggleTheme()"
                    class="fixed top-5 right-5 z-50 w-11 h-11 rounded-2xl flex items-center justify-center transition-all duration-200 border hover:border-emerald-500/50 shadow-sm backdrop-blur-md"
                    style="background: var(--color-card); border-color: var(--color-border); color: var(--color-text);"
                    :title="theme === 'dark' ? 'Usar tema claro' : 'Usar tema oscuro'"
                    :aria-label="theme === 'dark' ? 'Usar tema claro' : 'Usar tema oscuro'">
                <span aria-hidden="true" x-text="theme === 'dark' ? '☀️' : '🌙'" class="text-base"></span>
            </button>

            <div class="mb-8 text-center animate-fade-up">
                <a href="/" aria-label="Ir al inicio de Agente Inglés" class="inline-flex flex-col items-center gap-3 group focus:outline-none">
                    <x-application-logo class="w-14 h-14 transition-transform duration-300 group-hover:scale-105" />
                    <div class="flex flex-col items-center">
                        <span class="font-display font-extrabold text-xl tracking-tight gradient-text">
                            Agente Inglés
                        </span>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                            UTBIS · AI Language Platform
                        </span>
                    </div>
                </a>
            </div>

            <div class="w-full sm:max-w-md glass-card p-6 sm:p-8 animate-fade-up border"
                 style="border-color: var(--color-glass-border); box-shadow: 0 20px 40px -15px rgba(0,0,0,0.1);">
                {{ $slot }}
            </div>

            <p class="mt-8 text-center text-xs" style="color: var(--color-text-secondary);">
                Universidad Tecnológica de Puebla · UTBIS Bilingüe Internacional y Sustentable
            </p>
        </main>
    </body>
</html>
