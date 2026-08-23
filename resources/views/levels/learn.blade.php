<x-app-layout title="Estación de Lección">
    @php
        $skillMeta = [
            'reading' => [
                'label' => 'Reading & Grammar',
                'color' => '#10B981',
                'icon' => 'book-open',
            ],
            'listening' => [
                'label' => 'Listening Studio',
                'color' => '#0284C7',
                'icon' => 'headphones',
            ],
            'speaking' => [
                'label' => 'Speaking AI',
                'color' => '#D97706',
                'icon' => 'mic',
            ],
        ];

        $tabContent = $contentLessons->filter(function ($content) use ($activeTab) {
            return match ($activeTab) {
                'reading' => is_string($content->reading_text) && trim($content->reading_text) !== '',
                'listening' => (is_string($content->listening_script) && trim($content->listening_script) !== '') || $content->audio_url,
                'speaking' => is_string($content->speaking_text) && trim($content->speaking_text) !== '',
            };
        })->values();

        $practiceData = $activeTab === 'speaking'
            ? collect()
            : $questionnaires->map(function ($questionnaire) use ($activeTab) {
                $questions = $questionnaire->questions
                    ->filter(function ($question) use ($activeTab) {
                        if ($question->question_type === 'speaking') {
                            return false;
                        }

                        return $activeTab === 'reading'
                            ? in_array($question->question_skill_type, ['reading', 'writing'], true)
                            : $question->question_skill_type === 'listening';
                    })
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

                return [
                    'id' => $questionnaire->questionnaire_id,
                    'title' => $questionnaire->title,
                    'questions' => $questions->all(),
                ];
            })->filter(fn ($questionnaire) => $questionnaire['questions'] !== [])->values();

        $skillUrls = collect(array_keys($skillMeta))
            ->mapWithKeys(fn ($skill) => [$skill => route('lessons.learn', ['lesson' => $lesson, 'tab' => $skill])])
            ->all();
        $lessonMapUrl = route('levels.index').'#level-'.$lesson->lesson_cefr_level;
    @endphp

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            
            {{-- Barra Superior de Salida y Gamificación Duolingo --}}
            <div class="flex items-center justify-between gap-4">
                <a href="{{ route('levels.index').'#level-'.$lesson->lesson_cefr_level }}"
                   class="btn-duo btn-duo-outline text-xs py-2 px-3.5 inline-flex items-center gap-2"
                   aria-label="Volver al mapa">
                    <x-icon name="x" class="w-3.5 h-3.5" />
                    <span class="hidden sm:inline">Mapa CEFR</span>
                </a>

                {{-- XP & Racha Floating Badges --}}
                <div class="flex items-center gap-2">
                    <span class="gamification-pill border-amber-500/30 text-amber-600 dark:text-amber-400">
                        <x-icon name="gem" class="w-4 h-4 text-amber-500" />
                        <span id="lesson-xp-total" class="font-mono font-black">{{ auth()->user()->xp ?? 0 }}</span>
                        <span class="text-[10px] uppercase">XP</span>
                    </span>
                    <span class="gamification-pill border-orange-500/30 text-orange-600 dark:text-orange-400">
                        <x-icon name="flame" class="w-4 h-4 text-orange-500 animate-pulse" />
                        <span id="lesson-streak-total" class="font-mono font-black">{{ $gamification['current_streak'] ?? 0 }}</span>
                    </span>
                </div>
                <p id="lesson-gamification-status" class="sr-only" role="status" aria-live="polite"></p>
            </div>

            {{-- Cabecera de la Unidad Formativa EF English --}}
            <header class="ef-unit-card border-2 relative overflow-hidden animate-fade-up"
                    style="border-color: color-mix(in srgb, var(--color-primary) 30%, var(--color-border));">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="ef-cefr-badge font-mono">
                                {{ $lesson->lesson_cefr_level }}.{{ $lesson->lesson_sub_level }}
                            </span>
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                Módulo de Dominio
                            </span>
                        </div>
                        <h1 class="mt-2 font-display text-2xl sm:text-3xl font-black leading-tight" style="color: var(--color-text);">
                            {{ $title }}
                        </h1>
                        @if ($objective)
                            <p class="mt-1.5 text-xs sm:text-sm leading-relaxed flex items-center gap-1.5" style="color: var(--color-text-secondary);">
                                <x-icon name="target" class="w-4 h-4 text-emerald-500 shrink-0" />
                                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $objective }}</span>
                            </p>
                        @endif
                    </div>
                </div>
            </header>

            {{-- Selector Táctil de Habilidades (Tabs EF English) --}}
            <nav class="grid grid-cols-3 gap-3" aria-label="Habilidades de la lección">
                @foreach ($skillMeta as $skill => $meta)
                    @php
                        $available = in_array($skill, $requiredSkills, true);
                        $mastered = in_array($skill, $masteredSkills, true);
                        $active = $activeTab === $skill;
                    @endphp
                    @if ($available)
                        <a href="{{ route('lessons.learn', ['lesson' => $lesson, 'tab' => $skill]) }}"
                           class="btn-duo {{ $active ? 'btn-duo-green' : 'btn-duo-outline' }} p-3 sm:p-4 text-center flex-col gap-1 rounded-2xl h-auto"
                           data-skill-tab="{{ $skill }}" data-skill-label="{{ $meta['label'] }}" data-available="true" data-mastered="{{ $mastered ? 'true' : 'false' }}"
                           @if ($active) aria-current="page" @endif>
                            <x-icon :name="$meta['icon']" class="w-5 h-5 mx-auto mb-0.5" />
                            <span class="block text-xs font-extrabold">{{ $meta['label'] }}</span>
                            <span data-skill-status class="text-[10px] font-mono {{ $active ? 'text-white/90' : 'text-slate-400' }}">
                                {{ $mastered ? '✓ Dominado' : 'Pendiente' }}
                            </span>
                        </a>
                    @else
                        <div class="btn-duo btn-duo-outline p-3 sm:p-4 text-center flex-col gap-1 rounded-2xl h-auto opacity-40 cursor-not-allowed"
                             data-skill-tab="{{ $skill }}" data-skill-label="{{ $meta['label'] }}" data-available="false" data-mastered="false">
                            <x-icon :name="$meta['icon']" class="w-5 h-5 mx-auto mb-0.5" />
                            <span class="block text-xs font-bold">{{ $meta['label'] }}</span>
                            <span class="text-[10px] font-mono text-slate-400">Opcional</span>
                        </div>
                    @endif
                @endforeach
            </nav>

            @php
                // Preparar actividades estructuradas uniendo contenido con sus preguntas correspondientes
                $activities = $contentLessons->map(function ($content, $index) use ($questionnaires, $activeTab, $lesson) {
                    $matchingQuestionnaire = $questionnaires->first(function ($q) use ($content) {
                        return $q->listening_lesson_id === $content->listening_lesson_id
                            || $q->title === $content->title
                            || ($content->sort_order && str_contains($q->title, (string)$content->sort_order));
                    }) ?? $questionnaires->get($index);

                    $filteredQuestions = collect();
                    if ($matchingQuestionnaire && $activeTab !== 'speaking') {
                        $filteredQuestions = $matchingQuestionnaire->questions
                            ->filter(function ($question) use ($activeTab) {
                                if ($question->question_type === 'speaking') return false;
                                return $activeTab === 'reading'
                                    ? in_array($question->question_skill_type, ['reading', 'writing'], true)
                                    : $question->question_skill_type === 'listening';
                            })
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
                    }

                    return [
                        'id' => $content->listening_lesson_id,
                        'number' => $index + 1,
                        'sort_order' => $content->sort_order,
                        'title' => $content->title,
                        'reading_text' => $content->reading_text,
                        'listening_script' => $content->listening_script,
                        'speaking_text' => $content->speaking_text,
                        'audio_url' => $content->audio_url,
                        'lesson_id' => $content->lesson_id,
                        'questionnaire_id' => $matchingQuestionnaire?->questionnaire_id,
                        'questionnaire_title' => $matchingQuestionnaire?->title,
                        'questions' => $filteredQuestions->all(),
                        'speaking_feedback_url' => route('lessons.speaking-feedback', [$lesson, $content]),
                    ];
                })->filter(function ($act) use ($activeTab) {
                    return match ($activeTab) {
                        'reading' => (is_string($act['reading_text']) && trim($act['reading_text']) !== '') || count($act['questions']) > 0,
                        'listening' => (is_string($act['listening_script']) && trim($act['listening_script']) !== '') || $act['audio_url'] || count($act['questions']) > 0,
                        'speaking' => is_string($act['speaking_text']) && trim($act['speaking_text']) !== '',
                    };
                })->values();

                // Si no hay actividades pero hay cuestionarios genéricos
                $fallbackPracticeData = $practiceData;
            @endphp

            {{-- Estación Interactiva de Actividades Enfocadas --}}
            <script>
                function updateLessonUi(data) {
                    const xp = Number(data?.total_xp);
                    const streak = Number(data?.streak);
                    const xpElement = document.getElementById('lesson-xp-total');
                    const streakElement = document.getElementById('lesson-streak-total');
                    const status = document.getElementById('lesson-gamification-status');

                    if (xpElement && Number.isFinite(xp)) xpElement.textContent = String(xp);
                    if (streakElement && Number.isFinite(streak)) streakElement.textContent = String(streak);

                    if (Array.isArray(data?.mastered_skills)) {
                        const masteredSkills = new Set(data.mastered_skills);
                        document.querySelectorAll('[data-skill-tab]').forEach(tab => {
                            const skill = tab.dataset.skillTab;
                            const available = tab.dataset.available === 'true';
                            const mastered = masteredSkills.has(skill);
                            const tabStatus = tab.querySelector('[data-skill-status]');
                            const state = mastered ? 'Dominado' : (available ? 'Pendiente' : 'No disponible');

                            tab.dataset.mastered = mastered ? 'true' : 'false';
                            if (tabStatus) tabStatus.textContent = mastered ? '✓ Dominado' : state;
                            tab.setAttribute('aria-label', `${tab.dataset.skillLabel}: ${state}`);
                        });
                    }

                    if (status) {
                        const awarded = Number(data?.xp_awarded) || 0;
                        status.textContent = awarded > 0
                            ? `Ganaste ${awarded} XP. Total ${xp} XP. Racha ${streak}.`
                            : `Progreso actualizado. Total ${Number.isFinite(xp) ? xp : xpElement?.textContent || 0} XP.`;
                    }
                }

                function stationManager(activities, fallbackQuestionnaires, checkUrl, activeTab, skillUrls, mapUrl) {
                    return {
                        activities: activities || [],
                        fallbackQuestionnaires: fallbackQuestionnaires || [],
                        checkUrl,
                        activeTab,
                        skillUrls,
                        mapUrl,
                        activeIndex: 0,
                        qIndex: 0,
                        answers: {},
                        results: null,
                        loading: false,
                        error: null,
                        statusMessage: '',
                        isSpeakingContent: false,

                        get currentActivity() {
                            return this.activities[this.activeIndex] || this.activities[0] || null;
                        },
                        get currentSkillLabel() {
                            return { reading: 'Reading & Grammar', listening: 'Listening Studio', speaking: 'Speaking AI' }[this.activeTab] ?? this.activeTab;
                        },
                        get activityQuestions() {
                            return this.currentActivity?.questions ?? [];
                        },
                        get currentQuestion() {
                            return this.activityQuestions[this.qIndex] ?? null;
                        },
                        get answeredCount() {
                            return this.activityQuestions.filter(q => String(this.answers[q.id] ?? '').trim() !== '').length;
                        },
                        get currentResult() {
                            return this.results && this.currentQuestion ? this.results.results[this.currentQuestion.id] ?? null : null;
                        },
                        get nextSkill() {
                            if (!this.results?.passed) return null;
                            const mastered = new Set(this.results.mastered_skills ?? []);
                            return (this.results.required_skills ?? []).find(skill => !mastered.has(skill)) ?? null;
                        },
                        get nextButtonLabel() {
                            if (this.activeIndex < this.activities.length - 1) {
                                return 'Siguiente actividad →';
                            }
                            if (this.nextSkill) {
                                return 'Siguiente habilidad (' + this.skillLabel(this.nextSkill) + ') →';
                            }
                            return 'Volver al mapa CEFR →';
                        },

                        skillLabel(skill) {
                            return { reading: 'Reading', listening: 'Listening', speaking: 'Speaking' }[skill] ?? skill;
                        },

                        selectActivity(index) {
                            if (this.isSpeakingContent) {
                                window.AIVoice?.stop();
                                this.isSpeakingContent = false;
                            }
                            if (index < 0 || index >= this.activities.length) return;
                            this.activeIndex = index;
                            this.resetQuestions('Actividad ' + (index + 1) + ' cargada.');
                            this.$nextTick(() => {
                                this.$root.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            });
                        },
                        nextActivity() {
                            if (this.activeIndex < this.activities.length - 1) {
                                this.selectActivity(this.activeIndex + 1);
                            } else if (this.nextSkill && this.skillUrls[this.nextSkill]) {
                                window.location.href = this.skillUrls[this.nextSkill];
                            } else {
                                window.location.href = this.mapUrl;
                            }
                        },
                        prevActivity() {
                            if (this.activeIndex > 0) {
                                this.selectActivity(this.activeIndex - 1);
                            }
                        },

                        nextQuestion() {
                            if (this.qIndex < this.activityQuestions.length - 1) {
                                this.qIndex++;
                                this.focusQuestion();
                            }
                        },
                        prevQuestion() {
                            if (this.qIndex > 0) {
                                this.qIndex--;
                                this.focusQuestion();
                            }
                        },
                        resetQuestions(prefix = 'Cuestionario reiniciado.') {
                            this.qIndex = 0;
                            this.answers = {};
                            this.results = null;
                            this.error = null;
                            this.statusMessage = prefix;
                            this.focusQuestion();
                        },
                        focusQuestion() {
                            this.$nextTick(() => {
                                const focusTarget = this.$root.querySelector('[data-practice-question] [data-question-focus]');
                                focusTarget?.focus();
                            });
                        },
                        answerChanged() {
                            this.error = null;
                        },
                        optionStyle(option) {
                            if (!this.results || !this.currentResult) return '';
                            if (option.id === this.currentResult.correct_option_id) return 'background:var(--color-success-surface) !important;border-color:var(--color-success-border) !important;color:var(--color-success-text) !important;';
                            if (option.id === this.currentResult.student_answer && !this.currentResult.is_correct) return 'background:var(--color-error-surface) !important;border-color:var(--color-error-border) !important;color:var(--color-error-text) !important;';
                            return 'opacity:.6;';
                        },

                        speakContentText() {
                            if (this.isSpeakingContent) {
                                window.AIVoice?.stop();
                                this.isSpeakingContent = false;
                                return;
                            }

                            const text = this.activeTab === 'reading'
                                ? (this.currentActivity?.reading_text ?? '')
                                : (this.activeTab === 'listening' ? (this.currentActivity?.listening_script ?? '') : (this.currentActivity?.speaking_text ?? ''));

                            if (!text) return;

                            this.isSpeakingContent = true;
                            window.AIVoice?.speak(text, {
                                lang: 'en-US',
                                onStart: () => { this.isSpeakingContent = true; },
                                onEnd: () => { this.isSpeakingContent = false; },
                                onError: () => { this.isSpeakingContent = false; }
                            });
                        },

                        async verify() {
                            this.error = null;
                            if (this.answeredCount !== this.activityQuestions.length) {
                                this.error = 'Responde todas las preguntas antes de verificar.';
                                const missing = this.activityQuestions.findIndex(q => String(this.answers[q.id] ?? '').trim() === '');
                                if (missing >= 0) this.qIndex = missing;
                                return;
                            }

                            this.loading = true;
                            try {
                                const response = await fetch(this.checkUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    },
                                    body: JSON.stringify({
                                        questionnaire_id: this.currentActivity?.questionnaire_id,
                                        skill: this.activeTab,
                                        answers: this.answers
                                    }),
                                });
                                const data = await response.json();
                                if (!response.ok) {
                                    throw new Error(data.errors?.answers?.[0] || data.error || data.message || 'No se pudo verificar el cuestionario.');
                                }
                                this.results = data;
                                updateLessonUi(data);
                                this.$nextTick(() => this.$refs.resultsPanel?.focus());
                            } catch (error) {
                                this.error = error.message || 'No se pudo verificar el cuestionario.';
                            } finally {
                                this.loading = false;
                            }
                        },
                    };
                }
                function speakingRecorder(feedbackUrl, skillUrls, mapUrl) {
                    return {
                        feedbackUrl,
                        skillUrls,
                        mapUrl,
                        recording: false,
                        loading: false,
                        result: null,
                        error: null,
                        statusMessage: '',
                        elapsed: 0,
                        timer: null,
                        recorder: null,
                        stream: null,
                        chunks: [],
                        mimeType: 'audio/webm',
                        audioBlob: null,
                        audioUrl: null,

                        get nextSkill() {
                            if (!this.result || this.result.is_correct !== true) return null;
                            const mastered = new Set(this.result.mastered_skills ?? []);
                            return (this.result.required_skills ?? []).find(skill => !mastered.has(skill)) ?? null;
                        },

                        skillLabel(skill) {
                            return { reading: 'Reading', listening: 'Listening', speaking: 'Speaking' }[skill] ?? skill;
                        },

                        async toggle() {
                            if (this.loading) return;
                            if (this.recording) return this.stop();
                            await this.start();
                        },

                        async start() {
                            this.error = null;
                            this.result = null;

                            if (!navigator.mediaDevices?.getUserMedia || typeof MediaRecorder === 'undefined') {
                                this.error = 'Tu navegador no permite grabar audio.';
                                this.statusMessage = this.error;
                                return;
                            }

                            try {
                                this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                                this.discardRecording();
                                const types = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'];
                                const supportedType = types.find(type => MediaRecorder.isTypeSupported(type));
                                this.recorder = supportedType
                                    ? new MediaRecorder(this.stream, { mimeType: supportedType })
                                    : new MediaRecorder(this.stream);
                                this.mimeType = this.recorder.mimeType || supportedType || 'audio/webm';
                                this.chunks = [];
                                this.recorder.addEventListener('dataavailable', event => {
                                    if (event.data.size > 0) this.chunks.push(event.data);
                                });
                                this.recorder.addEventListener('stop', () => this.prepareRecording());
                                this.recorder.start();
                                this.recording = true;
                                this.elapsed = 0;
                                this.statusMessage = 'Grabación iniciada.';
                                this.timer = setInterval(() => {
                                    this.elapsed++;
                                    if (this.elapsed >= 60) this.stop();
                                }, 1000);
                            } catch (error) {
                                this.error = 'No se pudo acceder al micrófono. Revisa sus permisos.';
                                this.statusMessage = this.error;
                                this.stream?.getTracks().forEach(track => track.stop());
                            }
                        },

                        stop() {
                            if (!this.recording) return;
                            this.recording = false;
                            clearInterval(this.timer);
                            this.recorder?.stop();
                            this.stream?.getTracks().forEach(track => track.stop());
                            this.statusMessage = 'Grabación detenida. Preparando la reproducción.';
                        },

                        prepareRecording() {
                            if (this.chunks.length === 0) {
                                this.error = 'La grabación quedó vacía.';
                                this.statusMessage = this.error;
                                return;
                            }

                            this.audioBlob = new Blob(this.chunks, { type: this.mimeType });
                            this.audioUrl = URL.createObjectURL(this.audioBlob);
                            this.statusMessage = 'Grabación lista. Reprodúcela y envíala cuando estés conforme.';
                            this.$nextTick(() => this.$refs.playback?.focus());
                        },

                        discardRecording() {
                            if (this.audioUrl) URL.revokeObjectURL(this.audioUrl);
                            this.audioUrl = null;
                            this.audioBlob = null;
                            this.chunks = [];
                        },

                        async submit() {
                            if (!this.audioBlob) {
                                this.error = 'Primero graba y revisa una respuesta.';
                                this.statusMessage = this.error;
                                return;
                            }

                            this.loading = true;
                            this.error = null;
                            this.statusMessage = 'Enviando la grabación para evaluación.';
                            try {
                                const audioBase64 = await new Promise((resolve, reject) => {
                                    const reader = new FileReader();
                                    reader.addEventListener('load', () => resolve(reader.result));
                                    reader.addEventListener('error', reject);
                                    reader.readAsDataURL(this.audioBlob);
                                });
                                const response = await fetch(this.feedbackUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    },
                                    body: JSON.stringify({ audio_base64: audioBase64, mime_type: this.mimeType }),
                                });
                                const data = await response.json();
                                if (!response.ok) {
                                    throw new Error(data.errors?.audio_base64?.[0] || data.error || data.message || 'No se pudo evaluar el audio.');
                                }
                                this.result = data;
                                updateLessonUi(data);
                                this.statusMessage = data.is_correct === true
                                    ? 'Pronunciación aprobada.'
                                    : (data.is_correct === false ? 'La pronunciación necesita otro intento.' : 'La respuesta no pudo evaluarse.');
                                this.$nextTick(() => this.$refs.resultPanel?.focus());
                            } catch (error) {
                                this.error = error.message || 'No se pudo evaluar el audio.';
                                this.statusMessage = this.error;
                            } finally {
                                this.loading = false;
                            }
                        },

                        formatTime(seconds) {
                            const minutes = Math.floor(seconds / 60);
                            const remainder = seconds % 60;
                            return String(minutes).padStart(2, '0') + ':' + String(remainder).padStart(2, '0');
                        },

                        destroy() {
                            clearInterval(this.timer);
                            this.stream?.getTracks().forEach(track => track.stop());
                            if (this.audioUrl) URL.revokeObjectURL(this.audioUrl);
                        },
                    };
                }

                window.stationManager = stationManager;
                window.speakingRecorder = speakingRecorder;
                window.updateLessonUi = updateLessonUi;

                if (window.Alpine) {
                    window.Alpine.data('stationManager', stationManager);
                    window.Alpine.data('speakingRecorder', speakingRecorder);
                } else {
                    document.addEventListener('alpine:init', () => {
                        window.Alpine.data('stationManager', stationManager);
                        window.Alpine.data('speakingRecorder', speakingRecorder);
                    });
                }
            </script>

            <div x-data="stationManager(@js($activities), @js($fallbackPracticeData), @js(route('lessons.check-practice', $lesson)), @js($activeTab), @js($skillUrls), @js($lessonMapUrl))"
                 class="space-y-6">

                {{-- Selector / Stepper de Actividades por Vista --}}
                <div x-show="activities.length > 1" class="glass-card p-4 border" style="border-color: var(--color-glass-border);">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                Actividad seleccionada (<span x-text="activeIndex + 1">1</span> de <span x-text="activities.length">{{ count($activities) }}</span>)
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" @click="prevActivity()" :disabled="activeIndex === 0"
                                    class="btn-duo btn-duo-outline text-xs py-1 px-2.5 disabled:opacity-30 inline-flex items-center gap-1">
                                ← Anterior
                            </button>
                            <button type="button" @click="nextActivity()" :disabled="activeIndex >= activities.length - 1"
                                    class="btn-duo btn-duo-outline text-xs py-1 px-2.5 disabled:opacity-30 inline-flex items-center gap-1">
                                Siguiente →
                            </button>
                        </div>
                    </div>

                    {{-- Carrusel / Píldoras de Actividades --}}
                    <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-none">
                        <template x-for="(act, idx) in activities" :key="act.id || idx">
                            <button type="button"
                                    @click="selectActivity(idx)"
                                    class="px-3.5 py-2 rounded-xl text-xs font-bold shrink-0 transition-all border inline-flex items-center gap-1.5"
                                    :class="activeIndex === idx ? 'bg-emerald-500 text-white shadow-md border-emerald-500' : 'border-slate-200 dark:border-slate-800 hover:border-emerald-500 text-slate-600 dark:text-slate-300'"
                                    :style="activeIndex !== idx ? 'background: var(--color-bg);' : ''">
                                <span class="font-mono text-[11px]" x-text="'#' + (idx + 1)"></span>
                                <span class="max-w-[180px] truncate" x-text="act.title"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Contenedor de la Actividad Activa --}}
                <template x-if="currentActivity">
                    <div class="space-y-6 animate-fade-up">

                        {{-- Encabezado de la Actividad Actual --}}
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 font-mono"
                                      x-text="'Actividad ' + (activeIndex + 1) + ' · ' + currentSkillLabel"></span>
                                <h2 class="font-display text-xl sm:text-2xl font-black" style="color: var(--color-text);" x-text="currentActivity.title"></h2>
                            </div>
                            @if (in_array($activeTab, $masteredSkills, true))
                                <span class="rounded-full px-3.5 py-1 text-xs font-extrabold text-white bg-emerald-500 shadow-sm shrink-0">✓ Dominado</span>
                            @endif
                        </div>

                        {{-- SECCIÓN 1: CONTENIDO DE ESTUDIO DE LA ACTIVIDAD --}}
                        <article class="solid-card overflow-hidden border shadow-sm">
                            <header class="flex items-center justify-between gap-3 border-b px-6 py-4" style="border-color: var(--color-border); background: color-mix(in srgb, var(--color-primary) 5%, var(--color-card));">
                                <div class="flex items-center gap-2.5">
                                    <x-icon :name="$skillMeta[$activeTab]['icon']" class="w-4 h-4 text-emerald-500" />
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Material de Aprendizaje</span>
                                </div>

                                {{-- Botón de Lectura / Audio TTS IA --}}
                                <button type="button"
                                        @click="speakContentText()"
                                        class="btn-duo btn-duo-outline text-xs py-1.5 px-3 inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                                    <template x-if="isSpeakingContent">
                                        <span class="inline-flex items-center gap-1">
                                            <img src="{{ asset('img/soundwave.svg') }}" class="w-4 h-3.5 inline-block" alt="Hablando">
                                            <span>Detener voz</span>
                                        </span>
                                    </template>
                                    <template x-if="!isSpeakingContent">
                                        <span class="inline-flex items-center gap-1">
                                            <x-icon name="speaker" class="w-3.5 h-3.5" />
                                            <span>Escuchar con IA</span>
                                        </span>
                                    </template>
                                </button>
                            </header>

                            <div class="space-y-4 p-6 sm:p-7">
                                {{-- Si es READING --}}
                                <template x-if="activeTab === 'reading'">
                                    <div>
                                        <div :id="'skill-text-' + currentActivity.id"
                                             class="whitespace-pre-line text-base leading-relaxed selection:bg-emerald-500 selection:text-white"
                                             lang="en"
                                             style="color: var(--color-text);"
                                             x-text="currentActivity.reading_text || 'Lee el texto y responde las preguntas debajo.'"></div>
                                    </div>
                                </template>

                                {{-- Si es LISTENING --}}
                                <template x-if="activeTab === 'listening'">
                                    <div class="space-y-4">
                                        <template x-if="currentActivity.audio_url">
                                            <div class="p-4 rounded-2xl border" style="background: var(--color-bg); border-color: var(--color-border);">
                                                <audio :key="currentActivity.id" :src="currentActivity.audio_url" controls preload="metadata" class="w-full">
                                                    Tu navegador no soporta el reproductor de audio.
                                                </audio>
                                            </div>
                                        </template>
                                        <template x-if="!currentActivity.audio_url">
                                            <p class="rounded-xl p-3 text-xs" style="background: var(--color-bg); color: var(--color-text-secondary);">
                                                Esta actividad utiliza reproducción guiada por síntesis vocal de IA.
                                            </p>
                                        </template>

                                        <template x-if="currentActivity.listening_script">
                                            <details class="group">
                                                <summary class="inline-flex min-h-10 cursor-pointer items-center gap-2 text-xs font-bold hover:underline text-emerald-600 dark:text-emerald-400">
                                                    <span>Mostrar transcripción del audio</span>
                                                </summary>
                                                <div :id="'skill-text-' + currentActivity.id"
                                                     class="mt-3 whitespace-pre-line rounded-2xl p-4 text-sm leading-relaxed border"
                                                     style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text);"
                                                     x-text="currentActivity.listening_script"></div>
                                            </details>
                                        </template>
                                    </div>
                                </template>

                                {{-- Si es SPEAKING --}}
                                <template x-if="activeTab === 'speaking'">
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-xs font-bold uppercase tracking-wider text-amber-500">Modelo Nativo a Pronunciar</span>
                                        </div>
                                        <div :id="'skill-text-' + currentActivity.id"
                                             class="whitespace-pre-line text-base leading-relaxed p-4 rounded-2xl border"
                                             style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text);"
                                             lang="en"
                                             x-text="currentActivity.speaking_text"></div>

                                        {{-- Grabadora y Evaluador de Speaking con Gemini --}}
                                        <div x-data="speakingRecorder(currentActivity.speaking_feedback_url, @js($skillUrls), @js($lessonMapUrl))"
                                             class="rounded-2xl border p-5 mt-4"
                                             style="background: var(--color-bg); border-color: var(--color-border);">
                                            @if (! $geminiConfigured)
                                                <p class="text-sm" style="color: var(--color-text-secondary);">
                                                    La grabación está visible, pero la clave de IA Gemini aún no está configurada.
                                                </p>
                                            @else
                                                <div class="flex flex-wrap items-center gap-4">
                                                    <button type="button" @click="toggle()" :disabled="loading"
                                                            class="btn-duo px-6 py-3 text-sm inline-flex items-center gap-2"
                                                            :class="recording ? 'btn-duo-orange animate-pulse' : 'btn-duo-indigo'">
                                                        <x-icon name="mic" class="w-4 h-4" />
                                                        <span x-show="!recording" x-text="audioUrl ? 'Grabar de nuevo' : 'Grabar mi pronunciación'"></span>
                                                        <span x-show="recording" class="flex items-center gap-2">
                                                            <span class="w-2 h-2 rounded-full bg-white animate-ping"></span>
                                                            <span>Detener grabación · <span x-text="formatTime(elapsed)"></span></span>
                                                        </span>
                                                    </button>
                                                    <p class="text-xs" style="color: var(--color-text-secondary);">Lee el texto en voz alta con claridad (máx. 60s).</p>
                                                </div>

                                                <p class="sr-only" role="status" aria-live="polite" x-text="statusMessage"></p>
                                                <p x-show="error" x-text="error" class="feedback-error mt-3 rounded-2xl border p-3.5 text-sm font-semibold" role="alert"></p>

                                                <div x-show="audioUrl" class="mt-4 space-y-3 rounded-2xl border p-4" style="background: var(--color-card); border-color: var(--color-border);">
                                                    <p class="text-xs font-bold uppercase tracking-wider" style="color: var(--color-text);">Escucha tu grabación antes de enviarla</p>
                                                    <audio x-ref="playback" :src="audioUrl" controls preload="metadata" class="w-full">
                                                        Tu navegador no puede reproducir la grabación.
                                                    </audio>
                                                    <div class="flex flex-wrap gap-3 pt-1">
                                                        <button type="button" @click="submit()" :disabled="loading" class="btn-duo btn-duo-green text-xs inline-flex items-center gap-1.5">
                                                            <x-icon name="bot" class="w-3.5 h-3.5" />
                                                            <span x-show="!loading">Evaluar con IA Gemini</span>
                                                            <span x-show="loading">Analizando audio...</span>
                                                        </button>
                                                        <button type="button" @click="discardRecording()" :disabled="loading" class="btn-duo btn-duo-outline text-xs">Descartar</button>
                                                    </div>
                                                </div>

                                                <div x-show="result" x-ref="resultPanel" tabindex="-1" class="mt-5 space-y-3 rounded-2xl border p-5 shadow-sm" style="background: var(--color-card); border-color: var(--color-border);" aria-live="polite">
                                                    <p class="font-display font-extrabold text-base"
                                                       :style="result && result.is_correct === true ? 'color: var(--color-success-text)' : (result && result.is_correct === false ? 'color: var(--color-error-text)' : 'color: var(--color-text-secondary)')"
                                                       x-text="result && result.is_correct === true ? '🎉 ¡Pronunciación aprobada!' : (result && result.is_correct === false ? 'Inténtalo de nuevo' : 'La respuesta no pudo evaluarse')"></p>
                                                    <div class="text-sm rounded-xl p-3 border" style="background: var(--color-bg); border-color: var(--color-border);">
                                                        <span class="font-bold block text-xs uppercase tracking-wider text-slate-400">Transcripción de tu voz:</span>
                                                        <span lang="en" class="font-mono text-sm mt-1 block" x-text="result ? (result.transcription || 'Sin transcripción') : ''"></span>
                                                    </div>
                                                    <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary);" x-text="result ? result.feedback : ''"></p>
                                                    <p x-show="result && result.xp_awarded > 0" class="text-xs font-bold text-amber-500">
                                                        +<span x-text="result ? result.xp_awarded : 0"></span> XP Ganados
                                                    </p>
                                                    <div x-show="result && result.is_correct === true" class="flex flex-wrap gap-3 pt-2">
                                                        <a x-show="nextSkill" :href="nextSkill ? skillUrls[nextSkill] : '#'" class="btn-duo btn-duo-green text-xs" x-text="nextSkill ? 'Continuar con ' + skillLabel(nextSkill) : ''"></a>
                                                        <a x-show="result && result.lesson_completed" :href="mapUrl" class="btn-duo btn-duo-green text-xs">Volver al mapa de niveles</a>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </article>

                        {{-- SECCIÓN 2: CUESTIONARIO Y PREGUNTAS ESPECÍFICAS DE ESTA ACTIVIDAD --}}
                        <template x-if="activeTab !== 'speaking' && activityQuestions.length > 0">
                            <section class="glass-card p-6 sm:p-8 border shadow-sm" style="border-color: var(--color-glass-border);">
                                <div class="mb-6 flex items-center justify-between gap-3">
                                    <div>
                                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 font-mono">
                                            Ejercicios de la Actividad <span x-text="activeIndex + 1"></span>
                                        </span>
                                        <h3 class="font-display text-lg sm:text-xl font-extrabold" style="color: var(--color-text);">
                                            Cuestionario de Comprensión
                                        </h3>
                                        <p class="text-xs" style="color: var(--color-text-secondary);">
                                            Responde las preguntas basadas en el texto o audio anterior.
                                        </p>
                                    </div>
                                    <span class="rounded-full px-3.5 py-1 text-xs font-mono font-bold border" style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text);">
                                        <span x-text="answeredCount"></span> / <span x-text="activityQuestions.length"></span>
                                    </span>
                                </div>

                                <template x-if="currentQuestion">
                                    <fieldset data-practice-question class="rounded-3xl border-2 p-6" style="background: var(--color-bg); border-color: var(--color-border);" :disabled="loading">
                                        <legend data-question-focus tabindex="-1" class="mb-4 w-full text-base font-display font-bold leading-relaxed" lang="en" style="color: var(--color-text);" x-text="currentQuestion.text"></legend>

                                        <div x-show="currentQuestion.type === 'multiple_choice'" class="space-y-3">
                                            <template x-for="option in currentQuestion.options" :key="option.id">
                                                <label class="duo-choice-card"
                                                       :class="answers[currentQuestion.id] === option.id ? 'is-selected' : ''"
                                                       :style="optionStyle(option)">
                                                    <div class="flex items-center gap-3">
                                                        <input type="radio" :name="'practice-' + currentQuestion.id" :value="option.id" x-model="answers[currentQuestion.id]" @change="answerChanged()" :disabled="results !== null" class="w-4 h-4 text-emerald-600">
                                                        <span class="text-sm font-semibold" lang="en" x-text="option.text"></span>
                                                    </div>
                                                </label>
                                            </template>
                                        </div>

                                        <div x-show="currentQuestion.type !== 'multiple_choice'">
                                            <label :for="'practice-answer-' + currentQuestion.id" class="mb-2 block text-xs font-bold uppercase text-slate-400">Escribe tu respuesta</label>
                                            <input :id="'practice-answer-' + currentQuestion.id" type="text" x-model="answers[currentQuestion.id]"
                                                   :disabled="results !== null" @input.debounce.300ms="answerChanged()" @keydown.enter.prevent="nextQuestion()" lang="en"
                                                   class="min-h-12 w-full rounded-2xl border px-4 py-3 text-sm shadow-sm"
                                                   style="background: var(--color-card); border-color: var(--color-control-border); color: var(--color-text);"
                                                   placeholder="Respuesta en inglés...">
                                        </div>

                                        <div x-show="currentResult" class="mt-4 rounded-2xl border p-3.5 text-sm font-semibold" role="status" aria-live="polite"
                                             :style="currentResult && currentResult.is_correct ? 'background:var(--color-success-surface);border-color:var(--color-success-border);color:var(--color-success-text)' : 'background:var(--color-error-surface);border-color:var(--color-error-border);color:var(--color-error-text)'">
                                            <span x-text="currentResult && currentResult.is_correct ? '✓ Correcto' : '✗ Incorrecto'"></span>
                                            <span lang="en" x-show="currentResult && !currentResult.is_correct && currentResult.correct_answer" x-text="currentResult ? ' · Respuesta esperada: ' + currentResult.correct_answer : ''"></span>
                                        </div>
                                    </fieldset>
                                </template>

                                <p x-show="error" x-text="error" class="feedback-error mt-4 rounded-2xl border p-3.5 text-sm font-semibold" role="alert"></p>

                                <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                                    <button type="button" @click="prevQuestion()" :disabled="qIndex === 0 || loading" class="btn-duo btn-duo-outline text-xs py-2 disabled:opacity-40">Anterior</button>
                                    <button x-show="!results" type="button" @click="verify()" :disabled="loading" class="btn-duo btn-duo-green text-xs px-6 py-2.5 disabled:opacity-50">
                                        <span x-show="!loading">Verificar respuestas</span>
                                        <span x-show="loading">Evaluando...</span>
                                    </button>
                                    <button x-show="results" type="button" @click="resetQuestions()" class="btn-duo btn-duo-outline text-xs py-2">Reintentar</button>
                                    <button type="button" @click="nextQuestion()" :disabled="qIndex >= activityQuestions.length - 1 || loading" class="btn-duo btn-duo-outline text-xs py-2 disabled:opacity-40">Siguiente</button>
                                </div>

                                <div x-show="results" x-ref="resultsPanel" tabindex="-1" class="mt-6 rounded-3xl border-2 p-6" style="background: var(--color-bg); border-color: var(--color-border);" aria-live="polite">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <p class="font-display font-extrabold text-lg" x-text="results && results.passed ? '🎉 ¡Cuestionario dominado!' : 'Necesitas reforzar esta actividad'"></p>
                                            <p class="text-xs font-mono mt-1" style="color: var(--color-text-secondary);" x-text="results ? results.correct_count + '/' + results.gradable_count + ' correctas · ' + results.score + '%' : ''"></p>
                                        </div>
                                        <span class="font-display text-3xl font-black" :style="results && results.passed ? 'color: var(--color-primary);' : 'color: var(--color-error-text);'" x-text="results ? results.score + '%' : ''"></span>
                                    </div>
                                    <p x-show="results && results.xp_awarded > 0" class="mt-3 text-xs font-bold text-amber-500">+<span x-text="results ? results.xp_awarded : 0"></span> XP Otorgados</p>
                                    <p x-show="results && results.lesson_completed" class="mt-2 text-xs font-bold text-emerald-500 inline-flex items-center gap-1.5">
                                        <x-icon name="trophy" class="w-4 h-4 text-amber-500" />
                                        <span>Lección completa: has dominado todas sus habilidades.</span>
                                    </p>
                                    <p x-show="results && results.ai_feedback" class="mt-3 text-sm leading-relaxed" style="color: var(--color-text-secondary);" x-text="results ? results.ai_feedback : ''"></p>
                                    <div x-show="results && results.passed" class="mt-5 flex flex-wrap gap-3">
                                        <button type="button" @click="nextActivity()" class="btn-duo btn-duo-green text-xs inline-flex items-center gap-1.5">
                                            <span x-text="activeIndex < activities.length - 1 ? 'Ir a la siguiente actividad →' : (nextSkill ? 'Continuar con ' + skillLabel(nextSkill) + ' →' : 'Volver al mapa de niveles →')"></span>
                                        </button>
                                        <a x-show="nextSkill" :href="nextSkill ? skillUrls[nextSkill] : '#'" class="btn-duo btn-duo-outline text-xs" x-text="nextSkill ? 'Continuar con ' + skillLabel(nextSkill) : ''"></a>
                                        <a :href="mapUrl" class="btn-duo btn-duo-outline text-xs">Volver al mapa de niveles</a>
                                    </div>
                                </div>
                            </section>
                        </template>

                        {{-- Barra de Navegación entre Actividades al Final --}}
                        <div class="flex items-center justify-between gap-3 pt-2">
                            <button type="button"
                                    @click="prevActivity()"
                                    :disabled="activeIndex === 0"
                                    class="btn-duo btn-duo-outline text-xs py-2 px-4 disabled:opacity-30 inline-flex items-center gap-1.5">
                                ← Actividad anterior
                            </button>
                            <span class="text-xs font-mono text-slate-400">
                                Actividad <span x-text="activeIndex + 1">1</span> de <span x-text="activities.length">{{ count($activities) }}</span>
                            </span>
                            <button type="button"
                                    @click="nextActivity()"
                                    class="btn-duo btn-duo-green text-xs py-2 px-4 inline-flex items-center gap-1.5 shadow-sm">
                                <span x-text="nextButtonLabel">Siguiente actividad →</span>
                            </button>
                        </div>
                    </div>
                </template>

                <template x-if="!currentActivity">
                    <div class="solid-card p-10 text-center">
                        <h3 class="font-display font-bold text-lg" style="color: var(--color-text);">Sin contenido disponible</h3>
                        <p class="mt-2 text-sm max-w-md mx-auto" style="color: var(--color-text-secondary);">
                            No hay actividades registradas para esta habilidad en este momento.
                        </p>
                    </div>
                </template>
            </div>

            <div class="text-center pt-2">
                <a href="{{ route('listening.index', ['level' => $lesson->lesson_cefr_level]) }}" class="inline-flex items-center gap-1.5 text-sm font-bold hover:underline" style="color: var(--color-blue);">
                    <span>Abrir catálogo de Listening de nivel {{ $lesson->lesson_cefr_level }}</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
