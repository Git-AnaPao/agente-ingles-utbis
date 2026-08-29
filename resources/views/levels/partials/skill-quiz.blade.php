{{-- Cuestionario genérico reutilizado por Reading / Writing / Listening. --}}
<section class="glass-card p-6 sm:p-8 border shadow-sm" style="border-color: var(--color-glass-border);">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h3 class="font-display text-lg sm:text-xl font-extrabold" style="color: var(--color-text);">
                {{ $heading }}
            </h3>
        </div>
        <span class="rounded-full px-3.5 py-1 text-xs font-mono font-bold border" style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text);">
            <span x-text="answeredCount"></span> / <span x-text="currentQuestions.length"></span>
        </span>
    </div>

    <template x-if="currentQuestion">
        <fieldset class="rounded-3xl border-2 p-6" style="background: var(--color-bg); border-color: var(--color-border);" :disabled="currentState.loading">
            <legend tabindex="-1" class="mb-4 w-full text-base font-display font-bold leading-relaxed" lang="en" style="color: var(--color-text);" x-text="currentQuestion.text"></legend>

            <div x-show="currentQuestion.type === 'multiple_choice'" class="space-y-3">
                <template x-for="option in currentQuestion.options" :key="option.id">
                    <label class="duo-choice-card"
                           :class="currentState.answers[currentQuestion.id] === option.id ? 'is-selected' : ''"
                           :style="optionStyle(option)">
                        <div class="flex items-center gap-3">
                            <input type="radio" :name="'practice-' + currentQuestion.id" :value="option.id" x-model="currentState.answers[currentQuestion.id]" @change="answerChanged()" :disabled="currentState.results !== null" class="w-4 h-4 text-emerald-600">
                            <span class="text-sm font-semibold" lang="en" x-text="option.text"></span>
                        </div>
                    </label>
                </template>
            </div>

            <div x-show="currentQuestion.type !== 'multiple_choice'">
                <label :for="'practice-answer-' + currentQuestion.id" class="mb-2 block text-xs font-bold uppercase text-slate-400">Escribe tu respuesta</label>
                <input :id="'practice-answer-' + currentQuestion.id" type="text" x-model="currentState.answers[currentQuestion.id]"
                       :disabled="currentState.results !== null" @input.debounce.300ms="answerChanged()" @keydown.enter.prevent="nextQuestion()" lang="en"
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

    <p x-show="currentState.error" x-text="currentState.error" class="feedback-error mt-4 rounded-2xl border p-3.5 text-sm font-semibold" role="alert"></p>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <button type="button" @click="prevQuestion()" :disabled="currentState.qIndex === 0 || currentState.loading" class="btn-duo btn-duo-outline text-xs py-2 disabled:opacity-40">Anterior</button>
        <button x-show="!currentState.results" type="button" @click="verify()" :disabled="currentState.loading" class="btn-duo btn-duo-green text-xs px-6 py-2.5 disabled:opacity-50">
            <span x-show="!currentState.loading">Verificar respuestas</span>
            <span x-show="currentState.loading">Evaluando...</span>
        </button>
        <button x-show="currentState.results" type="button" @click="resetQuestions()" class="btn-duo btn-duo-outline text-xs py-2">Reintentar</button>
        <button type="button" @click="nextQuestion()" :disabled="currentState.qIndex >= currentQuestions.length - 1 || currentState.loading" class="btn-duo btn-duo-outline text-xs py-2 disabled:opacity-40">Siguiente</button>
    </div>

    <div x-show="currentState.results" class="mt-6 rounded-3xl border-2 p-6" style="background: var(--color-bg); border-color: var(--color-border);" aria-live="polite">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="font-display font-extrabold text-lg" x-text="currentState.results && currentState.results.passed ? '🎉 ¡Cuestionario dominado!' : 'Necesitas reforzar este paso'"></p>
                <p class="text-xs font-mono mt-1" style="color: var(--color-text-secondary);" x-text="currentState.results ? currentState.results.correct_count + '/' + currentState.results.gradable_count + ' correctas · ' + currentState.results.score + '%' : ''"></p>
            </div>
            <span class="font-display text-3xl font-black" :style="currentState.results && currentState.results.passed ? 'color: var(--color-primary);' : 'color: var(--color-error-text);'" x-text="currentState.results ? currentState.results.score + '%' : ''"></span>
        </div>
        <p x-show="currentState.results && currentState.results.xp_awarded > 0" class="mt-3 text-xs font-bold text-amber-500">+<span x-text="currentState.results ? currentState.results.xp_awarded : 0"></span> XP Otorgados</p>
        <p x-show="currentState.results && currentState.results.lesson_completed" class="mt-2 text-xs font-bold text-emerald-500 inline-flex items-center gap-1.5">
            <x-icon name="trophy" class="w-4 h-4 text-amber-500" />
            <span>Lección completa: has dominado todos los pasos requeridos.</span>
        </p>
        <p x-show="currentState.results && currentState.results.ai_feedback" class="mt-3 text-sm leading-relaxed" style="color: var(--color-text-secondary);" x-text="currentState.results ? currentState.results.ai_feedback : ''"></p>
        <div x-show="currentState.results && currentState.results.passed && stepIndex < availableSkills.length - 1" class="mt-5">
            <button type="button" @click="nextStep()" class="btn-duo btn-duo-green text-xs inline-flex items-center gap-1.5">
                Siguiente paso →
            </button>
        </div>
    </div>
</section>
