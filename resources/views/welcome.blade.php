<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Agente Inglés UTBIS: Plataforma de aprendizaje de inglés potenciada por IA para la comunidad UTBIS con evaluación oral en tiempo real, listening y diagnóstico CEFR.">
    <meta name="color-scheme" content="light dark">
    <meta property="og:title" content="Agente Inglés · UTBIS">
    <meta property="og:description" content="Tu tutor inteligente de inglés con lecciones interactivas, práctica oral con IA y feedback personalizado por nivel CEFR.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ asset('img/buho.png') }}">
    <meta property="og:image:alt" content="Búho, tutor virtual de Agente Inglés UTBIS">
    <meta property="og:site_name" content="Agente Inglés UTBIS">
    <meta property="og:locale" content="es_MX">
    <title>Agente Inglés · UTBIS AI Language Platform</title>
    <link rel="canonical" href="{{ url('/') }}">
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

            var deletedAccountChatKey = @js(session('clear_chat_storage_key'));
            if (deletedAccountChatKey) {
                try {
                    localStorage.removeItem(deletedAccountChatKey);
                } catch (error) {
                    // Handled safely.
                }
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen antialiased relative selection:bg-emerald-500 selection:text-white"
      x-data="theme"
      style="background-color: var(--color-bg);">

    {{-- Background Ambient Illumination --}}
    <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[42rem] h-[42rem] bg-emerald-500/15 dark:bg-emerald-500/10 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 -left-20 w-96 h-96 bg-indigo-500/10 dark:bg-indigo-500/8 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-amber-500/10 dark:bg-amber-500/5 rounded-full blur-3xl"></div>
    </div>

    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>

    {{-- Navbar --}}
    <nav class="fixed top-0 inset-x-0 z-50 backdrop-blur-xl border-b transition-colors duration-300"
         aria-label="Navegación de acceso"
         style="background: var(--color-glass); border-color: var(--color-glass-border);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
             x-data="{
                 open: false,
                 closeOnEscape() {
                     if (!this.open) return;
                     this.open = false;
                     this.$nextTick(() => this.$refs.mobileMenuButton?.focus());
                 }
             }"
             @keydown.escape.window="closeOnEscape()">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <a href="/" class="flex items-center gap-3 group focus:outline-none" aria-label="Inicio">
                    <x-application-logo class="w-9 h-9 transition-transform duration-300 group-hover:scale-105" />
                    <div class="flex flex-col">
                        <span class="font-display font-extrabold text-base tracking-tight gradient-text">
                            Agente Inglés
                        </span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                            UTBIS · AI Studio
                        </span>
                    </div>
                </a>

                <div class="flex items-center gap-3">
                    <button type="button" @click="toggleTheme()"
                             class="w-10 h-10 rounded-2xl flex items-center justify-center transition-all duration-200 border hover:border-emerald-500/50 shadow-sm"
                             style="background: var(--color-card); border-color: var(--color-border); color: var(--color-text);"
                             :title="theme === 'dark' ? 'Modo claro' : 'Modo oscuro'"
                             :aria-label="theme === 'dark' ? 'Usar tema claro' : 'Usar tema oscuro'">
                        <template x-if="theme === 'dark'">
                            <x-icon name="sun" class="w-4 h-4 text-amber-400" />
                        </template>
                        <template x-if="theme !== 'dark'">
                            <x-icon name="moon" class="w-4 h-4 text-indigo-500" />
                        </template>
                    </button>

                    <div class="hidden items-center gap-3 sm:flex">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn-lumina btn-3d-green">
                                Ir a mi panel
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn-secondary">
                                Iniciar sesión
                            </a>
                            <a href="{{ route('register') }}" class="btn-lumina btn-3d-green">
                                Comenzar ahora
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                            </a>
                        @endauth
                    </div>

                    <button type="button"
                            x-ref="mobileMenuButton"
                            @click="open = !open"
                            class="control-target inline-flex items-center justify-center rounded-2xl border sm:hidden"
                            style="background: var(--color-card); border-color: var(--color-border); color: var(--color-text);"
                            aria-controls="landing-mobile-menu"
                            :aria-expanded="open.toString()"
                            :aria-label="open ? 'Cerrar menú' : 'Abrir menú'">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path x-show="!open" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                            <path x-show="open" x-cloak stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                </div>
            </div>

            <div id="landing-mobile-menu" x-show="open" x-cloak class="pb-5 sm:hidden space-y-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-lumina btn-3d-green w-full">Ir a mi panel</a>
                @else
                    <a href="{{ route('login') }}" class="btn-secondary w-full">Iniciar sesión</a>
                    <a href="{{ route('register') }}" class="btn-lumina btn-3d-green w-full">Comenzar ahora</a>
                @endauth
            </div>
        </div>
    </nav>

    <main id="main-content" tabindex="-1">
        {{-- Hero Section --}}
        <section class="pt-32 sm:pt-40 pb-20 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
            <div class="max-w-5xl mx-auto text-center">
                {{-- Institutional AI Badge --}}
                <div class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-bold uppercase tracking-wider mb-8 animate-fade-up shadow-sm border"
                      style="background: color-mix(in srgb, var(--color-primary) 10%, var(--color-card)); color: var(--color-primary); border-color: color-mix(in srgb, var(--color-primary) 30%, transparent);">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>UTBIS Puebla · IA Multimodal con Google Gemini</span>
                </div>

                {{-- Mascot Floating Orb --}}
                <div class="flex justify-center mb-8 animate-fade-up" style="animation-delay: 0.1s;">
                    <div class="relative group">
                        <div class="absolute inset-0 bg-gradient-to-r from-emerald-500 to-indigo-500 rounded-3xl blur-2xl opacity-30 group-hover:opacity-60 transition duration-500"></div>
                        <div class="relative w-28 h-28 sm:w-32 sm:h-32 rounded-3xl flex items-center justify-center glass-card border-2 p-3 shadow-xl"
                             style="border-color: color-mix(in srgb, var(--color-primary) 40%, transparent);">
                            <img src="{{ asset('img/buho.png') }}" alt="Búho tutor inteligente" class="w-20 h-20 sm:w-24 sm:h-24 object-contain animate-float">
                        </div>
                    </div>
                </div>

                {{-- Main Title --}}
                <h1 class="font-display font-black text-4xl sm:text-6xl md:text-7xl leading-[1.1] tracking-tight animate-fade-up"
                    style="color: var(--color-text); animation-delay: 0.15s;">
                    Domina el inglés con un tutor IA
                    <span class="gradient-text block mt-2">creado para tu nivel.</span>
                </h1>

                <p class="mt-6 text-base sm:text-xl leading-relaxed max-w-2xl mx-auto animate-fade-up font-normal"
                   style="color: var(--color-text-secondary); animation-delay: 0.2s;">
                    Evaluación oral con inteligencia artificial, diagnóstico CEFR dinámico y laboratorio de listening diseñado exclusivamente para la comunidad universitaria de la UTBIS.
                </p>

                {{-- CTA Action Group --}}
                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 animate-fade-up" style="animation-delay: 0.25s;">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-lumina btn-3d-green w-full sm:w-auto px-8 py-4 text-base font-bold shadow-lg">
                            Continuar mi aprendizaje
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="btn-lumina btn-3d-green w-full sm:w-auto px-8 py-4 text-base font-bold shadow-lg">
                            Registrarme con correo @utbis
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </a>
                        <a href="{{ route('login') }}" class="btn-secondary w-full sm:w-auto px-8 py-4 text-base font-bold">
                            Ingresar a mi cuenta
                        </a>
                    @endauth
                </div>

                {{-- Stats Strip --}}
                <div class="mt-14 pt-8 border-t max-w-3xl mx-auto grid grid-cols-3 gap-4 animate-fade-up" style="border-color: var(--color-border); animation-delay: 0.3s;">
                    <div>
                        <span class="block font-display font-extrabold text-2xl sm:text-3xl" style="color: var(--color-primary);">A1 · C2</span>
                        <span class="text-xs uppercase tracking-wider font-semibold" style="color: var(--color-text-secondary);">Ruta Marco CEFR</span>
                    </div>
                    <div>
                        <span class="block font-display font-extrabold text-2xl sm:text-3xl text-indigo-500">Gemini 2.0</span>
                        <span class="text-xs uppercase tracking-wider font-semibold" style="color: var(--color-text-secondary);">Speaking con IA</span>
                    </div>
                    <div>
                        <span class="block font-display font-extrabold text-2xl sm:text-3xl text-amber-500">3,000+</span>
                        <span class="text-xs uppercase tracking-wider font-semibold" style="color: var(--color-text-secondary);">Ejercicios Interactivos</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Features Bento Grid --}}
        <section class="py-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-16">
                    <span class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Innovación Educativa</span>
                    <h2 class="font-display font-extrabold text-3xl sm:text-4xl mt-2" style="color: var(--color-text);">
                        Una experiencia de aprendizaje de otro nivel
                    </h2>
                    <p class="mt-3 text-base max-w-xl mx-auto" style="color: var(--color-text-secondary);">
                        Diseñado para impulsar tu bilingüismo con retroalimentación instantánea y práctica constante.
                    </p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {{-- Feature 1 --}}
                    <div class="solid-card p-7 hover:-translate-y-1 transition duration-300">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5 shadow-sm"
                             style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.05)); border: 1px solid rgba(16, 185, 129, 0.3); color: #10B981;">
                            <x-icon name="mic" class="w-6 h-6" />
                        </div>
                        <h3 class="font-display font-bold text-lg mb-2" style="color: var(--color-text);">Evaluación Oral con IA</h3>
                        <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                            Habla por el micrófono y recibe transcripción exacta, corrección gramatical y consejos de pronunciación personalizados con Google Gemini.
                        </p>
                    </div>

                    {{-- Feature 2 --}}
                    <div class="solid-card p-7 hover:-translate-y-1 transition duration-300">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5 shadow-sm"
                             style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(99, 102, 241, 0.05)); border: 1px solid rgba(99, 102, 241, 0.3); color: #6366F1;">
                            <x-icon name="conversation" class="w-6 h-6" />
                        </div>
                        <h3 class="font-display font-bold text-lg mb-2" style="color: var(--color-text);">Tutor IA Conversacional</h3>
                        <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                            Un copiloto de inglés disponible las 24 horas con voz femenina natural que adapta su vocabulario a tu nivel registrado.
                        </p>
                    </div>

                    {{-- Feature 3 --}}
                    <div class="solid-card p-7 hover:-translate-y-1 transition duration-300">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5 shadow-sm"
                             style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(245, 158, 11, 0.05)); border: 1px solid rgba(245, 158, 11, 0.3); color: #F59E0B;">
                            <x-icon name="target" class="w-6 h-6" />
                        </div>
                        <h3 class="font-display font-bold text-lg mb-2" style="color: var(--color-text);">Diagnóstico Dinámico CEFR</h3>
                        <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                            Examen institucional de 75 preguntas estructurado de A1 a C1 que calibra tu punto de partida en el mapa académico.
                        </p>
                    </div>

                    {{-- Feature 4 --}}
                    <div class="solid-card p-7 hover:-translate-y-1 transition duration-300">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5 shadow-sm"
                             style="background: linear-gradient(135deg, rgba(2, 132, 199, 0.15), rgba(2, 132, 199, 0.05)); border: 1px solid rgba(2, 132, 199, 0.3); color: #0284C7;">
                            <x-icon name="headphones" class="w-6 h-6" />
                        </div>
                        <h3 class="font-display font-bold text-lg mb-2" style="color: var(--color-text);">Laboratorio de Listening</h3>
                        <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                            Catálogo completo con audios reales, síntesis vocal de alta fidelidad y transcripciones interactivas por subnivel.
                        </p>
                    </div>

                    {{-- Feature 5 --}}
                    <div class="solid-card p-7 hover:-translate-y-1 transition duration-300">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5 shadow-sm"
                             style="background: linear-gradient(135deg, rgba(234, 88, 12, 0.15), rgba(234, 88, 12, 0.05)); border: 1px solid rgba(234, 88, 12, 0.3); color: #EA580C;">
                            <x-icon name="flame" class="w-6 h-6" />
                        </div>
                        <h3 class="font-display font-bold text-lg mb-2" style="color: var(--color-text);">Gamificación & Rachas</h3>
                        <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                            Gana puntos de experiencia (XP), mantén encendida tu racha de constancia diaria y sube de nivel de maestría.
                        </p>
                    </div>

                    {{-- Feature 6 --}}
                    <div class="solid-card p-7 hover:-translate-y-1 transition duration-300">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5 shadow-sm"
                             style="background: linear-gradient(135deg, rgba(168, 85, 247, 0.15), rgba(168, 85, 247, 0.05)); border: 1px solid rgba(168, 85, 247, 0.3); color: #A855F7;">
                            <x-icon name="gem" class="w-6 h-6" />
                        </div>
                        <h3 class="font-display font-bold text-lg mb-2" style="color: var(--color-text);">Dominio Progresivo CEFR</h3>
                        <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                            Avanza progresivamente desbloqueando subniveles tras superar lectura, audición y speaking.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- CEFR Levels Preview --}}
        <section class="py-16 px-4 sm:px-6 lg:px-8">
            <div class="max-w-5xl mx-auto">
                <div class="glass-card p-8 sm:p-12 border relative overflow-hidden"
                     style="border-color: var(--color-glass-border);">
                    <div class="text-center mb-10">
                        <span class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Estándar Internacional</span>
                        <h2 class="font-display font-extrabold text-2xl sm:text-3xl mt-2" style="color: var(--color-text);">
                            Marco Común Europeo de Referencia (CEFR)
                        </h2>
                        <p class="mt-2 text-sm" style="color: var(--color-text-secondary);">
                            Avanza progresivamente a través de cada nivel con objetivos claros de comunicación.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
                        @php
                            $cefrLevels = [
                                ['level' => 'A1', 'name' => 'Beginner', 'color' => '#10B981', 'glow' => 'rgba(16, 185, 129, 0.3)'],
                                ['level' => 'A2', 'name' => 'Elementary', 'color' => '#06B6D4', 'glow' => 'rgba(6, 182, 212, 0.3)'],
                                ['level' => 'B1', 'name' => 'Intermediate', 'color' => '#6366F1', 'glow' => 'rgba(99, 102, 241, 0.3)'],
                                ['level' => 'B2', 'name' => 'Upper-Inter.', 'color' => '#8B5CF6', 'glow' => 'rgba(139, 92, 246, 0.3)'],
                                ['level' => 'C1', 'name' => 'Advanced', 'color' => '#F59E0B', 'glow' => 'rgba(245, 158, 11, 0.3)'],
                                ['level' => 'C2', 'name' => 'Mastery', 'color' => '#EC4899', 'glow' => 'rgba(236, 72, 153, 0.3)'],
                            ];
                        @endphp
                        @foreach ($cefrLevels as $item)
                            <div class="text-center p-5 rounded-2xl transition-all duration-300 hover:-translate-y-1 border"
                                 style="background: color-mix(in srgb, {{ $item['color'] }} 8%, var(--color-card)); border-color: color-mix(in srgb, {{ $item['color'] }} 30%, transparent);">
                                <span class="text-2xl font-black font-display block" style="color: {{ $item['color'] }};">{{ $item['level'] }}</span>
                                <p class="text-xs font-bold mt-1.5 uppercase tracking-wide" style="color: var(--color-text-secondary);" lang="en">{{ $item['name'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Final Call to Action --}}
        <section class="py-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto text-center">
                <div class="glass-card p-10 sm:p-16 border relative overflow-hidden"
                     style="border-color: color-mix(in srgb, var(--color-primary) 40%, transparent); box-shadow: 0 20px 50px -15px var(--glow-primary);">
                    
                    <x-application-logo class="w-16 h-16 mx-auto mb-6 animate-float" />
                    
                    <h2 class="font-display font-black text-3xl sm:text-4xl mb-4" style="color: var(--color-text);">
                        Comienza tu viaje bilingüe hoy mismo
                    </h2>
                    <p class="text-base sm:text-lg mb-8 max-w-xl mx-auto" style="color: var(--color-text-secondary);">
                        Accede con tu cuenta institucional de la Universidad Tecnológica de Puebla y realiza tu test diagnóstico en minutos.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn-lumina btn-3d-green w-full sm:w-auto px-8 py-4 text-base font-bold shadow-lg">
                                Ir a mi panel de estudiante
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="btn-lumina btn-3d-green w-full sm:w-auto px-8 py-4 text-base font-bold shadow-lg">
                                Crear mi cuenta UTBIS
                            </a>
                            <a href="{{ route('login') }}" class="btn-secondary w-full sm:w-auto px-8 py-4 text-base font-bold">
                                Iniciar sesión
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer class="py-8 px-4 border-t text-center text-xs" style="border-color: var(--color-border); color: var(--color-text-secondary);">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
            <div>
                &copy; {{ date('Y') }} Universidad Tecnológica de Puebla · UTBIS Bilingüe Internacional y Sustentable
            </div>
            <div class="font-mono text-[11px]">
                Desarrollado con Laravel 12 + Google Gemini 2.0 Flash AI
            </div>
        </div>
    </footer>
</body>
</html>
