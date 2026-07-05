<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-8"
         style="background: linear-gradient(135deg, #27594B 0%, #518C4F 50%, #F2B950 100%);">

        <div class="w-full max-w-2xl">
            {{-- Header --}}
            <div class="text-center mb-8">
                <span class="text-6xl block mb-2">🦉</span>
                <h1 class="font-display font-bold text-3xl text-white">Placement Test</h1>
                <p class="mt-2 text-white/80 text-sm">
                    Responde las preguntas para que el búho tutor evalúe tu nivel.
                    Tómalo con calma, solo queremos saber dónde empezar.
                </p>
            </div>

            {{-- Progress bar --}}
            <div class="bg-white/20 rounded-full h-2 mb-8 overflow-hidden">
                <div class="h-full rounded-full bg-white transition-all duration-300" id="progress-bar" style="width: 0%;"></div>
            </div>

            <form method="POST" action="{{ route('placement.submit') }}" id="placement-form">
                @csrf

                @foreach ($questions as $index => $q)
                    <div class="question-card rounded-2xl bg-white p-6 sm:p-8 shadow-xl mb-6 {{ $index === 0 ? '' : 'hidden' }}"
                         data-index="{{ $index }}">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold text-white"
                                  style="background: #27594B;">{{ $index + 1 }}</span>
                            <span class="text-xs font-semibold px-2 py-1 rounded-full"
                                  style="background: #e2e3f1; color: #3a3a7b;">
                                {{ $q['level'] }}
                            </span>
                        </div>

                        <p class="font-display font-bold text-lg mb-5" style="color: #1f2937;">
                            {{ $q['question'] }}
                        </p>

                        <div class="space-y-3">
                            @foreach ($q['options'] as $optIndex => $option)
                                <label class="option-label flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all duration-200 hover:scale-[1.01]"
                                       style="border-color: #e5e7eb; background: #f9fafb;">
                                    <input type="radio" name="answers[{{ $q['id'] }}]" value="{{ $optIndex }}"
                                           class="w-5 h-5" style="accent-color: #518C4F;"
                                           onchange="this.closest('.option-label').style.borderColor = '#518C4F'; this.closest('.option-label').style.background = '#f0fff4';">
                                    <span class="text-sm font-medium" style="color: #374151;">{{ $option['text'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="flex items-center justify-between mt-4">
                    <button type="button" onclick="prevQuestion()"
                            class="px-6 py-3 rounded-xl font-bold text-sm transition-all duration-200"
                            style="background: #e5e7eb; color: #374151;" id="prev-btn">
                        ← Anterior
                    </button>

                    <span class="text-sm font-semibold text-white" id="question-counter">1 / {{ count($questions) }}</span>

                    <button type="button" onclick="nextQuestion()"
                            class="px-6 py-3 rounded-xl font-bold text-sm shadow-md hover:shadow-lg transition-all duration-200"
                            style="background: #F28729; color: white;" id="next-btn">
                        Siguiente →
                    </button>

                    <button type="submit" id="submit-btn"
                            class="px-8 py-3 rounded-xl font-bold text-sm shadow-md hover:shadow-lg transition-all duration-200 hidden"
                            style="background: linear-gradient(135deg, #518C4F, #27594B); color: white;">
                        ✅ Enviar respuestas
                    </button>
                </div>
            </form>

            <div class="text-center mt-6">
                <a href="{{ route('placement.skip') }}" class="text-sm text-white/60 hover:text-white/80 underline transition"
                   onclick="return confirm('¿Seguro? Empezarás desde el nivel más básico.');">
                    Prefiero empezar desde el principio
                </a>
            </div>
        </div>
    </div>

    <script>
        let current = 0;
        const total = {{ count($questions) }};
        const cards = document.querySelectorAll('.question-card');
        const progress = document.getElementById('progress-bar');
        const counter = document.getElementById('question-counter');
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const submitBtn = document.getElementById('submit-btn');

        function updateView() {
            cards.forEach((card, i) => {
                card.classList.toggle('hidden', i !== current);
            });

            const pct = ((current + 1) / total) * 100;
            progress.style.width = pct + '%';
            counter.textContent = (current + 1) + ' / ' + total;
            prevBtn.classList.toggle('hidden', current === 0);
            nextBtn.classList.toggle('hidden', current === total - 1);
            submitBtn.classList.toggle('hidden', current !== total - 1);
        }

        function nextQuestion() {
            const radios = cards[current].querySelectorAll('input[type="radio"]');
            const checked = Array.from(radios).some(r => r.checked);
            if (!checked) {
                cards[current].querySelectorAll('.option-label')[0].style.borderColor = '#F28729';
                cards[current].querySelectorAll('.option-label')[0].style.background = '#fff5f0';
                setTimeout(() => {
                    cards[current].querySelectorAll('.option-label')[0].style.borderColor = '#e5e7eb';
                    cards[current].querySelectorAll('.option-label')[0].style.background = '#f9fafb';
                }, 800);
                return;
            }
            if (current < total - 1) {
                current++;
                updateView();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        function prevQuestion() {
            if (current > 0) {
                current--;
                updateView();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight') nextQuestion();
            if (e.key === 'ArrowLeft') prevQuestion();
        });
    </script>
</x-guest-layout>
