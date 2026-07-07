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
    <body class="font-sans antialiased" x-data="theme" x-init="init()">

        <div class="min-h-screen relative">
            @include('layouts.navigation')

            @isset($header)
                <header class="border-b" style="background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark)); border-color: transparent;">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="page-enter">
                {{ $slot }}
            </main>
        </div>

        {{-- Espacio para el asistente Búho IA --}}
        <div class="owl-assistant">
            <div class="owl-tip" x-show="showOwlTip" x-cloak>
                <p class="font-semibold text-xs" style="color: var(--color-primary);">¡Hola! Soy tu búho tutor 🦉</p>
                <p style="color: var(--color-text-secondary);">Completa lecciones para ganar XP y subir de nivel.</p>
            </div>
            <button class="owl-avatar" @click="showOwlTip = !showOwlTip"
                    aria-label="Asistente búho">
                🦉
            </button>
        </div>

        <script>
            // Exponer variables de tema para uso global si es necesario
            window.__theme = {
                getTheme: () => localStorage.getItem('theme') || 'light',
                getGrayscale: () => localStorage.getItem('grayscale') === 'true',
            };
        </script>
    </body>
</html>
