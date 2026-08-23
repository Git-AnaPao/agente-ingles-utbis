<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <title>Examen de Diagnóstico CEFR · Agente Inglés UTBIS</title>
    <script>
        (function () {
            let theme;
            let grayscale = false;

            try {
                theme = localStorage.getItem('theme');
                grayscale = localStorage.getItem('grayscale') === 'true';
            } catch (error) {
                // The system preference remains available when storage is blocked.
            }

            if (theme !== 'light' && theme !== 'dark') {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.setAttribute('data-grayscale', grayscale ? 'true' : 'false');
            document.documentElement.style.colorScheme = theme;
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-950 text-slate-100 min-h-screen relative selection:bg-emerald-500 selection:text-white" x-data="theme">
    @php
        $levelColors = ['A1' => '#10B981', 'A2' => '#059669', 'B1' => '#0284C7', 'B2' => '#6366F1', 'C1' => '#D946EF'];
        $levelNames = ['A1' => 'Principiante', 'A2' => 'Elemental', 'B1' => 'Intermedio', 'B2' => 'Intermedio Alto', 'C1' => 'Avanzado'];
        $passages = [
            'b1_reading' => implode("\n\n", [
                "How many hours do you think you spend sitting every day? A recent survey has shown that many people spend about twelve hours every day sitting in front of a computer, driving to and from work, and watching TV. Add seven hours of sleeping and the total is stunning: nineteen hours of hardly moving.",
                "Sitting for long stretches of time is not healthy. In fact, a study has shown that people who sit a lot typically live two years less than more active people. The findings show that extended periods of sitting are harmful regardless of other time spent exercising or playing sport. Scientists have discovered that extended sitting changes the way the body deals with sugar and, thus, the risk of getting diabetes or heart disease increases for those people who sit all the time.",
                "Scientists at the UK's University of Chester have conducted a simple experiment about the effects of sitting versus standing. They asked ten people who usually spend their days sitting at work to stand for at least three hours a day for a week at their workplace. They wore monitors that checked their heart rate and blood sugar and recorded how much they were moving. At the beginning of the study some of the volunteers were concerned that they would be unable to stand so much, but they were pleasantly surprised—and one woman even said that her back hurt less after standing during work hours.",
                "The results of the study were astonishing. Blood sugar levels fell back to normal levels after a meal far more quickly on the days when the volunteers stood than when they sat. The heart rate monitors also showed that by standing the volunteers were burning more calories.",
            ]),
            'b2_reading' => implode("\n\n", [
                "Many of us love to eat a good piece of chocolate now and again. Unfortunately, chocolate is expected to become more expensive in the next few years. There are not enough cocoa trees in the world right now to meet the demand for chocolate.",
                "As economies in countries like China become stronger, more people are buying and eating chocolate. This makes the price of chocolate go up. Even if Central and South American cocoa bean farmers planted more cocoa trees today, the trees would not be ready to produce cocoa beans for ten years.",
                "Some people might stop buying chocolate if it gets too expensive. Others like Greg Johnson who just bought boxes of chocolate for all his employees, will not stop even if the price rises. \"I will continue to buy chocolate. I might just buy smaller boxes. Chocolate is a wonderful gift because almost everyone smiles when they get a box of chocolates,\" he said outside of a Godiva chocolate store.",
                "Big chocolate companies will either raise the cost of a chocolate bar or make the candy bars a bit smaller for the same price.",
                "Either way, if you are a chocolate fan like me, you might want to buy now before the prices rise.",
            ]),
            'c1_reading' => implode("\n\n", [
                "Modern technology has transformed the way people communicate, collaborate, and access information. Smartphones, instant messaging, and social media platforms have made it possible to remain connected with colleagues, friends, and family at virtually every moment of the day. While this constant connectivity has undoubtedly improved efficiency and convenience, it has also created unexpected challenges that are only now becoming fully understood.",
                "One of the most significant consequences is the gradual disappearance of clear boundaries between work and personal life. Employees often feel obligated to answer emails long after working hours have ended, fearing that delayed responses may be interpreted as a lack of commitment. As a result, many professionals report feeling mentally exhausted even after spending an evening at home, since they are never completely disconnected from their responsibilities.",
                "Researchers have also observed that the continuous flow of notifications may reduce people's ability to concentrate on complex tasks. Every interruption forces the brain to redirect its attention, and although each distraction may seem insignificant, their cumulative effect can substantially reduce productivity. Ironically, tools designed to help people accomplish more often encourage fragmented attention rather than sustained focus.",
                "This does not necessarily mean that technology itself is harmful. Instead, many experts argue that the problem lies in how society has chosen to use it. Organizations that establish clear expectations regarding digital communication often report higher employee satisfaction and lower levels of stress. Likewise, individuals who intentionally schedule periods without digital interruptions frequently experience greater creativity and improved decision-making.",
                "Ultimately, the challenge is not to reject technology but to develop healthier habits that allow people to benefit from its advantages without becoming controlled by it. Achieving this balance may prove to be one of the defining skills of the modern workplace.",
            ]),
        ];
    @endphp

    {{-- Ambient Mesh Glows --}}
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-32 left-1/4 w-[500px] h-[500px] bg-emerald-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 right-10 w-[400px] h-[400px] bg-indigo-500/10 rounded-full blur-3xl"></div>
    </div>

    <main class="relative z-10 px-4 py-8 min-h-screen">
        <div class="mx-auto max-w-5xl">
            
            {{-- Barra de Navegación Superior --}}
            <nav class="mb-6 flex items-center justify-between text-sm font-bold text-slate-300" aria-label="Acciones del examen">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center p-1">
                        <img src="{{ asset('img/buho.png') }}" alt="" class="w-full h-full object-contain">
                    </div>
                    <span class="font-display font-black text-white text-base">UTBIS Placement</span>
                </div>

                <div class="flex items-center gap-3">
                    @if ($history->isNotEmpty())
                        <a href="{{ route('levels.index') }}" class="btn-secondary text-xs px-3.5 py-2">
                            ← Volver al mapa
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" data-loading-text="Saliendo..." class="btn-secondary text-xs px-3.5 py-2">
                            Salir
                        </button>
                    </form>
                </div>
            </nav>

            @foreach (['success', 'info', 'error'] as $messageType)
                @if (session($messageType))
                    <div class="flash-message {{ $messageType === 'error' ? 'flash-error' : 'flash-success' }} mx-auto mb-5 max-w-3xl shadow-lg" role="{{ $messageType === 'error' ? 'alert' : 'status' }}">
                        {{ session($messageType) }}
                    </div>
                @endif
            @endforeach

            @if ($errors->any())
                <div class="flash-message flash-error mx-auto mb-5 max-w-3xl" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- 1. RESULTADOS GUARDADOS --}}
            @if ($resultsData)
                <section class="mx-auto max-w-3xl animate-fade-up">
                    <header class="mb-6 text-center">
                        <span class="text-xs font-bold font-mono uppercase tracking-widest text-emerald-400">Diagnóstico Completado</span>
                        <h1 class="mt-2 font-display text-3xl sm:text-4xl font-black text-white">Nivel Asignado: {{ $resultsData['level'] }}</h1>
                        <p class="mt-2 text-sm text-slate-400">Tu punto de partida óptimo en la ruta de aprendizaje CEFR.</p>
                    </header>

                    <div class="glass-card p-6 sm:p-10 border shadow-2xl relative overflow-hidden"
                         style="background: rgba(15, 23, 42, 0.75); border-color: rgba(255, 255, 255, 0.1);">
                        
                        <div class="flex flex-col items-center gap-6 sm:flex-row sm:justify-center">
                            <div class="flex h-32 w-32 shrink-0 flex-col items-center justify-center rounded-3xl text-white shadow-glow border-2"
                                 style="background: linear-gradient(135deg, {{ $levelColors[$resultsData['level']] ?? '#10B981' }}, #047857); border-color: rgba(255,255,255,0.2);">
                                <span class="font-display text-4xl font-black font-mono">{{ $resultsData['level'] }}</span>
                                <span class="text-xs font-semibold text-white/90 mt-0.5">{{ $levelNames[$resultsData['level']] ?? '' }}</span>
                            </div>
                            
                            <div class="text-center sm:text-left">
                                @if ($resultsData['was_skipped'])
                                    <h2 class="font-display text-2xl font-bold text-white">Comenzarás desde Nivel A1</h2>
                                    <p class="mt-1 text-sm text-slate-400">Elegiste iniciar la ruta formativa sin evaluación previa.</p>
                                @else
                                    <span class="font-display text-4xl font-black block" style="color: {{ $levelColors[$resultsData['level']] ?? '#10B981' }};">{{ $resultsData['score'] }}%</span>
                                    <p class="text-sm font-semibold text-slate-300 mt-1">{{ $resultsData['correct'] }} de {{ $resultsData['total'] }} aciertos en la prueba</p>
                                @endif
                                <p class="mt-2 text-xs font-mono text-slate-400">Registrado el: {{ $resultsData['taken_at'] ?? 'fecha reciente' }}</p>
                            </div>
                        </div>

                        @if (! $resultsData['was_skipped'])
                            <div class="mt-8 space-y-3.5 border-t border-slate-800 pt-6">
                                <h3 class="font-display text-xs font-bold uppercase tracking-wider text-slate-400">Desglose por Nivel CEFR</h3>
                                @foreach ($resultsData['breakdown'] as $cefr => $result)
                                    @php $percentage = $result['total'] > 0 ? round(($result['correct'] / $result['total']) * 100) : 0; @endphp
                                    <div class="flex items-center gap-3">
                                        <span class="font-mono text-xs font-bold w-7" style="color: {{ $levelColors[$cefr] ?? '#94A3B8' }};">{{ $cefr }}</span>
                                        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-800">
                                            <div class="h-full rounded-full transition-all" style="width: {{ $percentage }}%; background: {{ $levelColors[$cefr] ?? '#10B981' }};"></div>
                                        </div>
                                        <span class="w-16 text-right text-xs font-mono font-semibold text-slate-300">{{ $result['correct'] }}/{{ $result['total'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('levels.index').'#level-'.$resultsData['level'] }}" class="btn-lumina btn-3d-green flex-1 px-6 py-3.5 text-center text-sm font-bold shadow-lg">
                                <span>Ingresar al Nivel {{ $resultsData['level'] }}</span>
                                <svg class="w-4 h-4 ml-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                            </a>
                            <form method="POST" action="{{ route('placement.retake') }}" class="sm:w-auto" onsubmit="return window.confirm('¿Quieres iniciar un nuevo intento de diagnóstico?');">
                                @csrf
                                <button type="submit" data-loading-text="Preparando..." class="btn-secondary w-full px-6 py-3.5 text-sm font-bold">Repetir diagnóstico</button>
                            </form>
                        </div>
                    </div>

                    @if ($history->count() > 1)
                        <div class="mt-6 glass-card p-6 border rounded-2xl" style="background: rgba(15, 23, 42, 0.5); border-color: rgba(255, 255, 255, 0.08);">
                            <h3 class="font-display text-xs font-bold uppercase tracking-wider text-slate-400">Historial de Intentos</h3>
                            <div class="mt-3 space-y-2">
                                @foreach ($history as $attempt)
                                    <div class="flex items-center justify-between rounded-xl px-4 py-2.5 text-xs font-mono bg-slate-900/60 border border-slate-800">
                                        <span class="font-bold text-emerald-400">Nivel {{ $attempt['level'] }} · {{ $attempt['was_skipped'] ? 'Omitido' : $attempt['score'].'%' }}</span>
                                        <span class="text-slate-500">{{ $attempt['taken_at'] ?? 'Sin fecha' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>

            {{-- 2. SIN PREGUNTAS --}}
            @elseif ($questions === [])
                <section class="mx-auto max-w-xl text-center animate-fade-up">
                    <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center mx-auto mb-4 p-2">
                        <img src="{{ asset('img/buho.png') }}" alt="" class="w-full h-full object-contain">
                    </div>
                    <h1 class="font-display text-2xl font-bold text-white">Examen no configurado</h1>
                    <div class="glass-card mt-5 p-7 text-sm leading-relaxed border rounded-2xl" style="background: rgba(15, 23, 42, 0.7); border-color: rgba(255, 255, 255, 0.1); color: var(--color-text-secondary);">
                        Aún no hay reactivos publicados para el examen de diagnóstico. Puedes acceder al mapa general directamente.
                    </div>
                </section>

            {{-- 3. MODO EXAMEN / INTRO --}}
            @else
                <section x-data="placementTest(@js($questions), @js(auth()->id()))" x-cloak>
                    
                    {{-- Pantalla de Bienvenida al Test --}}
                    <div x-show="phase === 'intro'" class="mx-auto max-w-3xl animate-fade-up">
                        <header class="mb-6 text-center">
                            <div class="w-20 h-20 rounded-3xl bg-emerald-500/15 border-2 border-emerald-500/30 flex items-center justify-center mx-auto mb-4 p-3 shadow-glow">
                                <img src="{{ asset('img/buho.png') }}" alt="" class="w-full h-full object-contain animate-float">
                            </div>
                            <span class="text-xs font-bold font-mono uppercase tracking-widest text-emerald-400">Evaluación Inicial</span>
                            <h1 class="mt-2 font-display text-3xl sm:text-4xl font-black text-white">Examen de Diagnóstico CEFR</h1>
                            <p class="mt-2 text-sm text-slate-300">Determina con precisión tu nivel de entrada de A1 a C1</p>
                        </header>

                        <div class="glass-card p-6 sm:p-10 border shadow-2xl rounded-3xl"
                             style="background: rgba(15, 23, 42, 0.8); border-color: rgba(255, 255, 255, 0.1);">
                            <h2 class="font-display text-lg font-bold text-white">Instrucciones Importantes</h2>
                            <ul class="mt-4 space-y-3 text-sm text-slate-300">
                                <li class="flex items-start gap-2.5">
                                    <span class="text-emerald-400 font-bold">1.</span>
                                    <span>Consta de <strong>{{ count($questions) }} preguntas</strong> de opción múltiple organizadas por complejidad ascendente.</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="text-emerald-400 font-bold">2.</span>
                                    <span>Tiempo sugerido: <strong>50 a 60 minutos</strong> (el cronómetro es orientativo y no cerrará tu prueba).</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="text-emerald-400 font-bold">3.</span>
                                    <span>Tus respuestas se guardan automáticamente en esta pestaña mientras avanzas.</span>
                                </li>
                            </ul>

                            <div class="mt-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/25">
                                <p class="text-xs font-bold uppercase tracking-wider text-emerald-400">Recomendación</p>
                                <p class="text-xs text-slate-300 mt-0.5">Responde con honestidad y sin traductores para obtener una ruta de aprendizaje adecuada a tu nivel real.</p>
                            </div>

                            <button type="button" @click="startTest()" class="btn-lumina btn-3d-green mt-6 w-full px-6 py-4 text-base font-bold shadow-lg">
                                <span>Comenzar Examen de Diagnóstico</span>
                                <svg class="w-5 h-5 ml-1.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('placement.skip') }}" class="mt-5 text-center" data-confirm-message="¿Omitir el examen y comenzar directamente desde A1?">
                            @csrf
                            <button type="submit" data-loading-text="Guardando..." class="inline-flex min-h-11 items-center px-4 text-xs font-semibold text-slate-400 hover:text-white underline underline-offset-4">
                                Omitir evaluación y comenzar desde nivel A1 (Principiante)
                            </button>
                        </form>
                    </div>

                    {{-- Consola del Examen Activo --}}
                    <div x-show="phase === 'test'">
                        <div x-show="draftRestored" class="mb-4 flex items-center justify-between gap-3 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 px-4 py-2.5 text-xs font-semibold text-white backdrop-blur" role="status" aria-live="polite">
                            <span>✓ Borrador recuperado. Puedes continuar tu sesión con normalidad.</span>
                            <button type="button" @click="draftRestored = false" class="text-lg font-bold text-white/80 hover:text-white">×</button>
                        </div>

                        <p class="sr-only" role="status" aria-live="polite" aria-atomic="true" x-text="statusMessage"></p>

                        {{-- Barra de Estado del Test --}}
                        <header class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h1 class="font-display text-lg font-bold text-white">Examen de Diagnóstico</h1>
                                <p class="text-xs font-mono text-slate-400"><span x-text="answeredCount" class="text-emerald-400 font-bold"></span> de {{ count($questions) }} preguntas respondidas</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <time class="rounded-2xl bg-slate-900/80 border border-slate-700 px-3.5 py-1.5 text-xs font-mono font-bold text-amber-400" aria-label="Tiempo restante estimado" x-text="formatTime(timeLeft)"></time>
                            </div>
                        </header>

                        {{-- Barra de Progreso Global --}}
                        <div class="mb-5 h-2 overflow-hidden rounded-full bg-slate-800" role="progressbar" aria-label="Progreso de preguntas" aria-valuemin="1" :aria-valuemax="total" :aria-valuenow="currentIndex + 1">
                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-teal-500 transition-all duration-300" :style="'width:' + ((currentIndex + 1) / total * 100) + '%'"></div>
                        </div>

                        {{-- Índice de Reactivos --}}
                        <nav class="glass-card mb-5 rounded-2xl p-4 border" style="background: rgba(15, 23, 42, 0.7); border-color: rgba(255, 255, 255, 0.08);" aria-label="Índice de preguntas">
                            <div class="mb-2.5 flex items-center justify-between text-xs font-semibold text-slate-400">
                                <span class="font-bold uppercase tracking-wider text-[11px]">Navegador de Preguntas</span>
                                <span class="text-[11px] font-mono text-emerald-400"><span x-text="answeredCount"></span>/<span x-text="total"></span> completadas</span>
                            </div>
                            <div class="grid grid-cols-5 gap-1.5 sm:grid-cols-10 md:grid-cols-15">
                                @foreach ($questions as $index => $question)
                                    <button type="button"
                                            @click="goTo({{ $index }})"
                                            class="placement-question-link relative flex h-9 w-full items-center justify-center rounded-xl border text-xs font-mono font-bold transition"
                                            :data-state="isAnswered(@js($question['question_id'])) ? 'answered' : 'pending'"
                                            :aria-current="currentIndex === {{ $index }} ? 'step' : null">
                                        <span>{{ $index + 1 }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </nav>

                        {{-- Formulario de Preguntas --}}
                        <form id="placement-form" method="POST" action="{{ route('placement.submit') }}" @submit.prevent="confirmSubmit($event)" :aria-busy="submitting">
                            @csrf
                            @foreach ($questions as $index => $question)
                                <div x-show="currentIndex === {{ $index }}" data-question-panel="{{ $index }}">
                                    @if ($question['passage'])
                                        @php $passageText = $passages[$question['passage']] ?? null; @endphp
                                        <aside class="glass-card mb-4 p-6 border rounded-2xl" style="background: rgba(15, 23, 42, 0.8); border-color: rgba(255, 255, 255, 0.1);" aria-labelledby="passage-label-{{ $index }}">
                                            <span id="passage-label-{{ $index }}" class="text-xs font-mono font-bold uppercase tracking-wider text-emerald-400">Pasaje de Lectura Asociado</span>
                                            @if ($passageText)
                                                <div class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-200" lang="en">{{ $passageText }}</div>
                                            @else
                                                <p class="feedback-error mt-3 rounded-xl border p-3 text-sm">Pasaje no disponible.</p>
                                            @endif
                                        </aside>
                                    @endif

                                    <fieldset class="glass-card min-w-0 p-6 sm:p-8 border rounded-3xl shadow-xl"
                                              style="background: rgba(15, 23, 42, 0.8); border-color: rgba(255, 255, 255, 0.1);">
                                        <legend data-question-focus tabindex="-1" class="w-full">
                                            <span class="mb-4 flex items-center justify-between gap-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg text-xs font-mono font-bold text-white shadow-xs" style="background: {{ $levelColors[$question['level']] ?? '#10B981' }};">{{ $question['id'] }}</span>
                                                    <span class="rounded-lg px-2.5 py-0.5 text-xs font-mono font-bold bg-slate-800 text-slate-300 border border-slate-700">Nivel {{ $question['level'] }}</span>
                                                </div>
                                                <span class="text-xs font-mono text-slate-400">{{ $index + 1 }} de {{ count($questions) }}</span>
                                            </span>
                                            <span class="block font-display text-base sm:text-lg font-bold leading-relaxed text-white" lang="en">{{ $question['question'] }}</span>
                                        </legend>

                                        @if ($question['options'] === [])
                                            <p class="feedback-error mt-4 rounded-xl border p-3 text-sm">Pregunta sin opciones.</p>
                                        @else
                                            <div class="mt-5 space-y-2.5">
                                                @foreach ($question['options'] as $option)
                                                    <label class="placement-option flex min-h-12 cursor-pointer items-center gap-3.5 rounded-2xl border px-4 py-3 transition-all"
                                                           :class="{ 'is-selected': answers[@js($question['question_id'])] === @js($option['id']) }">
                                                        <input type="radio" name="answers[{{ $question['question_id'] }}]" value="{{ $option['id'] }}"
                                                               @change="selectAnswer(@js($question['question_id']), @js($option['id']))"
                                                               :checked="answers[@js($question['question_id'])] === @js($option['id'])"
                                                               class="h-4 w-4 text-emerald-500">
                                                        <span class="text-sm font-medium text-slate-200" lang="en">{{ $option['text'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @endif
                                    </fieldset>
                                </div>
                            @endforeach

                            <p x-show="submitError" x-ref="submitError" x-text="submitError" class="feedback-error mt-4 rounded-2xl border p-3.5 text-xs font-semibold" role="alert" tabindex="-1"></p>

                            {{-- Botones de Navegación del Test --}}
                            <div class="mt-6 flex items-center justify-between gap-3">
                                <button type="button" @click="previous()" :disabled="currentIndex === 0 || submitting" class="btn-secondary text-xs px-4 py-2.5 disabled:opacity-40">Anterior</button>
                                <span class="text-xs font-mono text-slate-400" x-text="(currentIndex + 1) + ' / ' + total"></span>
                                <button type="button" x-show="currentIndex < total - 1" @click="next()" :disabled="submitting" class="btn-secondary text-xs px-5 py-2.5 font-bold">Siguiente →</button>
                                <button type="submit" x-show="currentIndex === total - 1" :disabled="submitting" class="btn-lumina btn-3d-green text-xs px-6 py-2.5 font-bold shadow-md">
                                    <span x-show="!submitting">Finalizar y Enviar (<span x-text="answeredCount"></span>/<span x-text="total"></span>)</span>
                                    <span x-show="submitting">Evaluando respuestas...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            @endif
        </div>
    </main>

    <script>
        function placementTest(questions, userId) {
            const draftVersion = 2;
            const storageKey = `agente-ingles:placement-draft:v${draftVersion}:${userId}`;

            function loadDraft() {
                try {
                    const value = sessionStorage.getItem(storageKey);
                    if (!value) return null;

                    const stored = JSON.parse(value);
                    if (stored?.version !== draftVersion || !stored.answers || typeof stored.answers !== 'object') {
                        sessionStorage.removeItem(storageKey);
                        return null;
                    }

                    const answers = {};
                    questions.forEach(question => {
                        const answer = stored.answers[question.question_id];
                        const isValid = question.options.some(option => String(option.id) === String(answer));
                        if (isValid) answers[question.question_id] = answer;
                    });

                    return { ...stored, answers };
                } catch (error) {
                    return null;
                }
            }

            const draft = loadDraft();

            return {
                phase: draft ? 'test' : 'intro',
                questions,
                total: questions.length,
                currentIndex: Math.min(Math.max(Number(draft?.currentIndex ?? 0), 0), Math.max(questions.length - 1, 0)),
                answers: draft?.answers ?? {},
                timeLeft: Math.max(Number(draft?.timeLeft ?? 55 * 60), 0),
                timer: null,
                draftRestored: Boolean(draft),
                submitError: null,
                statusMessage: '',
                submitting: false,

                get answeredCount() {
                    return this.questions.filter(question => this.isAnswered(question.question_id)).length;
                },

                init() {
                    if (this.phase === 'test') {
                        this.startTimer();
                        this.statusMessage = this.questionAnnouncement(this.currentIndex);
                    }
                },

                startTest() {
                    this.phase = 'test';
                    this.startTimer();
                    this.saveDraft();
                    this.statusMessage = 'Examen iniciado. ' + this.questionAnnouncement(0);
                    this.focusCurrentQuestion();
                },

                startTimer() {
                    clearInterval(this.timer);
                    this.timer = setInterval(() => {
                        if (this.timeLeft > 0) {
                            this.timeLeft--;
                            this.saveDraft();
                        } else {
                            clearInterval(this.timer);
                        }
                    }, 1000);
                },

                saveDraft() {
                    try {
                        sessionStorage.setItem(storageKey, JSON.stringify({
                            version: draftVersion,
                            answers: this.answers,
                            currentIndex: this.currentIndex,
                            timeLeft: this.timeLeft,
                        }));
                    } catch (error) {
                        this.submitError = 'No fue posible guardar el borrador en esta pestaña.';
                    }
                },

                selectAnswer(questionId, optionId) {
                    this.answers[questionId] = optionId;
                    this.submitError = null;
                    this.saveDraft();
                    this.statusMessage = 'Respuesta guardada. ' + this.answeredCount + ' de ' + this.total + ' preguntas respondidas.';
                },

                isAnswered(questionId) {
                    return String(this.answers[questionId] ?? '').trim() !== '';
                },

                questionAnnouncement(index) {
                    const question = this.questions[index];
                    const state = question && this.isAnswered(question.question_id) ? 'Respondida.' : 'Pendiente.';
                    return 'Pregunta ' + (index + 1) + ' de ' + this.total + '. ' + state;
                },

                goTo(index) {
                    if (this.submitting || index < 0 || index >= this.total) return;
                    this.currentIndex = index;
                    this.saveDraft();
                    this.statusMessage = this.questionAnnouncement(index);
                    this.focusCurrentQuestion();
                },

                next() {
                    if (this.currentIndex < this.total - 1) this.goTo(this.currentIndex + 1);
                },

                previous() {
                    if (this.currentIndex > 0) this.goTo(this.currentIndex - 1);
                },

                focusCurrentQuestion() {
                    this.$nextTick(() => {
                        const panel = this.$root.querySelector(`[data-question-panel="${this.currentIndex}"]`);
                        const target = panel?.querySelector('[data-question-focus]');
                        const behavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
                        panel?.scrollIntoView({ behavior, block: 'start' });
                        target?.focus({ preventScroll: true });
                    });
                },

                firstMissingIndex() {
                    return this.questions.findIndex(question => !this.answers[question.question_id]);
                },

                confirmSubmit(event) {
                    const missingIndex = this.firstMissingIndex();
                    if (missingIndex >= 0) {
                        this.currentIndex = missingIndex;
                        this.submitError = 'Faltan ' + (this.total - this.answeredCount) + ' preguntas. Tu borrador permanece guardado.';
                        this.saveDraft();
                        this.statusMessage = this.submitError;
                        this.focusCurrentQuestion();
                        this.$nextTick(() => this.$refs.submitError?.focus({ preventScroll: true }));
                        return;
                    }

                    if (window.confirm('¿Enviar tus respuestas? Después del envío no podrás modificar este intento.')) {
                        clearInterval(this.timer);
                        this.submitting = true;
                        this.statusMessage = 'Enviando respuestas. Espera un momento.';
                        this.saveDraft();
                        this.$nextTick(() => event.target.submit());
                    }
                },

                formatTime(seconds) {
                    const minutes = Math.floor(seconds / 60);
                    const remainder = seconds % 60;
                    return String(minutes).padStart(2, '0') + ':' + String(remainder).padStart(2, '0');
                },

                destroy() {
                    clearInterval(this.timer);
                },
            };
        }

        @if ($resultsData)
            try {
                sessionStorage.removeItem(@js('agente-ingles:placement-draft:v2:'.auth()->id()));
            } catch (error) {
                // The result is already persisted on the server.
            }
        @endif
    </script>
</body>
</html>
