<x-app-layout title="Dashboard">
    @php
        $displayName = trim(implode(' ', array_filter([
            auth()->user()->user_name,
            auth()->user()->user_last_name,
        ])));

        $currentCefr = $levelInfo['cefr_level'] ?? 'A1';
        $cefrDescriptions = [
            'A1' => 'Principiante · Breakthrough',
            'A2' => 'Elemental · Waystage',
            'B1' => 'Intermedio · Threshold',
            'B2' => 'Intermedio Alto · Vantage',
            'C1' => 'Avanzado · Effective Proficiency',
            'C2' => 'Maestría · Mastery',
        ];

        // Bocado del día de inglés
        $todayBite = [
            'phrase' => 'Break the ice',
            'ipa' => '/breɪk ðiː aɪs/',
            'meaning' => 'Iniciar una conversación de forma amigable para romper la tensión o timidez inicial.',
            'example' => 'He told a funny joke to break the ice before the presentation started.',
            'tag' => 'Idiom of the Day',
        ];
    @endphp

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            {{-- Notificación de Retake de Diagnóstico --}}
            @if (session('status') === 'placement-retake-started')
                <div class="rounded-2xl p-4 text-xs sm:text-sm font-semibold border flex items-center justify-between gap-4 animate-fade-up"
                     style="background: var(--color-success-surface); border-color: var(--color-success-border); color: var(--color-success-text);"
                     role="status">
                    <span>Has iniciado un nuevo intento de diagnóstico. ¡Mucho éxito en tu evaluación!</span>
                    <a href="{{ route('placement.index') }}" class="btn-duo btn-duo-green text-xs py-1.5 px-3">
                        Ir al examen
                    </a>
                </div>
            @endif

            {{-- ═══════════════════════════════════════════════════════════════
                 DISPOSICIÓN PRINCIPAL (DUOLINGO LEARNING PATH + EF STATIONS)
                 ═══════════════════════════════════════════════════════════════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                
                {{-- COLUMNA IZQUIERDA Y CENTRAL (2 COLUMNAS EN DESKTOP) --}}
                <div class="lg:col-span-2 space-y-8">
                    
                    {{-- HÉROE DE UNIDAD ACTUAL (EF ENGLISH + DUOLINGO FUSION) --}}
                    <section class="ef-unit-card border-2 relative overflow-hidden animate-fade-up"
                              style="border-color: color-mix(in srgb, var(--color-primary) 35%, var(--color-border));">
                        
                        {{-- Ambient Glow --}}
                        <div class="absolute -right-12 -bottom-12 w-60 h-60 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                            <div class="flex items-start gap-4 min-w-0">
                                <div class="relative shrink-0">
                                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-3xl flex items-center justify-center p-2.5 border-2 shadow-lg"
                                         style="background: color-mix(in srgb, var(--color-primary) 12%, var(--color-card)); border-color: var(--color-primary);">
                                        <img src="{{ asset('img/buho.png') }}" alt="Búho tutor" class="w-full h-full object-contain animate-float">
                                    </div>
                                    <span class="absolute -bottom-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500 text-white shadow-sm border-2 border-white dark:border-slate-900">
                                        <x-icon name="zap" class="w-3 h-3 text-white" />
                                    </span>
                                </div>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                        <span class="ef-cefr-badge">
                                            Nivel {{ $currentCefr }}
                                        </span>
                                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                            {{ $cefrDescriptions[$currentCefr] ?? 'Ruta Formativa' }}
                                        </span>
                                    </div>

                                    <h1 class="font-display font-black text-2xl sm:text-3xl tracking-tight leading-tight" style="color: var(--color-text);">
                                        ¡Hello, {{ $displayName }}!
                                    </h1>
                                    
                                    <p class="mt-1 text-xs sm:text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                                        @if ($nextLesson)
                                            Tu siguiente objetivo formativo es: 
                                            <span class="font-bold font-display text-emerald-600 dark:text-emerald-400">"{{ $nextActivityName }}"</span>.
                                        @else
                                            ¡Has completado las lecciones disponibles en tu ruta actual! Repasa o practica con el tutor.
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Progreso de la Unidad & Botón 3D Duolingo --}}
                        <div class="mt-6 pt-6 border-t flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4"
                             style="border-color: var(--color-border);">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between text-xs font-bold mb-2">
                                    <span style="color: var(--color-text);">Dominio de la Ruta</span>
                                    <span class="font-mono text-emerald-600 dark:text-emerald-400">{{ $completedCount }}/{{ $totalLessons }} ({{ $completionPct }}%)</span>
                                </div>
                                <div class="progress-bar h-3" role="progressbar" aria-valuenow="{{ $completionPct }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar-fill" style="width: {{ $completionPct }}%;"></div>
                                </div>
                            </div>

                            <a href="{{ $nextActivityUrl }}" class="btn-duo btn-duo-green shrink-0 shadow-md">
                                <span>{{ $nextLesson ? 'Continuar Lección' : 'Explorar Mapa' }}</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                            </a>
                        </div>
                    </section>

                    {{-- MISIONES DIARIAS (DUOLINGO DAILY QUESTS) --}}
                    <section class="solid-card p-6 border space-y-4 animate-fade-up" style="border-color: var(--color-card-border);">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-emerald-500/10 text-emerald-500">
                                    <x-icon name="target" class="w-5 h-5" />
                                </div>
                                <div>
                                    <h2 class="font-display font-extrabold text-base sm:text-lg" style="color: var(--color-text);">
                                        Misiones Diarias
                                    </h2>
                                    <p class="text-xs" style="color: var(--color-text-secondary);">
                                        Completa tus metas de hoy para maximizar tu retención
                                    </p>
                                </div>
                            </div>
                            <span class="text-xs font-mono font-bold px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                Hoy: {{ $gamification['today_xp'] ?? 0 }} XP
                            </span>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            {{-- Misión 1: Racha --}}
                            <div class="p-4 rounded-2xl border flex flex-col justify-between"
                                 style="background: var(--color-bg); border-color: var(--color-border);">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--color-text-secondary);">Protege tu Racha</span>
                                    <x-icon name="flame" class="w-4 h-4 text-orange-500 animate-pulse" />
                                </div>
                                <div class="font-display font-black text-lg text-orange-500">
                                    {{ $streak }} {{ $streak === 1 ? 'Día' : 'Días' }}
                                </div>
                                <div class="mt-2 progress-bar h-2">
                                    <div class="progress-bar-fill" style="width: 100%; background: #FB923C;"></div>
                                </div>
                                <span class="text-[11px] mt-1.5" style="color: var(--color-text-secondary);">Racha activa hoy</span>
                            </div>

                            {{-- Misión 2: Meta XP --}}
                            @php
                                $todayXp = $gamification['today_xp'] ?? 0;
                                $xpGoal = 50;
                                $xpPct = min(100, (int) round(($todayXp / $xpGoal) * 100));
                            @endphp
                            <div class="p-4 rounded-2xl border flex flex-col justify-between"
                                 style="background: var(--color-bg); border-color: var(--color-border);">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--color-text-secondary);">Meta Diaria (50 XP)</span>
                                    <x-icon name="zap" class="w-4 h-4 text-amber-500" />
                                </div>
                                <div class="font-display font-black text-lg text-amber-500">
                                    {{ $todayXp }} / {{ $xpGoal }} XP
                                </div>
                                <div class="mt-2 progress-bar h-2">
                                    <div class="progress-bar-fill" style="width: {{ $xpPct }}%; background: #F59E0B;"></div>
                                </div>
                                <span class="text-[11px] mt-1.5 font-mono" style="color: var(--color-text-secondary);">{{ $xpPct }}% completado</span>
                            </div>

                            {{-- Misión 3: Lección del día --}}
                            <div class="p-4 rounded-2xl border flex flex-col justify-between"
                                 style="background: var(--color-bg); border-color: var(--color-border);">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--color-text-secondary);">Lección Formativa</span>
                                    <x-icon name="book-open" class="w-4 h-4 text-emerald-500" />
                                </div>
                                <div class="font-display font-black text-lg text-emerald-500">
                                    {{ $completedCount }} Completadas
                                </div>
                                <div class="mt-2 progress-bar h-2">
                                    <div class="progress-bar-fill" style="width: {{ $completionPct }}%;"></div>
                                </div>
                                <span class="text-[11px] mt-1.5" style="color: var(--color-text-secondary);">Avanza en tu ruta CEFR</span>
                            </div>
                        </div>
                    </section>

                    {{-- HUB DE HABILIDADES EF ENGLISH (READING, LISTENING, SPEAKING) --}}
                    <section class="space-y-4 animate-fade-up">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="font-display font-extrabold text-lg sm:text-xl" style="color: var(--color-text);">
                                    Estaciones de Habilidad
                                </h2>
                                <p class="text-xs" style="color: var(--color-text-secondary);">
                                    Práctica guiada en los cuatro pilares del marco CEFR
                                </p>
                            </div>
                            <a href="{{ route('levels.index') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                                Ver todas →
                            </a>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            {{-- Reading Hub --}}
                            <a href="{{ route('levels.index') }}"
                               class="solid-card p-5 border flex flex-col justify-between transition-all duration-200 hover:-translate-y-1 hover:border-emerald-500/50 group">
                                <div>
                                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl mb-3 border"
                                         style="background: rgba(16, 185, 129, 0.12); color: #059669; border-color: rgba(16, 185, 129, 0.25);">
                                        <x-icon name="book-open" class="w-6 h-6" />
                                    </div>
                                    <span class="ef-skill-pill ef-skill-reading text-[10px] mb-2">Reading & Grammar</span>
                                    <h3 class="font-display font-bold text-base mt-1 group-hover:text-emerald-500 transition" style="color: var(--color-text);">
                                        Lectura & Sintaxis
                                    </h3>
                                    <p class="text-xs mt-1 leading-relaxed" style="color: var(--color-text-secondary);">
                                        Textos contextuales, análisis gramatical y vocabulario por niveles.
                                    </p>
                                </div>
                                <div class="mt-4 pt-3 border-t flex items-center justify-between text-xs font-bold text-emerald-600 dark:text-emerald-400"
                                     style="border-color: var(--color-border);">
                                    <span>Iniciar lectura</span>
                                    <span>→</span>
                                </div>
                            </a>

                            {{-- Listening Hub --}}
                            <a href="{{ route('listening.index') }}"
                               class="solid-card p-5 border flex flex-col justify-between transition-all duration-200 hover:-translate-y-1 hover:border-sky-500/50 group">
                                <div>
                                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl mb-3 border"
                                         style="background: rgba(2, 132, 199, 0.12); color: #0284C7; border-color: rgba(2, 132, 199, 0.25);">
                                        <x-icon name="headphones" class="w-6 h-6" />
                                    </div>
                                    <span class="ef-skill-pill ef-skill-listening text-[10px] mb-2">Listening Studio</span>
                                    <h3 class="font-display font-bold text-base mt-1 group-hover:text-sky-500 transition" style="color: var(--color-text);">
                                        Comprensión Auditiva
                                    </h3>
                                    <p class="text-xs mt-1 leading-relaxed" style="color: var(--color-text-secondary);">
                                        Audios nativos, control de velocidad, transcripciones y tests.
                                    </p>
                                </div>
                                <div class="mt-4 pt-3 border-t flex items-center justify-between text-xs font-bold text-sky-600 dark:text-sky-400"
                                     style="border-color: var(--color-border);">
                                    <span>Escuchar audios</span>
                                    <span>→</span>
                                </div>
                            </a>

                            {{-- Speaking Hub --}}
                            <a href="{{ route('chat.index') }}"
                               class="solid-card p-5 border flex flex-col justify-between transition-all duration-200 hover:-translate-y-1 hover:border-amber-500/50 group">
                                <div>
                                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl mb-3 border"
                                         style="background: rgba(245, 158, 11, 0.12); color: #D97706; border-color: rgba(245, 158, 11, 0.25);">
                                        <x-icon name="mic" class="w-6 h-6" />
                                    </div>
                                    <span class="ef-skill-pill ef-skill-speaking text-[10px] mb-2">Speaking & AI Tutor</span>
                                    <h3 class="font-display font-bold text-base mt-1 group-hover:text-amber-500 transition" style="color: var(--color-text);">
                                        Conversación con IA
                                    </h3>
                                    <p class="text-xs mt-1 leading-relaxed" style="color: var(--color-text-secondary);">
                                        Roleplay situacional, práctica de pronunciación y feedback continuo.
                                    </p>
                                </div>
                                <div class="mt-4 pt-3 border-t flex items-center justify-between text-xs font-bold text-amber-600 dark:text-amber-400"
                                     style="border-color: var(--color-border);">
                                    <span>Hablar con Búho</span>
                                    <span>→</span>
                                </div>
                            </a>
                        </div>
                    </section>
                </div>

                {{-- ══════════════════════════════════════════════════════════
                     COLUMNA DERECHA (COMPANION WIDGET DUOLINGO + EF ENGLISH)
                     ══════════════════════════════════════════════════════════ --}}
                <div class="space-y-6">
                    
                    {{-- TIP / EXPRESIÓN DIARIA DEL BÚHO CON AUDIO TTS VOZ FEMENINA --}}
                    <section class="solid-card p-6 border relative overflow-hidden animate-fade-up"
                             style="border-color: var(--color-card-border);"
                             x-data="{
                                 speaking: false,
                                 playPhrase() {
                                     if (this.speaking) {
                                         window.AIVoice?.stop();
                                         this.speaking = false;
                                         return;
                                     }
                                     const text = '{{ $todayBite['phrase'] }}. {{ $todayBite['example'] }}';
                                     this.speaking = true;
                                     window.AIVoice?.speak(text, {
                                         lang: 'en-US',
                                         rate: 0.92,
                                         pitch: 1.08,
                                         onStart: () => { this.speaking = true; },
                                         onEnd: () => { this.speaking = false; },
                                         onError: () => { this.speaking = false; }
                                     });
                                 }
                             }">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <x-icon name="lightbulb" class="w-4 h-4 text-emerald-500" />
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                    English Bite of the Day
                                </span>
                            </div>
                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                {{ $todayBite['tag'] }}
                            </span>
                        </div>

                        <div class="mt-2">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="font-display font-black text-xl" style="color: var(--color-text);">
                                    "{{ $todayBite['phrase'] }}"
                                </h3>
                                <button type="button"
                                        @click="playPhrase()"
                                        class="w-11 h-11 rounded-2xl flex items-center justify-center border transition-all duration-150 hover:scale-105"
                                        :class="speaking ? 'bg-emerald-500 text-white shadow-glow' : 'bg-card text-primary'"
                                        style="border-color: var(--color-control-border);"
                                        aria-label="Escuchar pronunciación con voz femenina de IA">
                                    <template x-if="speaking">
                                        <img src="{{ asset('img/soundwave.svg') }}" class="w-5 h-4 inline-block" alt="Reproduciendo audio">
                                    </template>
                                    <template x-if="!speaking">
                                        <x-icon name="play" class="w-4 h-4 text-emerald-500" />
                                    </template>
                                </button>
                            </div>
                            <p class="font-mono text-xs text-slate-400 mt-0.5">{{ $todayBite['ipa'] }}</p>
                        </div>

                        <div class="mt-3 p-3 rounded-2xl border text-xs" style="background: var(--color-bg); border-color: var(--color-border);">
                            <div class="flex items-start gap-1.5">
                                <x-icon name="lightbulb" class="w-3.5 h-3.5 text-amber-500 shrink-0 mt-0.5" />
                                <p class="font-semibold" style="color: var(--color-text);">{{ $todayBite['meaning'] }}</p>
                            </div>
                            <p class="mt-2 italic" style="color: var(--color-text-secondary);">"{{ $todayBite['example'] }}"</p>
                        </div>
                    </section>

                    {{-- RESUMEN CEFR ACADÉMICO EF ENGLISH --}}
                    <section class="solid-card p-6 border space-y-4 animate-fade-up"
                             style="border-color: var(--color-card-border); animation-delay: 0.1s;">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <x-icon name="trophy" class="w-4 h-4 text-amber-500" />
                                <h3 class="font-display font-extrabold text-sm uppercase tracking-wider" style="color: var(--color-text);">
                                    Marco Académico CEFR
                                </h3>
                            </div>
                            <span class="ef-cefr-badge text-xs">{{ $currentCefr }}</span>
                        </div>

                        <p class="text-xs leading-relaxed" style="color: var(--color-text-secondary);">
                            Tu nivel actual está alineado al estándar internacional CEFR. Al completar las unidades desbloquearás tu examen de nivel.
                        </p>

                        <div class="space-y-2">
                            <div class="flex justify-between text-xs font-bold">
                                <span style="color: var(--color-text);">Nivel {{ $levelInfo['level'] }} Gamificado</span>
                                <span class="font-mono text-amber-500">{{ $levelInfo['current'] }}/{{ $levelInfo['needed'] }} XP</span>
                            </div>
                            <div class="progress-bar h-2.5">
                                <div class="progress-bar-fill" style="width: {{ $levelInfo['progress'] }}%; background: linear-gradient(90deg, #F59E0B, #D97706);"></div>
                            </div>
                            <p class="text-[11px] text-right font-mono" style="color: var(--color-text-secondary);">
                                Falta {{ $levelInfo['needed'] - $levelInfo['current'] }} XP para nivel {{ $levelInfo['level'] + 1 }}
                            </p>
                        </div>

                        <a href="{{ route('placement.index') }}"
                           class="btn-duo btn-duo-outline w-full text-xs py-2.5 flex items-center justify-center">
                            <span>Reevaluar Nivel / Diagnóstico</span>
                        </a>
                    </section>

                    {{-- ACCESOS RÁPIDOS & DIAGNÓSTICO --}}
                    <div class="solid-card p-5 border flex items-center justify-between gap-4 animate-fade-up"
                         style="border-color: var(--color-card-border); animation-delay: 0.2s;">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center bg-indigo-500/10 text-indigo-500">
                                <x-icon name="bot" class="w-5 h-5" />
                            </div>
                            <div>
                                <h4 class="font-display font-bold text-sm" style="color: var(--color-text);">Tutor IA Copilot</h4>
                                <p class="text-[11px]" style="color: var(--color-text-secondary);">Disponible 24/7 con voz femenina</p>
                            </div>
                        </div>
                        <a href="{{ route('chat.index') }}" class="btn-duo btn-duo-indigo text-xs py-2 px-3.5">
                            Chatear
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
