{{-- Cuestionario genérico reutilizado por Reading / Writing / Listening. --}}
<section
    class="glass-card p-6 sm:p-8 border shadow-sm overflow-hidden"
    style="border-color: var(--color-glass-border);"
>
    {{-- Encabezado --}}
    <div class="mb-6 flex items-center justify-between gap-4">
        <div class="min-w-0 flex-1">
            <h3
                class="font-display text-lg sm:text-xl font-extrabold break-words"
                style="color: var(--color-text);"
            >
                {{ $heading }}
            </h3>
        </div>

        {{-- Contador: 1 / 10, 2 / 10, etc. --}}
        <span
            class="shrink-0 rounded-full px-3.5 py-1 text-xs font-mono font-bold border"
            style="
                background: var(--color-bg);
                border-color: var(--color-border);
                color: var(--color-text);
            "
        >
            <span x-text="currentQuestions.length ? currentState.qIndex + 1 : 0"></span>
            /
            <span x-text="currentQuestions.length"></span>
        </span>
    </div>


    {{-- Pregunta actual --}}
    <template x-if="currentQuestion">
        <div class="w-full min-w-0">

            {{-- Texto de la pregunta FUERA del fieldset --}}
            <div class="mb-5 px-1 sm:px-2">
                <p
                    :id="'practice-question-' + currentQuestion.id"
                    tabindex="-1"
                    class="w-full max-w-full text-base sm:text-lg font-display font-bold leading-relaxed whitespace-normal break-words [overflow-wrap:anywhere]"
                    lang="en"
                    style="color: var(--color-text);"
                    x-text="currentQuestion.text"
                ></p>
            </div>


            {{-- Recuadro de respuestas --}}
            <fieldset
                class="w-full max-w-full rounded-3xl border-2 p-4 sm:p-6 overflow-hidden"
                style="
                    background: var(--color-bg);
                    border-color: var(--color-border);
                "
                :disabled="currentState.loading"
                :aria-labelledby="'practice-question-' + currentQuestion.id"
            >

                {{-- MULTIPLE CHOICE --}}
                <div
                    x-show="currentQuestion.type === 'multiple_choice'"
                    class="space-y-3"
                >
                    <template
                        x-for="option in currentQuestion.options"
                        :key="option.id"
                    >
                        <label
                            class="duo-choice-card w-full max-w-full overflow-hidden"
                            :class="
                                currentState.answers[currentQuestion.id] === option.id
                                    ? 'is-selected'
                                    : ''
                            "
                            :style="optionStyle(option)"
                        >
                            <div class="flex min-w-0 items-center gap-3">

                                <input
                                    type="radio"
                                    :name="'practice-' + currentQuestion.id"
                                    :value="option.id"
                                    x-model="currentState.answers[currentQuestion.id]"
                                    @change="answerChanged()"
                                    :disabled="currentState.results !== null"
                                    class="w-4 h-4 shrink-0 text-emerald-600"
                                >

                                <span
                                    class="min-w-0 flex-1 text-sm font-semibold leading-relaxed whitespace-normal break-words [overflow-wrap:anywhere]"
                                    lang="en"
                                    x-text="option.text"
                                ></span>

                            </div>
                        </label>
                    </template>
                </div>


                {{-- FILL BLANK / RESPUESTA ESCRITA --}}
                <div
                    x-show="currentQuestion.type !== 'multiple_choice'"
                    class="w-full"
                >
                    <label
                        :for="'practice-answer-' + currentQuestion.id"
                        class="mb-2 block text-xs font-bold uppercase text-slate-400"
                    >
                        Escribe tu respuesta
                    </label>

                    <input
                        :id="'practice-answer-' + currentQuestion.id"
                        type="text"
                        x-model="currentState.answers[currentQuestion.id]"
                        :disabled="currentState.results !== null"
                        @input.debounce.300ms="answerChanged()"
                        @keydown.enter.prevent="nextQuestion()"
                        lang="en"
                        class="min-h-12 w-full max-w-full rounded-2xl border px-4 py-3 text-sm shadow-sm"
                        style="
                            background: var(--color-card);
                            border-color: var(--color-control-border);
                            color: var(--color-text);
                        "
                        placeholder="Respuesta en inglés..."
                    >
                </div>


                {{-- Resultado de la pregunta --}}
                <div
                    x-show="currentResult"
                    class="mt-4 w-full max-w-full rounded-2xl border p-3.5 text-sm font-semibold break-words"
                    role="status"
                    aria-live="polite"
                    :style="
                        currentResult && currentResult.is_correct
                            ? 'background:var(--color-success-surface);border-color:var(--color-success-border);color:var(--color-success-text)'
                            : 'background:var(--color-error-surface);border-color:var(--color-error-border);color:var(--color-error-text)'
                    "
                >
                    <span
                        x-text="
                            currentResult && currentResult.is_correct
                                ? '✓ Correcto'
                                : '✗ Incorrecto'
                        "
                    ></span>

                    <span
                        lang="en"
                        x-show="
                            currentResult &&
                            !currentResult.is_correct &&
                            currentResult.correct_answer
                        "
                        x-text="
                            currentResult
                                ? ' · Respuesta esperada: ' + currentResult.correct_answer
                                : ''
                        "
                    ></span>
                </div>

            </fieldset>
        </div>
    </template>


    {{-- Error --}}
    <p
        x-show="currentState.error"
        x-text="currentState.error"
        class="feedback-error mt-4 rounded-2xl border p-3.5 text-sm font-semibold"
        role="alert"
    ></p>


    {{-- Navegación --}}
    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">

        <button
            type="button"
            @click="prevQuestion()"
            :disabled="currentState.qIndex === 0 || currentState.loading"
            class="btn-duo btn-duo-outline text-xs py-2 disabled:opacity-40"
        >
            Anterior
        </button>


        <button
            x-show="!currentState.results"
            type="button"
            @click="verify()"
            :disabled="currentState.loading"
            class="btn-duo btn-duo-green text-xs px-6 py-2.5 disabled:opacity-50"
        >
            <span x-show="!currentState.loading">
                Verificar respuestas
            </span>

            <span x-show="currentState.loading">
                Evaluando...
            </span>
        </button>


        <button
            x-show="currentState.results"
            type="button"
            @click="resetQuestions()"
            class="btn-duo btn-duo-outline text-xs py-2"
        >
            Reintentar
        </button>


        <button
            type="button"
            @click="nextQuestion()"
            :disabled="
                currentState.qIndex >= currentQuestions.length - 1 ||
                currentState.loading
            "
            class="btn-duo btn-duo-outline text-xs py-2 disabled:opacity-40"
        >
            Siguiente
        </button>

    </div>


    {{-- Resultado general --}}
    <div
        x-show="currentState.results"
        class="mt-6 rounded-3xl border-2 p-6"
        style="
            background: var(--color-bg);
            border-color: var(--color-border);
        "
        aria-live="polite"
    >

        <div class="flex items-center justify-between gap-4">

            <div class="min-w-0 flex-1">

                <p
                    class="font-display font-extrabold text-lg"
                    x-text="
                        currentState.results && currentState.results.passed
                            ? '🎉 ¡Cuestionario dominado!'
                            : 'Necesitas reforzar este paso'
                    "
                ></p>

                <p
                    class="text-xs font-mono mt-1"
                    style="color: var(--color-text-secondary);"
                    x-text="
                        currentState.results
                            ? currentState.results.correct_count
                                + '/'
                                + currentState.results.gradable_count
                                + ' correctas · '
                                + currentState.results.score
                                + '%'
                            : ''
                    "
                ></p>

            </div>


            <span
                class="shrink-0 font-display text-3xl font-black"
                :style="
                    currentState.results && currentState.results.passed
                        ? 'color: var(--color-primary);'
                        : 'color: var(--color-error-text);'
                "
                x-text="
                    currentState.results
                        ? currentState.results.score + '%'
                        : ''
                "
            ></span>

        </div>


        {{-- XP --}}
        <p
            x-show="
                currentState.results &&
                currentState.results.xp_awarded > 0
            "
            class="mt-3 text-xs font-bold text-amber-500"
        >
            +
            <span
                x-text="
                    currentState.results
                        ? currentState.results.xp_awarded
                        : 0
                "
            ></span>
            XP Otorgados
        </p>


        {{-- Lección completada --}}
        <p
            x-show="
                currentState.results &&
                currentState.results.lesson_completed
            "
            class="mt-2 text-xs font-bold text-emerald-500 inline-flex items-center gap-1.5"
        >
            <x-icon
                name="trophy"
                class="w-4 h-4 text-amber-500"
            />

            <span>
                Lección completa: has dominado todos los pasos requeridos.
            </span>
        </p>


        {{-- Feedback de IA --}}
        <p
            x-show="
                currentState.results &&
                currentState.results.ai_feedback
            "
            class="mt-3 text-sm leading-relaxed"
            style="color: var(--color-text-secondary);"
            x-text="
                currentState.results
                    ? currentState.results.ai_feedback
                    : ''
            "
        ></p>


        {{-- Siguiente habilidad --}}
        <div
            x-show="
                currentState.results &&
                currentState.results.passed &&
                stepIndex < availableSkills.length - 1
            "
            class="mt-5"
        >
            <button
                type="button"
                @click="nextStep()"
                class="btn-duo btn-duo-green text-xs inline-flex items-center gap-1.5"
            >
                Siguiente paso →
            </button>
        </div>

    </div>
</section>