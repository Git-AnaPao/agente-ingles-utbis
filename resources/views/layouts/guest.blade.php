<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Agente Inglés UTBIS') }}</title>

        <script>
            (function() {
                var t = localStorage.getItem('theme');
                var g = localStorage.getItem('grayscale');
                if (t) document.documentElement.setAttribute('data-theme', t);
                if (g === 'true') document.documentElement.setAttribute('data-grayscale', 'true');
            })();
        </script>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased" x-data="theme" x-init="init()"
          style="background-color: var(--color-bg);">

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">

            <div class="mb-8 animate-fade-up">
                <a href="/" aria-label="Ir al inicio">
                    <div class="flex flex-col items-center gap-2">
                        <span class="text-6xl block animate-float" role="img" aria-label="Búho tutor">🦉</span>
                        <span class="font-display font-bold text-sm tracking-wide" style="color: var(--color-primary);">
                            Agente Inglés · UTBIS
                        </span>
                    </div>
                </a>
            </div>

            <div class="w-full sm:max-w-md solid-card px-8 py-8 animate-fade-up">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
