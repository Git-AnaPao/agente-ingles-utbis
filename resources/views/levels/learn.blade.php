<x-app-layout title="Estación de Lección">
    @php
        $skillMeta = [
            'reading' => ['label' => 'Reading', 'icon' => 'book-open'],
            'writing' => ['label' => 'Writing', 'icon' => 'pencil'],
            'listening' => ['label' => 'Listening', 'icon' => 'headphones'],
            'speaking' => ['label' => 'Speaking', 'icon' => 'mic'],
        ];

        $questions = $listeningLesson->questionnaire?->questions ?? collect();
        $practiceBySkill = collect(['reading', 'writing', 'listening'])->mapWithKeys(function (string $skill) use ($questions) {
            $items = $questions
                ->filter(fn ($question) => $question->question_skill_type === $skill && $question->question_type !== 'speaking')
                ->values()
                ->map(fn ($question) => [
                    'id' => $question->question_id,
                    'type' => $question->question_type,
                    'text' => $question->question_text,
                    'options' => $question->options
                        ->sortBy('option_order')
                        ->values()
                        ->map(fn ($option) => ['id' => $option->option_id, 'text' => $option->option_text])
                        ->all(),
                ]);

            return [$skill => $items->all()];
        });

        $currentNumber = collect($lessonPath)->firstWhere('current', true)['number'] ?? null;
        $mapUrl = route('levels.index').'#level-'.$listeningLesson->cefr_level;

        $config = [
            'practiceBySkill' => $practiceBySkill,
            'availableSkills' => $availableSkills,
            'requiredSkills' => $requiredSkills,
            'masteredSkills' => $masteredSkills,
            'initialTab' => $activeTab,
            'checkUrl' => route('lessons.check-practice', $listeningLesson),
            'speakingFeedbackUrl' => route('lessons.speaking-feedback', $listeningLesson),
            'mapUrl' => $mapUrl,
            'nextLessonUrl' => $nextLessonUrl,
            'xpTotal' => (int) (auth()->user()->xp ?? 0),
            'streak' => (int) ($gamification['current_streak'] ?? 0),
        ];

        $exitWarningMessage = 'Si sales o cambias de página antes de completar la lección, tu avance no se guardará. ¿Deseas salir de todas formas?';
    @endphp

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8"
             x-data="lessonStation(@js($config))"
             x-init="init()"
             data-lesson-station-root>

            {{-- Barra Superior de Salida y Gamificación --}}
            <div class="flex items-center justify-between gap-4">
                <a href="{{ $mapUrl }}"
                   class="btn-duo btn-duo-outline text-xs py-2 px-3.5 inline-flex items-center gap-2"
                   aria-label="Volver al mapa">
                    <x-icon name="x" class="w-3.5 h-3.5" />
                    <span class="hidden sm:inline">Mapa CEFR</span>
                </a>

                <div class="flex items-center gap-2">
                    <span class="gamification-pill border-amber-500/30 text-amber-600 dark:text-amber-400">
                        <x-icon name="gem" class="w-4 h-4 text-amber-500" />
                        <span x-text="xpTotal" class="font-mono font-black">{{ $config['xpTotal'] }}</span>
                        <span class="text-[10px] uppercase">XP</span>
                    </span>
                    <span class="gamification-pill border-orange-500/30 text-orange-600 dark:text-orange-400">
                        <x-icon name="flame" class="w-4 h-4 text-orange-500 animate-pulse" />
                        <span x-text="streak" class="font-mono font-black">{{ $config['streak'] }}</span>
                    </span>
                </div>
                <p class="sr-only" role="status" aria-live="polite" x-text="statusMessage"></p>
            </div>

            {{-- Cabecera: el título aparece UNA sola vez --}}
            <header class="ef-unit-card border-2 relative overflow-hidden animate-fade-up"
                    style="border-color: color-mix(in srgb, var(--color-primary) 30%, var(--color-border));">
                <div class="flex items-center gap-2">
                    <span class="ef-cefr-badge font-mono">{{ $listeningLesson->cefr_level }}</span>
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        Lección{{ $currentNumber ? ' #'.$currentNumber : '' }} de la unidad
                    </span>
                </div>
                <h1 class="mt-2 font-display text-2xl sm:text-3xl font-black leading-tight" style="color: var(--color-text);">
                    {{ $title }}
                </h1>
            </header>

            {{-- Sendero horizontal de lecciones de la unidad: bloqueo estricto entre lecciones --}}
            @if (count($lessonPath) > 1)
                <nav class="flex gap-2 overflow-x-auto pb-1 scrollbar-none" aria-label="Lecciones de la unidad">
                    @foreach ($lessonPath as $node)
                        @php $ll = $node['listeningLesson']; @endphp
                        @if ($node['current'])
                            <span class="px-3.5 py-2 rounded-xl text-xs font-bold shrink-0 border-2 inline-flex items-center gap-1.5 bg-emerald-500 text-white border-emerald-500 shadow-md"
                                  aria-current="page">
                                <span class="font-mono text-[11px]">#{{ $node['number'] }}</span>
                                <span class="max-w-[140px] truncate">{{ $ll->title }}</span>
                            </span>
                        @elseif ($node['completed'] || $node['unlocked'])
                            <a href="{{ route('lessons.learn', $ll) }}"
                               class="px-3.5 py-2 rounded-xl text-xs font-bold shrink-0 border inline-flex items-center gap-1.5 hover:border-emerald-500 transition-colors"
                               style="background: var(--color-card); border-color: var(--color-border); color: var(--color-text-secondary);">
                                <span class="font-mono text-[11px]">#{{ $node['number'] }}</span>
                                @if ($node['completed'])
                                    <x-icon name="check" class="w-3 h-3 text-emerald-500" />
                                @endif
                                <span class="max-w-[140px] truncate">{{ $ll->title }}</span>
                            </a>
                        @else
                            <span class="px-3.5 py-2 rounded-xl text-xs font-bold shrink-0 border inline-flex items-center gap-1.5 opacity-50 cursor-not-allowed"
                                  style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text-secondary);"
                                  title="Completa la lección anterior para desbloquear #{{ $node['number'] }}">
                                <x-icon name="lock" class="w-3 h-3" />
                                <span class="font-mono text-[11px]">#{{ $node['number'] }}</span>
                            </span>
                        @endif
                    @endforeach
                </nav>
            @endif

            {{-- Barra de progreso de ESTA lección --}}
            <div class="glass-card p-4 border" style="border-color: var(--color-glass-border);">
                <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                    <span style="color: var(--color-text);">Progreso de la Lección{{ $currentNumber ? ' #'.$currentNumber : '' }}</span>
                    <span class="font-mono text-emerald-600 dark:text-emerald-400">{{ $activitiesDone }}/{{ $activitiesTotal }} pasos</span>
                </div>
                <div class="progress-bar h-3" role="progressbar" aria-valuenow="{{ $activitiesTotal > 0 ? round($activitiesDone / $activitiesTotal * 100) : 0 }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar-fill" style="width: {{ $activitiesTotal > 0 ? round($activitiesDone / $activitiesTotal * 100) : 0 }}%;"></div>
                </div>
            </div>

            {{-- Pestañas internas: navegación LIBRE dentro de la lección activa --}}
            <nav class="grid grid-cols-4 gap-2 sm:gap-3" aria-label="Habilidades de esta lección">
                @foreach ($skillMeta as $skill => $meta)
                    @php $available = in_array($skill, $availableSkills, true); @endphp
                    @if ($available)
                        <button type="button"
                                @click="activeTab = '{{ $skill }}'"
                                class="btn-duo p-2.5 sm:p-3.5 text-center flex-col gap-1 rounded-2xl h-auto"
                                :class="activeTab === '{{ $skill }}' ? 'btn-duo-green' : 'btn-duo-outline'">
                            <x-icon :name="$meta['icon']" class="w-4 h-4 sm:w-5 sm:h-5 mx-auto mb-0.5" />
                            <span class="block text-[11px] sm:text-xs font-extrabold">{{ $meta['label'] }}</span>
                            <span class="text-[9px] sm:text-[10px] font-mono"
                                  :class="activeTab === '{{ $skill }}' ? 'text-white/90' : 'text-slate-400'"
                                  x-text="masteredSkills.has('{{ $skill }}') ? '✓ Completado' : '{{ in_array($skill, $requiredSkills, true) ? 'Pendiente' : 'Opcional' }}'"></span>
                        </button>
                    @else
                        <div class="btn-duo btn-duo-outline p-2.5 sm:p-3.5 text-center flex-col gap-1 rounded-2xl h-auto opacity-40 cursor-not-allowed">
                            <x-icon :name="$meta['icon']" class="w-4 h-4 sm:w-5 sm:h-5 mx-auto mb-0.5" />
                            <span class="block text-[11px] sm:text-xs font-bold">{{ $meta['label'] }}</span>
                            <span class="text-[9px] sm:text-[10px] font-mono text-slate-400">No disponible</span>
                        </div>
                    @endif
                @endforeach
            </nav>

            {{-- READING --}}
            <template x-if="activeTab === 'reading'">
                <div class="space-y-6 animate-fade-up">
                    <article class="solid-card overflow-hidden border shadow-sm">
                        <header class="flex items-center gap-2.5 border-b px-6 py-4" style="border-color: var(--color-border); background: color-mix(in srgb, var(--color-primary) 5%, var(--color-card));">
                            <x-icon name="book-open" class="w-4 h-4 text-emerald-500" />
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Material de Lectura</span>
                        </header>
                        <div class="p-6 sm:p-7 whitespace-pre-line text-base leading-relaxed" lang="en" style="color: var(--color-text);">{{ $listeningLesson->reading_text ?: 'Lee el texto y responde las preguntas debajo.' }}</div>
                    </article>
                    @include('levels.partials.skill-quiz', ['skill' => 'reading', 'heading' => 'Cuestionario de Comprensión Lectora'])
                </div>
            </template>

            {{-- WRITING (producción independiente, no depende del texto de lectura) --}}
            <template x-if="activeTab === 'writing'">
                <div class="space-y-6 animate-fade-up">
                    <article class="solid-card overflow-hidden border shadow-sm">
                        <header class="flex items-center gap-2.5 border-b px-6 py-4" style="border-color: var(--color-border); background: color-mix(in srgb, var(--color-primary) 5%, var(--color-card));">
                            <x-icon name="pencil" class="w-4 h-4 text-emerald-500" />
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Producción Escrita</span>
                        </header>
                        <div class="p-6 sm:p-7 text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                            Completa las oraciones usando lo que aprendiste en esta lección o tu propio conocimiento del inglés. No necesitas releer el texto de Reading.
                        </div>
                    </article>
                    @include('levels.partials.skill-quiz', ['skill' => 'writing', 'heading' => 'Ejercicios de Producción Escrita'])
                </div>
            </template>

            {{-- LISTENING --}}
            <template x-if="activeTab === 'listening'">
                <div class="space-y-6 animate-fade-up">
                    <article class="solid-card overflow-hidden border shadow-sm">
                        <header class="flex items-center gap-2.5 border-b px-6 py-4" style="border-color: var(--color-border); background: color-mix(in srgb, var(--color-primary) 5%, var(--color-card));">
                            <x-icon name="headphones" class="w-4 h-4 text-emerald-500" />
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Material de Escucha</span>
                        </header>
                        <div class="space-y-4 p-6 sm:p-7">
                            @if ($listeningLesson->audio_url)
                                <div class="p-4 rounded-2xl border" style="background: var(--color-bg); border-color: var(--color-border);">
                                    <audio src="{{ $listeningLesson->audio_url }}" controls preload="metadata" class="w-full">
                                        Tu navegador no soporta el reproductor de audio.
                                    </audio>
                                </div>
                            @else
                                <p class="rounded-xl p-3 text-xs" style="background: var(--color-bg); color: var(--color-text-secondary);">
                                    Esta actividad utiliza reproducción guiada por síntesis vocal de IA.
                                </p>
                            @endif
                            @if ($listeningLesson->listening_script)
                                <details class="group">
                                    <summary class="inline-flex min-h-10 cursor-pointer items-center gap-2 text-xs font-bold hover:underline text-emerald-600 dark:text-emerald-400">
                                        <span>Mostrar transcripción del audio</span>
                                    </summary>
                                    <div class="mt-3 whitespace-pre-line rounded-2xl p-4 text-sm leading-relaxed border" style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text);">{{ $listeningLesson->listening_script }}</div>
                                </details>
                            @endif
                        </div>
                    </article>
                    @include('levels.partials.skill-quiz', ['skill' => 'listening', 'heading' => 'Cuestionario de Comprensión Auditiva'])
                </div>
            </template>

            {{-- SPEAKING (opcional, evaluado con IA) --}}
            <template x-if="activeTab === 'speaking'">
                <div class="space-y-6 animate-fade-up">
                    <article class="solid-card overflow-hidden border shadow-sm">
                        <header class="flex items-center gap-2.5 border-b px-6 py-4" style="border-color: var(--color-border); background: color-mix(in srgb, var(--color-primary) 5%, var(--color-card));">
                            <x-icon name="mic" class="w-4 h-4 text-amber-500" />
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Modelo a Pronunciar (opcional)</span>
                        </header>
                        <div class="space-y-4 p-6 sm:p-7">
                            <div class="whitespace-pre-line text-base leading-relaxed p-4 rounded-2xl border" lang="en" style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text);">{{ $listeningLesson->speaking_text }}</div>

                            @if (! $geminiConfigured)
                                <p class="text-sm" style="color: var(--color-text-secondary);">
                                    La grabación está visible, pero la clave de IA aún no está configurada.
                                </p>
                            @else
                                <div class="rounded-2xl border p-5" style="background: var(--color-bg); border-color: var(--color-border);">
                                    <div class="flex flex-wrap items-center gap-4">
                                        <button type="button" @click="toggleRecording()" :disabled="speaking.loading"
                                                class="btn-duo px-6 py-3 text-sm inline-flex items-center gap-2"
                                                :class="speaking.recording ? 'btn-duo-orange animate-pulse' : 'btn-duo-indigo'">
                                            <x-icon name="mic" class="w-4 h-4" />
                                            <span x-show="!speaking.recording" x-text="speaking.audioUrl ? 'Grabar de nuevo' : 'Grabar mi pronunciación'"></span>
                                            <span x-show="speaking.recording" class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-white animate-ping"></span>
                                                <span>Detener grabación · <span x-text="formatTime(speaking.elapsed)"></span></span>
                                            </span>
                                        </button>
                                        <p class="text-xs" style="color: var(--color-text-secondary);">Lee el texto en voz alta con claridad (máx. 60s).</p>
                                    </div>

                                    <p x-show="speaking.error" x-text="speaking.error" class="feedback-error mt-3 rounded-2xl border p-3.5 text-sm font-semibold" role="alert"></p>

                                    <div x-show="speaking.audioUrl" class="mt-4 space-y-3 rounded-2xl border p-4" style="background: var(--color-card); border-color: var(--color-border);">
                                        <p class="text-xs font-bold uppercase tracking-wider" style="color: var(--color-text);">Escucha tu grabación antes de enviarla</p>
                                        <audio x-ref="speakingPlayback" :src="speaking.audioUrl" controls preload="metadata" class="w-full"></audio>
                                        <div class="flex flex-wrap gap-3 pt-1">
                                            <button type="button" @click="submitSpeaking()" :disabled="speaking.loading" class="btn-duo btn-duo-green text-xs inline-flex items-center gap-1.5">
                                                <x-icon name="bot" class="w-3.5 h-3.5" />
                                                <span x-show="!speaking.loading">Evaluar con IA</span>
                                                <span x-show="speaking.loading">Analizando audio...</span>
                                            </button>
                                            <button type="button" @click="discardSpeakingRecording()" :disabled="speaking.loading" class="btn-duo btn-duo-outline text-xs">Descartar</button>
                                        </div>
                                    </div>

                                    <div x-show="speaking.result" class="mt-5 space-y-3 rounded-2xl border p-5 shadow-sm" style="background: var(--color-card); border-color: var(--color-border);" aria-live="polite">
                                        <p class="font-display font-extrabold text-base"
                                           :style="speaking.result?.is_correct === true ? 'color: var(--color-success-text)' : (speaking.result?.is_correct === false ? 'color: var(--color-error-text)' : 'color: var(--color-text-secondary)')"
                                           x-text="speaking.result?.is_correct === true ? '🎉 ¡Pronunciación aprobada!' : (speaking.result?.is_correct === false ? 'Inténtalo de nuevo' : 'La respuesta no pudo evaluarse')"></p>
                                        <div class="text-sm rounded-xl p-3 border" style="background: var(--color-bg); border-color: var(--color-border);">
                                            <span class="font-bold block text-xs uppercase tracking-wider text-slate-400">Transcripción de tu voz:</span>
                                            <span lang="en" class="font-mono text-sm mt-1 block" x-text="speaking.result?.transcription || 'Sin transcripción'"></span>
                                        </div>
                                        <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary);" x-text="speaking.result?.feedback"></p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </article>
                </div>
            </template>

            {{-- Navegación de PASOS dentro de la lección activa (nunca cambia de lección) --}}
            <div class="flex items-center justify-between gap-3 pt-2">
                <button type="button" @click="prevStep()" :disabled="stepIndex === 0"
                        class="btn-duo btn-duo-outline text-xs py-2 px-4 disabled:opacity-30 inline-flex items-center gap-1.5">
                    ← Paso anterior
                </button>
                <span class="text-xs font-mono text-slate-400">
                    Paso <span x-text="stepIndex + 1"></span> de <span x-text="availableSkills.length"></span>
                </span>
                <button type="button" @click="nextStep()" :disabled="stepIndex >= availableSkills.length - 1"
                        class="btn-duo btn-duo-outline text-xs py-2 px-4 disabled:opacity-30 inline-flex items-center gap-1.5">
                    Siguiente paso →
                </button>
            </div>

            {{-- Lección completada --}}
            <div x-show="lessonCompleted" x-cloak class="solid-card p-6 sm:p-8 text-center border-2 border-emerald-500/40 bg-emerald-500/5 space-y-4">
                <x-icon name="trophy" class="w-8 h-8 mx-auto text-amber-500" />
                <h3 class="font-display font-black text-xl" style="color: var(--color-text);">🎉 ¡Lección completada!</h3>
                <p class="text-sm" style="color: var(--color-text-secondary);">Dominaste todos los pasos requeridos de esta lección.</p>
                <div class="flex flex-wrap justify-center gap-3 pt-1">
                    <a x-show="nextLessonUrl" :href="nextLessonUrl" class="btn-duo btn-duo-green text-xs">Finalizar lección → Siguiente</a>
                    <a :href="mapUrl" class="btn-duo btn-duo-outline text-xs">Volver al mapa de niveles</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de confirmación de salida (teletransportado a <body> para que el overlay cubra todo el viewport, sin depender de que ningún ancestro tenga transform/filter que rompa position:fixed) --}}
    <template x-teleport="body">
    <div x-data x-show="$store.exitGuard?.open" x-cloak
         class="fixed inset-0 z-[100] flex items-center justify-center p-4"
         style="background: rgba(15, 23, 42, 0.6);">
        <div class="solid-card max-w-sm w-full p-6 space-y-4 border-2" style="border-color: var(--color-border);" @click.outside="$store.exitGuard.cancel()">
            <h3 class="font-display font-bold text-lg" style="color: var(--color-text);">Progreso sin guardar</h3>
            <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary);">{{ $exitWarningMessage }}</p>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" @click="$store.exitGuard.cancel()" class="btn-duo btn-duo-outline text-xs">Seguir en la lección</button>
                <button type="button" @click="$store.exitGuard.confirm()" class="btn-duo btn-duo-orange text-xs">Salir de todas formas</button>
            </div>
        </div>
    </div>
    </template>

    @push('scripts')
    <script>
        function registerLessonStationAlpineComponents() {
            Alpine.store('exitGuard', {
                open: false,
                pendingHref: null,
                cancel() {
                    this.open = false;
                    this.pendingHref = null;
                },
                confirm() {
                    const href = this.pendingHref;
                    this.open = false;
                    this.pendingHref = null;
                    if (window.__lessonBeforeUnload) {
                        window.removeEventListener('beforeunload', window.__lessonBeforeUnload);
                        window.__lessonBeforeUnload = null;
                    }
                    // Deferred so the beforeunload listener removal is fully
                    // committed by the browser before navigation starts —
                    // some browsers still fire it if both happen in the same tick.
                    if (href) setTimeout(() => { window.location.href = href; }, 0);
                },
            });

            Alpine.data('lessonStation', (config) => ({
                practiceBySkill: config.practiceBySkill || {},
                availableSkills: config.availableSkills || [],
                requiredSkills: config.requiredSkills || [],
                masteredSkills: new Set(config.masteredSkills || []),
                activeTab: config.initialTab,
                checkUrl: config.checkUrl,
                speakingFeedbackUrl: config.speakingFeedbackUrl,
                mapUrl: config.mapUrl,
                nextLessonUrl: config.nextLessonUrl,
                xpTotal: config.xpTotal || 0,
                streak: config.streak || 0,
                dirty: false,
                lessonCompleted: false,
                statusMessage: '',

                skillState: {},
                speaking: { recording: false, loading: false, result: null, error: null, elapsed: 0, timer: null, recorder: null, stream: null, chunks: [], mimeType: 'audio/webm', audioBlob: null, audioUrl: null },

                init() {
                    this.availableSkills.forEach((skill) => {
                        this.skillState[skill] = { qIndex: 0, answers: {}, results: null, loading: false, error: null };
                    });

                    window.__lessonBeforeUnload = (e) => {
                        if (this.dirty && !this.lessonCompleted) {
                            e.preventDefault();
                            e.returnValue = '';
                        }
                    };
                    window.addEventListener('beforeunload', window.__lessonBeforeUnload);

                    this._clickGuard = (event) => {
                        if (!this.dirty || this.lessonCompleted) return;
                        const link = event.target.closest('a[href]');
                        if (!link) return;
                        const href = link.getAttribute('href');
                        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
                        event.preventDefault();
                        Alpine.store('exitGuard').pendingHref = link.href;
                        Alpine.store('exitGuard').open = true;
                    };
                    document.addEventListener('click', this._clickGuard, true);
                },

                get stepIndex() {
                    return Math.max(0, this.availableSkills.indexOf(this.activeTab));
                },
                get currentQuestions() {
                    return this.practiceBySkill[this.activeTab] || [];
                },
                get currentState() {
                    return this.skillState[this.activeTab] || { qIndex: 0, answers: {}, results: null, loading: false, error: null };
                },
                get currentQuestion() {
                    return this.currentQuestions[this.currentState.qIndex] ?? null;
                },
                get answeredCount() {
                    const state = this.currentState;
                    return this.currentQuestions.filter((q) => String(state.answers[q.id] ?? '').trim() !== '').length;
                },
                get currentResult() {
                    const state = this.currentState;
                    return state.results && this.currentQuestion ? (state.results.results[this.currentQuestion.id] ?? null) : null;
                },

                prevStep() {
                    const idx = this.stepIndex;
                    if (idx > 0) this.activeTab = this.availableSkills[idx - 1];
                },
                nextStep() {
                    const idx = this.stepIndex;
                    if (idx < this.availableSkills.length - 1) this.activeTab = this.availableSkills[idx + 1];
                },

                nextQuestion() {
                    const state = this.currentState;
                    if (state.qIndex < this.currentQuestions.length - 1) state.qIndex++;
                },
                prevQuestion() {
                    const state = this.currentState;
                    if (state.qIndex > 0) state.qIndex--;
                },
                resetQuestions() {
                    const state = this.currentState;
                    state.qIndex = 0;
                    state.answers = {};
                    state.results = null;
                    state.error = null;
                },
                answerChanged() {
                    this.currentState.error = null;
                    this.dirty = true;
                },
                optionStyle(option) {
                    const result = this.currentResult;
                    if (!this.currentState.results || !result) return '';
                    if (option.id === result.correct_option_id) return 'background:var(--color-success-surface) !important;border-color:var(--color-success-border) !important;color:var(--color-success-text) !important;';
                    if (option.id === result.student_answer && !result.is_correct) return 'background:var(--color-error-surface) !important;border-color:var(--color-error-border) !important;color:var(--color-error-text) !important;';
                    return 'opacity:.6;';
                },

                async verify() {
                    const skill = this.activeTab;
                    const state = this.skillState[skill];
                    state.error = null;

                    if (this.answeredCount !== this.currentQuestions.length) {
                        state.error = 'Responde todas las preguntas antes de verificar.';
                        return;
                    }

                    state.loading = true;
                    try {
                        const response = await fetch(this.checkUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ skill, answers: state.answers }),
                        });
                        const data = await response.json();
                        if (!response.ok) {
                            throw new Error(data.errors?.answers?.[0] || data.error || data.message || 'No se pudo verificar el cuestionario.');
                        }
                        state.results = data;
                        this.applyProgress(data);
                    } catch (error) {
                        state.error = error.message || 'No se pudo verificar el cuestionario.';
                    } finally {
                        state.loading = false;
                    }
                },

                applyProgress(data) {
                    this.dirty = true;
                    if (Number.isFinite(Number(data.total_xp))) this.xpTotal = Number(data.total_xp);
                    if (Number.isFinite(Number(data.streak))) this.streak = Number(data.streak);
                    if (Array.isArray(data.mastered_skills)) this.masteredSkills = new Set(data.mastered_skills);
                    if (data.lesson_completed) this.lessonCompleted = true;
                    const awarded = Number(data.xp_awarded) || 0;
                    this.statusMessage = awarded > 0
                        ? `Ganaste ${awarded} XP. Total ${this.xpTotal} XP.`
                        : 'Progreso actualizado.';
                },

                formatTime(seconds) {
                    const minutes = Math.floor(seconds / 60);
                    const remainder = seconds % 60;
                    return String(minutes).padStart(2, '0') + ':' + String(remainder).padStart(2, '0');
                },

                async toggleRecording() {
                    if (this.speaking.loading) return;
                    if (this.speaking.recording) return this.stopRecording();
                    await this.startRecording();
                },
                async startRecording() {
                    this.speaking.error = null;
                    this.speaking.result = null;

                    if (!navigator.mediaDevices?.getUserMedia || typeof MediaRecorder === 'undefined') {
                        this.speaking.error = 'Tu navegador no permite grabar audio.';
                        return;
                    }

                    try {
                        this.speaking.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                        this.discardSpeakingRecording();
                        const types = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'];
                        const supportedType = types.find((type) => MediaRecorder.isTypeSupported(type));
                        this.speaking.recorder = supportedType
                            ? new MediaRecorder(this.speaking.stream, { mimeType: supportedType })
                            : new MediaRecorder(this.speaking.stream);
                        this.speaking.mimeType = this.speaking.recorder.mimeType || supportedType || 'audio/webm';
                        this.speaking.chunks = [];
                        this.speaking.recorder.addEventListener('dataavailable', (event) => {
                            if (event.data.size > 0) this.speaking.chunks.push(event.data);
                        });
                        this.speaking.recorder.addEventListener('stop', () => this.prepareSpeakingRecording());
                        this.speaking.recorder.start();
                        this.speaking.recording = true;
                        this.speaking.elapsed = 0;
                        this.dirty = true;
                        this.speaking.timer = setInterval(() => {
                            this.speaking.elapsed++;
                            if (this.speaking.elapsed >= 60) this.stopRecording();
                        }, 1000);
                    } catch (error) {
                        this.speaking.error = 'No se pudo acceder al micrófono. Revisa sus permisos.';
                        this.speaking.stream?.getTracks().forEach((track) => track.stop());
                    }
                },
                stopRecording() {
                    if (!this.speaking.recording) return;
                    this.speaking.recording = false;
                    clearInterval(this.speaking.timer);
                    this.speaking.recorder?.stop();
                    this.speaking.stream?.getTracks().forEach((track) => track.stop());
                },
                prepareSpeakingRecording() {
                    if (this.speaking.chunks.length === 0) {
                        this.speaking.error = 'La grabación quedó vacía.';
                        return;
                    }
                    this.speaking.audioBlob = new Blob(this.speaking.chunks, { type: this.speaking.mimeType });
                    this.speaking.audioUrl = URL.createObjectURL(this.speaking.audioBlob);
                },
                discardSpeakingRecording() {
                    if (this.speaking.audioUrl) URL.revokeObjectURL(this.speaking.audioUrl);
                    this.speaking.audioUrl = null;
                    this.speaking.audioBlob = null;
                    this.speaking.chunks = [];
                },
                async submitSpeaking() {
                    if (!this.speaking.audioBlob) {
                        this.speaking.error = 'Primero graba y revisa una respuesta.';
                        return;
                    }

                    this.speaking.loading = true;
                    this.speaking.error = null;
                    try {
                        const audioBase64 = await new Promise((resolve, reject) => {
                            const reader = new FileReader();
                            reader.addEventListener('load', () => resolve(reader.result));
                            reader.addEventListener('error', reject);
                            reader.readAsDataURL(this.speaking.audioBlob);
                        });
                        const response = await fetch(this.speakingFeedbackUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ audio_base64: audioBase64, mime_type: this.speaking.mimeType }),
                        });
                        const data = await response.json();
                        if (!response.ok) {
                            throw new Error(data.errors?.audio_base64?.[0] || data.error || data.message || 'No se pudo evaluar el audio.');
                        }
                        this.speaking.result = data;
                        if (data.evaluated) this.applyProgress(data);
                    } catch (error) {
                        this.speaking.error = error.message || 'No se pudo evaluar el audio.';
                    } finally {
                        this.speaking.loading = false;
                    }
                },
            }));
        }

        if (window.Alpine) {
            registerLessonStationAlpineComponents();
        } else {
            document.addEventListener('alpine:init', registerLessonStationAlpineComponents);
        }
    </script>
    @endpush
</x-app-layout>
