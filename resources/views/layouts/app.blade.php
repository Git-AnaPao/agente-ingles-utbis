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
    <body class="font-sans antialiased" x-data="theme" x-init="init()"
          style="background-color: var(--color-bg);">

        <div class="min-h-screen">
            @include('layouts.navigation')

            @isset($header)
                <header class="shadow-sm" style="background-color: var(--color-primary);">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
