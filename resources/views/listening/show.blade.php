<x-app-layout title="Práctica de Listening">
    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-4xl space-y-7 px-4 sm:px-6 lg:px-8">
            {{-- Header de la Actividad --}}
            <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div class="flex items-center gap-3.5">
                    <a href="{{ route('listening.index', ['level' => $listeningLesson->cefr_level]) }}"
                       class="btn-secondary h-11 w-11 p-0 rounded-2xl flex items-center justify-center text-sm font-bold"
                       aria-label="Volver al catálogo de listening">←</a>
                    <div>
                        <p class="text-xs font-mono font-bold uppercase tracking-wider text-sky-600 dark:text-sky-400">
                            {{ $listeningLesson->cefr_level }}.{{ $listeningLesson->sub_level }} · Actividad #{{ $listeningLesson->sort_order }}
                        </p>
                        <h1 class="font-display text-xl sm:text-2xl font-black leading-tight" lang="en" style="color: var(--color-text);">{{ $listeningLesson->title }}</h1>
                    </div>
                </div>
                <span id="listening-xp-total" class="inline-flex items-center gap-1.5 rounded-2xl border px-3.5 py-2 text-xs font-mono font-bold" role="status" aria-live="polite" style="background: var(--color-card); color: var(--color-primary); border-color: var(--color-border);">
                    {{ auth()->user()->xp ?? 0 }} XP
                </span>
            </header>

            @if ($listeningLesson->description)
                <div class="glass-card p-5 text-sm leading-relaxed border" lang="en" style="border-color: var(--color-glass-border); color: var(--color-text-secondary);">
                    {{ $listeningLesson->description }}
                </div>
            @endif

            {{-- Barra de Progreso de Preguntas --}}
            @if ($gradableCount > 0)
                <section class="glass-card p-5 border" style="border-color: var(--color-glass-border);" aria-labelledby="progress-label">
                    <div class="mb-2.5 flex items-center justify-between">
                        <h2 id="progress-label" class="text-xs font-bold uppercase tracking-wide text-slate-400">Preguntas Respondidas</h2>
                        <span id="progress-text" class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400" aria-live="polite">0 / {{ $gradableCount }}</span>
                    </div>
                    <div id="progress-track" class="progress-bar h-2.5" role="progressbar" aria-labelledby="progress-label" aria-valuemin="0" aria-valuemax="{{ $gradableCount }}" aria-valuenow="0" aria-valuetext="0 de {{ $gradableCount }} preguntas respondidas">
                        <div id="progress-bar" class="progress-bar-fill" style="width: 0%;"></div>
                    </div>
                </section>
            @else
                <section class="feedback-neutral rounded-2xl border p-5" role="status" aria-labelledby="no-gradable-heading">
                    <h2 id="no-gradable-heading" class="font-display text-base font-bold">Actividad Informativa</h2>
                    <p class="mt-1 text-sm">No hay preguntas evaluables en esta actividad. Puedes estudiar el contenido libremente.</p>
                </section>
            @endif

            {{-- Lectura de Apoyo --}}
            @if ($listeningLesson->reading_text)
                <section class="solid-card overflow-hidden">
                    <header class="flex items-center justify-between gap-3 border-b px-6 py-4" style="border-color: var(--color-border);">
                        <div>
                            <h2 class="text-xs font-mono font-bold uppercase text-emerald-600 dark:text-emerald-400">Lectura de Contexto</h2>
                            <p class="text-xs" style="color: var(--color-text-secondary);">Lee el texto antes de iniciar la escucha.</p>
                        </div>
                        <button type="button" class="listening-tts-btn control-target rounded-xl border px-3.5 py-2 text-xs font-bold inline-flex items-center gap-1.5 transition hover:scale-105" data-target="reading-text" data-default-label="Escuchar" aria-pressed="false" style="background: var(--color-bg); border-color: var(--color-control-border); color: var(--color-primary);">
                            <span class="tts-icon" aria-hidden="true">🔊</span>
                            <span class="tts-label">Escuchar lectura</span>
                        </button>
                    </header>
                    <div id="reading-text" class="whitespace-pre-line p-6 sm:p-7 text-base leading-relaxed" lang="en" style="color: var(--color-text);">{{ $listeningLesson->reading_text }}</div>
                </section>
            @endif

            {{-- Reproductor de Audio / Guion --}}
            @if ($listeningLesson->audio_url || $listeningLesson->listening_script)
                <section class="solid-card overflow-hidden">
                    <header class="flex items-center justify-between gap-3 border-b px-6 py-4" style="border-color: var(--color-border);">
                        <div class="flex items-center gap-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-sky-500 animate-pulse"></span>
                            <div>
                                <h2 class="text-xs font-mono font-bold uppercase text-sky-600 dark:text-sky-400">Comprensión Auditiva</h2>
                                <p class="text-xs" style="color: var(--color-text-secondary);">Escucha con atención; puedes pausar y repetir.</p>
                            </div>
                        </div>
                        @if (! $listeningLesson->audio_url && $listeningLesson->listening_script)
                            <button type="button" class="listening-tts-btn control-target shrink-0 rounded-xl border px-3.5 py-2 text-xs font-bold inline-flex items-center gap-1.5 transition hover:scale-105" data-target="listening-script" data-default-label="Escuchar guion" aria-pressed="false" style="background: var(--color-bg); border-color: var(--color-control-border); color: var(--color-primary);">
                                <span class="tts-icon" aria-hidden="true">🔊</span>
                                <span class="tts-label">Escuchar guion</span>
                            </button>
                        @endif
                    </header>
                    <div class="space-y-4 p-6 sm:p-7">
                        @if ($listeningLesson->audio_url)
                            <div class="p-4 rounded-2xl border" style="background: var(--color-bg); border-color: var(--color-border);">
                                <audio id="lesson-audio" controls preload="metadata" class="w-full">
                                    <source src="{{ $listeningLesson->audio_url }}">
                                    Tu navegador no puede reproducir este audio.
                                </audio>
                            </div>
                        @else
                            <p class="rounded-2xl p-4 text-xs leading-relaxed border" style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text-secondary);">
                                Esta actividad no tiene archivo grabado. Usa “Escuchar guion” para oírlo con la síntesis vocal.
                            </p>
                        @endif

                        @if ($listeningLesson->listening_script)
                            <details class="group">
                                <summary class="inline-flex min-h-10 cursor-pointer items-center gap-2 text-xs font-bold hover:underline" style="color: var(--color-primary);">
                                    <span>Mostrar transcripción del audio</span>
                                </summary>
                                <div id="listening-script" class="mt-3 whitespace-pre-line rounded-2xl p-4 text-sm leading-relaxed border" lang="en" style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text);">{{ $listeningLesson->listening_script }}</div>
                            </details>
                        @endif
                    </div>
                </section>
            @endif

            @if ($listeningLesson->speaking_text)
                <section class="solid-card p-6 border-l-4 border-amber-500">
                    <h2 class="text-xs font-mono font-bold uppercase text-amber-600 dark:text-amber-400">Extensión de Speaking (IA)</h2>
                    <div class="mt-2 whitespace-pre-line text-sm leading-relaxed" lang="en" style="color: var(--color-text);">{{ $listeningLesson->speaking_text }}</div>
                    <p class="mt-3 text-xs" style="color: var(--color-text-secondary);">
                        Para evaluar tu pronunciación con IA,
                        <a href="{{ route('lessons.learn', ['lesson' => $lesson, 'tab' => 'speaking']) }}" class="font-bold underline text-amber-600 dark:text-amber-400">grábate en la pestaña Speaking</a>.
                    </p>
                </section>
            @endif

            {{-- Sección de Preguntas --}}
            <section class="solid-card overflow-hidden">
                <header class="border-b px-6 py-4" style="border-color: var(--color-border);">
                    <h2 class="font-display text-lg font-bold" style="color: var(--color-text);">Cuestionario de Evaluación</h2>
                    <p class="text-xs font-mono" style="color: var(--color-text-secondary);">
                        {{ count($questions) }} preguntas totales · {{ $gradableCount }} evaluables en listening
                    </p>
                </header>

                @if ($questions === [])
                    <div class="p-10 text-center text-sm" style="color: var(--color-text-secondary);">
                        Esta actividad no tiene preguntas publicadas.
                    </div>
                @else
                    <form id="listening-form" class="divide-y" style="border-color: var(--color-border);">
                        @foreach ($questions as $question)
                            <fieldset class="question-item min-w-0 p-6 sm:p-7" data-question="{{ $question['number'] }}" tabindex="-1" @if ($question['is_speaking']) disabled @endif>
                                <legend class="w-full">
                                    <span class="flex items-start gap-3.5">
                                     <span class="question-number flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-xs font-mono font-black" style="background: color-mix(in srgb, var(--color-primary) 12%, transparent); color: var(--color-primary);">{{ $question['number'] }}</span>
                                     <span class="min-w-0 flex-1 text-base font-bold leading-relaxed" lang="en" style="color: var(--color-text);">{{ $question['text'] }}</span>
                                    </span>
                                </legend>

                                @if ($question['is_speaking'])
                                    <p class="mt-3 rounded-xl p-3 text-xs border" style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text-secondary);">
                                        Consigna de speaking (evaluada en el módulo oral).
                                    </p>
                                @elseif (! empty($question['options']))
                                    <div class="mt-4 space-y-2.5">
                                        @foreach ($question['options'] as $option)
                                            <label class="option-label flex min-h-12 cursor-pointer items-center gap-3.5 rounded-2xl border px-4 py-3 transition-all duration-200 hover:border-sky-500/50" style="background: var(--color-bg); border-color: var(--color-control-border);">
                                                <input type="radio" class="option-input w-4 h-4" name="answers[{{ $question['number'] }}]" value="{{ $option }}">
                                                <span class="text-sm font-medium" lang="en" style="color: var(--color-text);">{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <label for="answer-{{ $question['number'] }}" class="mt-4 block text-xs font-bold uppercase text-slate-400">Tu respuesta</label>
                                    <input id="answer-{{ $question['number'] }}" type="text" class="option-input mt-2 min-h-12 w-full rounded-2xl border px-4 py-3 text-sm shadow-sm"
                                           name="answers[{{ $question['number'] }}]" placeholder="Escribe tu respuesta en inglés..." lang="en"
                                           style="background: var(--color-card); border-color: var(--color-control-border); color: var(--color-text);">
                                @endif
                            </fieldset>
                        @endforeach
                    </form>

                    <div class="border-t px-6 py-5" style="border-color: var(--color-border); background: var(--color-bg);">
                        @if ($gradableCount > 0)
                            <p id="form-error" class="feedback-error mb-4 hidden rounded-2xl border p-3.5 text-sm font-semibold" role="alert" tabindex="-1"></p>
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <button id="check-answers-btn" type="button" class="btn-lumina btn-3d-green px-6 py-3 text-sm font-bold disabled:opacity-50 shadow-md">
                                        <span id="check-label">Verificar respuestas</span>
                                    </button>
                                    <button id="reset-btn" type="button" class="btn-secondary hidden text-xs px-4 py-2.5">Intentar de nuevo</button>
                                </div>
                                <div id="score-display" class="hidden text-right">
                                    <span class="block text-xs uppercase font-bold text-slate-400">Calificación</span>
                                    <span id="score-value" class="font-display text-2xl font-black" style="color: var(--color-primary);"></span>
                                </div>
                            </div>
                        @else
                            <p class="text-sm font-semibold" style="color: var(--color-text-secondary);">No hay respuestas que verificar en esta actividad.</p>
                        @endif
                    </div>
                @endif
            </section>

            {{-- Panel de Resultados --}}
            <section id="results-container" class="hidden space-y-4" aria-live="polite" aria-labelledby="result-title" tabindex="-1">
                <div class="glass-card p-6 sm:p-8 border shadow-lg" style="border-color: var(--color-glass-border);">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 id="result-title" class="font-display text-xl sm:text-2xl font-black" style="color: var(--color-text);"></h2>
                            <p id="result-message" class="mt-1 text-sm" style="color: var(--color-text-secondary);"></p>
                            <p id="xp-message" class="mt-2.5 hidden text-xs font-bold text-amber-500"></p>
                        </div>
                        <span id="result-score" class="font-display text-3xl sm:text-4xl font-black" style="color: var(--color-primary);"></span>
                    </div>
                    <p id="ai-feedback" class="mt-4 hidden rounded-2xl p-4 text-sm leading-relaxed border" style="background: color-mix(in srgb, var(--color-indigo) 10%, var(--color-card)); border-color: color-mix(in srgb, var(--color-indigo) 25%, transparent); color: var(--color-text);"></p>
                    <div id="result-actions" class="mt-6 hidden flex-wrap gap-3">
                        <a id="result-next-link" href="{{ route('listening.index', ['level' => $listeningLesson->cefr_level]) }}" class="btn-lumina btn-3d-green hidden px-6 py-3 text-sm font-bold shadow-md"></a>
                        <button id="result-retry-btn" type="button" class="btn-secondary hidden px-5 py-2.5 text-sm font-bold">Intentar de nuevo</button>
                        <a href="{{ route('listening.index', ['level' => $listeningLesson->cefr_level]) }}" class="btn-secondary px-5 py-2.5 text-sm font-bold">Elegir otra actividad</a>
                    </div>
                </div>

                @foreach ($questions as $question)
                    <article class="question-result hidden solid-card p-5 border" data-result-number="{{ $question['number'] }}">
                        <div class="flex items-start gap-3.5">
                            <span class="result-icon flex h-8 w-8 shrink-0 items-center justify-center rounded-xl font-bold text-xs"></span>
                            <div class="min-w-0 flex-1">
                                <p class="result-status text-sm font-bold"></p>
                                <p class="mt-1 text-xs" style="color: var(--color-text-secondary);">
                                    Tu respuesta: <span class="student-answer font-semibold font-mono" lang="en"></span>
                                </p>
                                <p class="correct-answer-row mt-1 hidden text-xs font-bold" style="color: var(--color-primary);">
                                    Respuesta correcta: <span class="correct-answer font-mono" lang="en"></span>
                                </p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            const gradableCount = @js($gradableCount);
            const form = document.getElementById('listening-form');
            const checkButton = document.getElementById('check-answers-btn');
            const resetButton = document.getElementById('reset-btn');
            const formError = document.getElementById('form-error');
            const progressText = document.getElementById('progress-text');
            const progressBar = document.getElementById('progress-bar');
            const progressTrack = document.getElementById('progress-track');
            const skillUrls = @js([
                'reading' => route('lessons.learn', ['lesson' => $lesson, 'tab' => 'reading']),
                'listening' => route('lessons.learn', ['lesson' => $lesson, 'tab' => 'listening']),
                'speaking' => route('lessons.learn', ['lesson' => $lesson, 'tab' => 'speaking']),
            ]);
            const mapUrl = @js(route('levels.index').'#level-'.$lesson->lesson_cefr_level);
            let hasResult = false;
            let activeTtsButton = null;

            function finishTts() {
                if (activeTtsButton) {
                    activeTtsButton.dataset.speaking = 'false';
                    activeTtsButton.setAttribute('aria-pressed', 'false');
                    activeTtsButton.querySelector('.tts-icon').textContent = '🔊';
                    activeTtsButton.querySelector('.tts-label').textContent = activeTtsButton.dataset.defaultLabel ?? 'Escuchar';
                    activeTtsButton = null;
                }
            }

            function stopTts() {
                window.AIVoice?.stop();
                finishTts();
            }

            document.querySelectorAll('.listening-tts-btn').forEach(button => {
                button.addEventListener('click', () => {
                    if (activeTtsButton === button) {
                        stopTts();
                        return;
                    }

                    stopTts();
                    const target = document.getElementById(button.dataset.target);
                    const text = target?.textContent.trim() ?? '';
                    if (!text) return;

                    activeTtsButton = button;
                    button.dataset.speaking = 'true';
                    button.setAttribute('aria-pressed', 'true');
                    button.querySelector('.tts-icon').textContent = '■';
                    button.querySelector('.tts-label').textContent = 'Detener';

                    window.AIVoice?.speak(text, {
                        lang: 'en-US',
                        onStart: () => {
                            activeTtsButton = button;
                        },
                        onEnd: () => {
                            if (activeTtsButton === button) finishTts();
                        },
                        onError: () => {
                            if (activeTtsButton === button) finishTts();
                        }
                    });
                });
            });

            window.addEventListener('pagehide', stopTts);

            function answersFromForm() {
                const answers = {};
                if (!form) return answers;

                for (const [key, value] of new FormData(form).entries()) {
                    const match = key.match(/^answers\[(.+)]$/);
                    if (match && String(value).trim() !== '') answers[match[1]] = value;
                }

                return answers;
            }

            function updateProgress() {
                const answered = Object.keys(answersFromForm()).length;
                const percent = gradableCount > 0 ? Math.round((answered / gradableCount) * 100) : 0;
                if (progressText) progressText.textContent = answered + ' / ' + gradableCount;
                if (progressBar) progressBar.style.width = percent + '%';
                if (progressTrack) {
                    progressTrack.setAttribute('aria-valuenow', String(answered));
                    progressTrack.setAttribute('aria-valuetext', answered + ' de ' + gradableCount + ' preguntas respondidas');
                }
            }

            function setInputsDisabled(disabled) {
                document.querySelectorAll('.option-input').forEach(input => {
                    input.disabled = disabled;
                });
            }

            function resultElement(number) {
                return Array.from(document.querySelectorAll('[data-result-number]'))
                    .find(element => element.dataset.resultNumber === String(number));
            }

            function showResults(data) {
                hasResult = true;
                setInputsDisabled(true);
                checkButton?.classList.add('hidden');
                resetButton?.classList.remove('hidden');
                document.getElementById('score-display').classList.remove('hidden');
                document.getElementById('score-value').textContent = data.score + '%';

                const xpTotal = document.getElementById('listening-xp-total');
                if (xpTotal && Number.isFinite(Number(data.total_xp))) {
                    xpTotal.textContent = data.total_xp + ' XP';
                }

                const container = document.getElementById('results-container');
                container.classList.remove('hidden');
                document.getElementById('result-title').textContent = data.passed ? '🎉 ¡Listening dominado!' : 'Sigue practicando';
                document.getElementById('result-message').textContent = data.passed
                    ? (data.lesson_completed ? 'Aprobaste y completaste todas las habilidades de la lección.' : 'Aprobaste listening; revisa las habilidades que aún faltan.')
                    : 'Necesitas al menos 70% de aciertos para dominar listening.';
                document.getElementById('result-score').textContent = data.correct_count + '/' + data.total_count;

                const xpMessage = document.getElementById('xp-message');
                if (data.xp_awarded > 0) {
                    xpMessage.textContent = '+' + data.xp_awarded + ' XP Ganados · Total ' + data.total_xp + ' XP · Racha ' + data.streak;
                    xpMessage.classList.remove('hidden');
                } else {
                    xpMessage.classList.add('hidden');
                    xpMessage.textContent = '';
                }

                const feedback = document.getElementById('ai-feedback');
                if (data.ai_feedback) {
                    feedback.textContent = '💡 Tutor IA: ' + data.ai_feedback;
                    feedback.classList.remove('hidden');
                } else {
                    feedback.classList.add('hidden');
                    feedback.textContent = '';
                }

                const actions = document.getElementById('result-actions');
                const nextLink = document.getElementById('result-next-link');
                const retryButton = document.getElementById('result-retry-btn');
                actions.classList.remove('hidden');
                actions.classList.add('flex');
                nextLink.classList.add('hidden');
                retryButton.classList.add('hidden');

                if (data.passed) {
                    const mastered = new Set(data.mastered_skills ?? []);
                    const nextSkill = (data.required_skills ?? []).find(skill => !mastered.has(skill));
                    nextLink.href = data.lesson_completed ? mapUrl : (skillUrls[nextSkill] ?? @js(route('listening.index', ['level' => $listeningLesson->cefr_level])));
                    nextLink.textContent = data.lesson_completed
                        ? 'Continuar en el mapa'
                        : (nextSkill ? 'Continuar con ' + nextSkill[0].toUpperCase() + nextSkill.slice(1) : 'Elegir otra actividad');
                    nextLink.classList.remove('hidden');
                } else {
                    retryButton.classList.remove('hidden');
                }

                Object.entries(data.results).forEach(([number, result]) => {
                    const element = resultElement(number);
                    if (!element) return;

                    const icon = element.querySelector('.result-icon');
                    const status = element.querySelector('.result-status');
                    const studentAnswer = element.querySelector('.student-answer');
                    const correctRow = element.querySelector('.correct-answer-row');
                    const correctAnswer = element.querySelector('.correct-answer');

                    element.classList.remove('hidden');
                    studentAnswer.textContent = result.student_answer || '(sin respuesta)';
                    correctRow.classList.add('hidden');

                    if (result.is_correct === null) {
                        element.style.borderLeft = '4px solid var(--color-control-border)';
                        icon.textContent = '–';
                        icon.style.background = 'var(--color-bg)';
                        icon.style.color = 'var(--color-text-secondary)';
                        status.textContent = 'No evaluada (speaking)';
                        status.style.color = 'var(--color-text-secondary)';
                    } else if (result.is_correct) {
                        element.style.borderLeft = '4px solid var(--color-success-border)';
                        icon.textContent = '✓';
                        icon.style.background = 'var(--color-success-surface)';
                        icon.style.color = 'var(--color-success-text)';
                        status.textContent = 'Correcta';
                        status.style.color = 'var(--color-success-text)';
                    } else {
                        element.style.borderLeft = '4px solid var(--color-error-border)';
                        icon.textContent = '✕';
                        icon.style.background = 'var(--color-error-surface)';
                        icon.style.color = 'var(--color-error-text)';
                        status.textContent = 'Incorrecta';
                        status.style.color = 'var(--color-error-text)';
                        correctAnswer.textContent = result.correct_answer || '(sin respuesta configurada)';
                        correctRow.classList.remove('hidden');
                    }
                });

                const behavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
                container.scrollIntoView({ behavior, block: 'start' });
                container.focus({ preventScroll: true });
            }

            function resetForm() {
                if (!form) return;
                form.reset();
                hasResult = false;
                setInputsDisabled(false);
                checkButton?.classList.remove('hidden');
                resetButton?.classList.add('hidden');
                document.getElementById('score-display').classList.add('hidden');
                document.getElementById('results-container').classList.add('hidden');
                document.getElementById('result-actions').classList.add('hidden');
                document.getElementById('result-actions').classList.remove('flex');
                document.querySelectorAll('.question-result').forEach(element => element.classList.add('hidden'));
                document.querySelectorAll('.option-label').forEach(label => {
                    label.style.background = 'var(--color-bg)';
                    label.style.borderColor = 'var(--color-control-border)';
                });
                formError?.classList.add('hidden');
                if (formError) formError.textContent = '';
                updateProgress();
                form.querySelector('.option-input:not(:disabled)')?.focus();
            }

            if (form) {
                form.addEventListener('input', event => {
                    if (hasResult) return;
                    if (event.target.matches('input[type="radio"]')) {
                        const fieldset = event.target.closest('.question-item');
                        fieldset.querySelectorAll('.option-label').forEach(label => {
                            label.style.background = 'var(--color-bg)';
                            label.style.borderColor = 'var(--color-control-border)';
                        });
                        event.target.closest('.option-label').style.background = 'color-mix(in srgb, var(--color-primary) 8%, transparent)';
                        event.target.closest('.option-label').style.borderColor = 'var(--color-primary)';
                    }
                    updateProgress();
                });
            }

            resetButton?.addEventListener('click', resetForm);
            document.getElementById('result-retry-btn')?.addEventListener('click', resetForm);
            checkButton?.addEventListener('click', async () => {
                const answers = answersFromForm();
                formError.classList.add('hidden');
                formError.textContent = '';

                if (Object.keys(answers).length !== gradableCount) {
                    formError.textContent = 'Responde todas las preguntas de listening antes de verificar.';
                    formError.classList.remove('hidden');
                    const firstMissing = Array.from(form.querySelectorAll('.question-item:not(:disabled)'))
                        .find(fieldset => !Object.hasOwn(answers, fieldset.dataset.question));
                    firstMissing?.querySelector('.option-input')?.focus();
                    return;
                }

                checkButton.disabled = true;
                form.setAttribute('aria-busy', 'true');
                document.getElementById('check-label').textContent = 'Verificando...';

                try {
                    const response = await fetch(@js(route('listening.check', $listeningLesson)), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ answers }),
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.errors?.answers?.[0] || data.message || 'No se pudo verificar la actividad.');
                    }
                    showResults(data);
                } catch (error) {
                    formError.textContent = error.message || 'No se pudo verificar la actividad.';
                    formError.classList.remove('hidden');
                    formError.focus();
                } finally {
                    checkButton.disabled = false;
                    form.removeAttribute('aria-busy');
                    document.getElementById('check-label').textContent = 'Verificar respuestas';
                }
            });

            updateProgress();
        </script>
    @endpush
</x-app-layout>
