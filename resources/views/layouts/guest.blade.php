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
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased" x-data="theme" x-init="init()"
          style="background-color: var(--color-bg);">

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">

            <div class="mb-6">
                <a href="/" aria-label="Ir al inicio">
                    <div class="flex flex-col items-center gap-1">
                        <span class="text-5xl" role="img" aria-label="Búho tutor">🦉</span>
                        <span class="font-display font-bold text-sm tracking-wide" style="color: var(--color-primary);">
                            Agente Inglés · UTBIS
                        </span>
                    </div>
                </a>
            </div>

            <div class="w-full sm:max-w-md px-6 py-8 shadow-lg overflow-hidden sm:rounded-2xl"
                 style="background-color: var(--color-card);">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
