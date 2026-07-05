<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Agente Inglés UTBIS') }}</title>

        <!-- Fuentes: Plus Jakarta Sans (títulos) + Inter (cuerpo) — Style Guide §Tipografía -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    {{-- Fondo Gris Claro (#F2F2F2) — Style Guide §Colores de Interfaz --}}
    <body class="font-sans text-gray-900 antialiased" style="background-color: #F2F2F2;">

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">

            {{-- Logo / Owl avatar --}}
            <div class="mb-6">
                <a href="/" aria-label="Ir al inicio">
                    {{-- Búho pixel art placeholder — Style Guide §Personaje --}}
                    <div class="flex flex-col items-center gap-1">
                        <span class="text-5xl" role="img" aria-label="Búho tutor">🦉</span>
                        <span class="font-display font-bold text-sm tracking-wide" style="color:#27594B;">
                            Agente Inglés · UTBIS
                        </span>
                    </div>
                </a>
            </div>

            {{-- Tarjeta blanca — Style Guide §Pantalla de Chat / §Colores de Interfaz --}}
            <div class="w-full sm:max-w-md px-6 py-8 bg-white shadow-lg overflow-hidden sm:rounded-2xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
