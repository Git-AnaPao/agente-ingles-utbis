<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Agente Inglés · UTBIS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen antialiased" style="background-color: var(--color-bg); font-family:Inter,sans-serif;">

    {{-- Navbar --}}
    <nav class="fixed top-0 inset-x-0 z-50" x-data="theme" x-init="init()">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="/" class="flex items-center gap-2.5 group" aria-label="Inicio">
                    <span class="text-2xl transition-transform duration-300 group-hover:scale-110">🦉</span>
                    <span class="font-display font-bold text-sm" style="color: var(--color-primary);">Agente Inglés</span>
                </a>
                <div class="flex items-center gap-3">
                    <button @click="toggleTheme()"
                            class="w-9 h-9 rounded-full flex items-center justify-center transition-all duration-300 hover:scale-110"
                            style="background: var(--color-glass); border: 1px solid var(--color-glass-border);"
                            :title="theme === 'dark' ? 'Modo claro' : 'Modo oscuro'"
                            aria-label="Cambiar tema">
                        <span x-text="theme === 'dark' ? '&#9728;&#65039;' : '&#127769;'" class="text-base"></span>
                    </button>
                    <a href="{{ route('login') }}"
                       class="px-4 py-2 rounded-xl text-sm font-semibold transition hover:opacity-80"
                       style="color: var(--color-primary);">
                        Iniciar sesión
                    </a>
                    <a href="{{ route('register') }}"
                       class="px-5 py-2 rounded-xl text-sm font-bold text-white shadow-md transition hover:shadow-lg hover:-translate-y-0.5"
                       style="background: linear-gradient(135deg, var(--color-accent), #FF6B4A);">
                        Registrarme
                    </a>
                </div>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="pt-28 pb-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center">
            <span class="inline-flex rounded-full px-4 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] mb-6"
                  style="background: color-mix(in srgb, var(--color-primary) 10%, transparent); color: var(--color-primary);">
                UTBIS · Agente de IA
            </span>

            <div class="flex justify-center mb-6">
                <div class="w-24 h-24 rounded-3xl flex items-center justify-center text-6xl animate-float"
                     style="background: color-mix(in srgb, var(--color-primary) 10%, transparent);">
                    🦉
                </div>
            </div>

            <h1 class="font-display font-extrabold text-4xl sm:text-5xl md:text-6xl leading-tight"
                style="color: var(--color-text);">
                Aprende inglés de forma
                <span class="gradient-text">simple, clara y constante.</span>
            </h1>

            <p class="mt-6 text-lg leading-relaxed max-w-2xl mx-auto" style="color: var(--color-text-secondary);">
                Tu tutor virtual te acompaña con lecciones interactivas, práctica guiada y feedback amable, adaptado a tu nivel del Marco Común Europeo (CEFR).
            </p>

            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}"
                   class="w-full sm:w-auto px-8 py-4 rounded-2xl font-bold text-white text-base shadow-lg transition-all duration-200 hover:shadow-xl hover:-translate-y-1"
                   style="background: linear-gradient(135deg, var(--color-accent), #FF6B4A); font-family:'Plus Jakarta Sans',sans-serif;">
                    Comenzar gratis &#x2192;
                </a>
                <a href="{{ route('login') }}"
                   class="w-full sm:w-auto px-8 py-4 rounded-2xl font-bold text-base transition-all duration-200 solid-card hover:shadow-lg hover:-translate-y-0.5"
                   style="color: var(--color-primary); font-family:'Plus Jakarta Sans',sans-serif;">
                    Ya tengo cuenta
                </a>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="font-display font-bold text-2xl sm:text-3xl" style="color: var(--color-text);">
                    ¿Por qué Agente Inglés?
                </h2>
                <p class="mt-3 text-sm" style="color: var(--color-text-secondary);">
                    Todo lo que necesitas para mejorar tu inglés en un solo lugar.
                </p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {{-- Feature 1 --}}
                <div class="solid-card p-6 group transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-4"
                         style="background: linear-gradient(135deg, var(--color-accent-light), var(--color-accent));">
                        🎯
                    </div>
                    <h3 class="font-display font-bold text-base mb-2" style="color: var(--color-text);">Test de ubicación CEFR</h3>
                    <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                        Evaluación de 75 preguntas para determinar tu nivel real: A1, A2, B1, B2 o C1.
                    </p>
                </div>

                {{-- Feature 2 --}}
                <div class="solid-card p-6 group transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-4"
                         style="background: linear-gradient(135deg, var(--color-primary-light), var(--color-primary));">
                        📚
                    </div>
                    <h3 class="font-display font-bold text-base mb-2" style="color: var(--color-text);">Lecciones adaptativas</h3>
                    <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                        Contenido organizado por niveles CEFR con lecciones de Grammar, Vocabulary y Reading.
                    </p>
                </div>

                {{-- Feature 3 --}}
                <div class="solid-card p-6 group transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-4"
                         style="background: linear-gradient(135deg, var(--color-purple), #9B8FF0);">
                        💬
                    </div>
                    <h3 class="font-display font-bold text-base mb-2" style="color: var(--color-text);">Tútor IA (búho)</h3>
                    <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                        Un asistente inteligente que te guía, resuelve dudas y te da feedback durante tu aprendizaje.
                    </p>
                </div>

                {{-- Feature 4 --}}
                <div class="solid-card p-6 group transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-4"
                         style="background: linear-gradient(135deg, var(--color-warning), var(--color-accent));">
                        🏆
                    </div>
                    <h3 class="font-display font-bold text-base mb-2" style="color: var(--color-text);">Gamificación</h3>
                    <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                        Gana XP, sube de nivel, completa insignias y desbloquea logros a medida que avanzas.
                    </p>
                </div>

                {{-- Feature 5 --}}
                <div class="solid-card p-6 group transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-4"
                         style="background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));">
                        📊
                    </div>
                    <h3 class="font-display font-bold text-base mb-2" style="color: var(--color-text);">Seguimiento de progreso</h3>
                    <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                        Profesores y estudiantes visualizan avances, estadísticas y nivel actual en tiempo real.
                    </p>
                </div>

                {{-- Feature 6 --}}
                <div class="solid-card p-6 group transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-4"
                         style="background: linear-gradient(135deg, var(--color-accent), var(--color-warning));">
                        🔒
                    </div>
                    <h3 class="font-display font-bold text-base mb-2" style="color: var(--color-text);">Acceso exclusivo UTBIS</h3>
                    <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                        Solo usuarios con correo institucional @utbispuebla.edu.mx pueden registrarse.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- CEFR Levels --}}
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="solid-card p-8 sm:p-10">
                <div class="text-center mb-8">
                    <h2 class="font-display font-bold text-2xl sm:text-3xl" style="color: var(--color-text);">
                        Niveles CEFR que cubrimos
                    </h2>
                    <p class="mt-3 text-sm" style="color: var(--color-text-secondary);">
                        Desde cero hasta nivel avanzado, con un camino claro de aprendizaje.
                    </p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                    @php
                        $cefrLevels = [
                            ['level' => 'A1', 'name' => 'Beginner', 'color' => '#2D6A4F'],
                            ['level' => 'A2', 'name' => 'Elementary', 'color' => '#40916C'],
                            ['level' => 'B1', 'name' => 'Intermediate', 'color' => '#5B8DEF'],
                            ['level' => 'B2', 'name' => 'Upper-Inter.', 'color' => '#9B6FE8'],
                            ['level' => 'C1', 'name' => 'Advanced', 'color' => '#E86F8A'],
                        ];
                    @endphp
                    @foreach ($cefrLevels as $item)
                        <div class="text-center p-4 rounded-2xl transition-all duration-200 hover:-translate-y-1"
                             style="background: color-mix(in srgb, {{ $item['color'] }} 8%, transparent); border: 2px solid color-mix(in srgb, {{ $item['color'] }} 20%, transparent);">
                            <span class="text-2xl font-bold font-display" style="color: {{ $item['color'] }};">{{ $item['level'] }}</span>
                            <p class="text-[11px] font-semibold mt-1" style="color: var(--color-text-secondary);">{{ $item['name'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- CTA Final --}}
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center">
            <div class="solid-card p-10 sm:p-14"
                 style="background: linear-gradient(135deg, var(--color-primary-dark), var(--color-primary)); border-color: transparent;">
                <span class="text-5xl block mb-4">🦉</span>
                <h2 class="font-display font-bold text-2xl sm:text-3xl text-white mb-4">
                    ¿Listo para empezar?
                </h2>
                <p class="text-base text-white/80 mb-8 max-w-lg mx-auto">
                    Crea tu cuenta gratuita y descubre tu nivel de inglés con nuestro test de ubicación CEFR.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('register') }}"
                       class="w-full sm:w-auto px-8 py-4 rounded-2xl font-bold text-base shadow-lg transition-all duration-200 hover:shadow-xl hover:-translate-y-1"
                       style="background: var(--color-accent); color: white; font-family:'Plus Jakarta Sans',sans-serif;">
                        Crear cuenta gratis &#x2192;
                    </a>
                    <a href="{{ route('login') }}"
                       class="w-full sm:w-auto px-8 py-4 rounded-2xl font-bold text-base transition-all duration-200 hover:bg-white/10"
                       style="border: 2px solid rgba(255,255,255,0.3); color: white; font-family:'Plus Jakarta Sans',sans-serif;">
                        Ya tengo cuenta
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="py-8 px-4 text-center" style="border-top: 1px solid var(--color-border);">
        <p class="text-xs" style="color: var(--color-text-secondary);">
            &copy; {{ date('Y') }} UTBIS &mdash; Agente de Inglés con IA
        </p>
    </footer>

</body>
</html>
