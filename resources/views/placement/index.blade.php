<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-8"
         style="background: linear-gradient(135deg, #27594B 0%, #518C4F 50%, #F2B950 100%);"
         x-data="placementTest()">

        <div class="w-full max-w-2xl">

            {{-- ═══════ INSTRUCTIONS SCREEN ═══════ --}}
            <template x-if="phase === 'intro'">
                <div class="animate-fade-up">
                    <div class="text-center mb-8">
                        <span class="text-6xl block mb-2">&#x1F989;</span>
                        <h1 class="font-display font-bold text-3xl text-white">Placement Test</h1>
                        <p class="mt-2 text-white/80 text-sm">
                            English Level Assessment &mdash; CEFR A1&ndash;C1
                        </p>
                    </div>

                    <div class="rounded-2xl bg-white p-6 sm:p-8 shadow-xl">
                        <h2 class="font-display font-bold text-xl mb-4" style="color: #1f2937;">Test Instructions</h2>
                        <ul class="space-y-3 text-sm mb-6" style="color: #374151;">
                            <li class="flex gap-2"><span class="text-brand-verde font-bold">1.</span> Read each question carefully before answering.</li>
                            <li class="flex gap-2"><span class="text-brand-verde font-bold">2.</span> Select only one answer for each question.</li>
                            <li class="flex gap-2"><span class="text-brand-verde font-bold">3.</span> There is only one correct answer.</li>
                            <li class="flex gap-2"><span class="text-brand-verde font-bold">4.</span> Do not use dictionaries, translators, or external resources.</li>
                            <li class="flex gap-2"><span class="text-brand-verde font-bold">5.</span> There is no penalty for incorrect answers.</li>
                            <li class="flex gap-2"><span class="text-brand-verde font-bold">6.</span> Once submitted, answers cannot be modified.</li>
                            <li class="flex gap-2"><span class="text-brand-verde font-bold">7.</span> Answer independently without assistance.</li>
                        </ul>

                        <div class="rounded-xl p-4 mb-6" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                            <p class="text-sm font-semibold" style="color: #166534;">
                                &#x23F1;&#xFE0F; Recommended time: approximately 50&ndash;60 minutes
                            </p>
                            <p class="text-xs mt-1" style="color: #15803d;">75 questions &bull; Levels A1, A2, B1, B2, C1</p>
                        </div>

                        <button type="button" @click="startTest()"
                                class="w-full px-6 py-3 rounded-2xl font-bold text-sm text-white shadow-lg transition-all duration-200 hover:-translate-y-0.5"
                                style="background: linear-gradient(135deg, #2D6A4F, #40916C);">
                            Start Test &#x2192;
                        </button>
                    </div>

                    <div class="text-center mt-6">
                        <a href="{{ route('placement.skip') }}" class="text-sm text-white/60 hover:text-white/80 underline transition"
                           onclick="return confirm('Are you sure? You will start from the most basic level.');">
                            Skip &mdash; start from A1
                        </a>
                    </div>
                </div>
            </template>

            {{-- ═══════ TEST SCREEN ═══════ --}}
            <template x-if="phase === 'test'">
                <div>
                    {{-- Header --}}
                    <div class="flex items-center justify-between mb-4">
                        <h1 class="font-display font-bold text-lg text-white">Placement Test</h1>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-bold px-3 py-1.5 rounded-full"
                                  :class="timeLeft < 300 ? 'bg-red-500 text-white animate-pulse' : 'bg-white/20 text-white'"
                                  x-text="formatTime(timeLeft)"></span>
                        </div>
                    </div>

                    {{-- Progress bar --}}
                    <div class="bg-white/20 rounded-full h-2 mb-6 overflow-hidden">
                        <div class="h-full rounded-full bg-white transition-all duration-300"
                             :style="'width:' + ((currentIndex + 1) / total * 100) + '%'"></div>
                    </div>

                    <form method="POST" action="{{ route('placement.submit') }}" id="placement-form" @submit.prevent="submitTest()">
                        @csrf

                        {{-- Level section header --}}
                        <template x-if="showLevelHeader">
                            <div class="rounded-xl p-4 mb-4 text-center" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(8px);">
                                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold text-white"
                                      :style="'background:' + levelColors[currentLevel]">
                                    <span x-text="currentLevel"></span>
                                    <span class="text-white/70">&mdash;</span>
                                    <span x-text="levelNames[currentLevel]"></span>
                                </span>
                            </div>
                        </template>

                        {{-- Reading passage (shown at first question of each reading section) --}}
                        <template x-if="currentQuestion.passage && isFirstOfPassage">
                            <div class="rounded-2xl bg-white p-5 sm:p-6 shadow-xl mb-4 border-l-4" style="border-color: #518C4F;">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="text-sm">&#x1F4D6;</span>
                                    <span class="font-display font-bold text-sm" style="color: #27594B;">Reading Passage</span>
                                </div>
                                <div class="text-sm leading-relaxed whitespace-pre-line" style="color: #374151;"
                                     x-html="passages[currentQuestion.passage]"></div>
                            </div>
                        </template>

                        {{-- Question card --}}
                        <div class="rounded-2xl bg-white p-6 sm:p-8 shadow-xl mb-4">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold text-white"
                                      style="background: #27594B;" x-text="currentQuestion.id"></span>
                                <span class="text-xs font-semibold px-2 py-1 rounded-full"
                                      style="background: #e2e3f1; color: #3a3a7b;"
                                      x-text="currentQuestion.level"></span>
                                <span class="text-xs text-gray-400 ml-auto" x-text="(currentIndex + 1) + ' / ' + total"></span>
                            </div>

                            <p class="font-display font-bold text-lg mb-5" style="color: #1f2937;"
                               x-text="currentQuestion.question"></p>

                            <div class="space-y-3">
                                <template x-for="(opt, optIdx) in currentQuestion.options" :key="optIdx">
                                    <label class="option-label flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all duration-200 hover:scale-[1.01]"
                                           :style="answers[currentQuestion.id] === optIdx
                                               ? 'border-color: #518C4F; background: #f0fff4;'
                                               : 'border-color: #e5e7eb; background: #f9fafb;'"
                                           @click="selectAnswer(currentQuestion.id, optIdx)">
                                        <input type="radio"
                                               :name="'answers[' + currentQuestion.id + ']'"
                                               :value="optIdx"
                                               class="w-5 h-5"
                                               style="accent-color: #518C4F;"
                                               :checked="answers[currentQuestion.id] === optIdx"
                                               @click.stop>
                                        <span class="text-sm font-medium" style="color: #374151;" x-text="opt"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- Navigation --}}
                        <div class="flex items-center justify-between mt-4">
                            <button type="button" @click="prevQuestion()"
                                    class="px-6 py-3 rounded-xl font-bold text-sm transition-all duration-200"
                                    style="background: #e5e7eb; color: #374151;"
                                    :class="currentIndex === 0 ? 'opacity-40 pointer-events-none' : ''">
                                &larr; Previous
                            </button>

                            <button type="button" @click="nextQuestion()"
                                    class="px-6 py-3 rounded-xl font-bold text-sm shadow-md hover:shadow-lg transition-all duration-200"
                                    style="background: #F28729; color: white;"
                                    x-show="currentIndex < total - 1">
                                Next &rarr;
                            </button>

                            <button type="submit"
                                    class="px-8 py-3 rounded-xl font-bold text-sm shadow-md hover:shadow-lg transition-all duration-200"
                                    style="background: linear-gradient(135deg, #518C4F, #27594B); color: white;"
                                    x-show="currentIndex === total - 1"
                                    :disabled="answeredCount < total"
                                    :class="answeredCount < total ? 'opacity-50 cursor-not-allowed' : ''">
                                &#x2705; Submit (<span x-text="answeredCount"></span>/<span x-text="total"></span>)
                            </button>
                        </div>
                    </form>
                </div>
            </template>

            {{-- ═══════ TIME UP OVERLAY ═══════ --}}
            <template x-if="timeLeft <= 0 && phase === 'test'">
                <div class="fixed inset-0 z-50 flex items-center justify-center" style="background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);">
                    <div class="rounded-2xl bg-white p-8 shadow-2xl text-center max-w-md mx-4">
                        <span class="text-5xl block mb-4">&#x23F0;</span>
                        <h2 class="font-display font-bold text-2xl mb-2" style="color: #1f2937;">Time's Up!</h2>
                        <p class="text-sm mb-6" style="color: #6b7280;">
                            The recommended time has elapsed. You can still submit your answers.
                        </p>
                        <button type="button" @click="submitTest()"
                                class="px-8 py-3 rounded-2xl font-bold text-sm text-white shadow-lg"
                                style="background: linear-gradient(135deg, #2D6A4F, #40916C);">
                            Submit Now
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <script>
        function placementTest() {
            const rawQuestions = @json($questions);
            const total = rawQuestions.length;

            return {
                phase: 'intro',
                currentIndex: 0,
                total: total,
                answers: {},
                timeLeft: 55 * 60,
                timerInterval: null,

                questions: rawQuestions,

                passages: {
                    b1_reading: `<p>How many hours do you think you spend sitting every day? A recent survey has shown that many people spend about twelve hours every day sitting in front of a computer, driving to and from work, and watching TV. Add seven hours of sleeping and the total is stunning: nineteen hours of hardly moving.</p>\n<p>Sitting for long stretches of time is not healthy. In fact, a study has shown that people who sit a lot typically live two years less than more active people. The findings show that extended periods of sitting are harmful regardless of other time spent exercising or playing sport. Scientists have discovered that extended sitting changes the way the body deals with sugar and, thus, the risk of getting diabetes or heart disease increases for those people who sit all the time.</p>\n<p>Scientists at the UK's University of Chester have conducted a simple experiment about the effects of sitting versus standing. They asked ten people who usually spend their days sitting at work to stand for at least three hours a day for a week at their workplace. They wore monitors that checked their heart rate and blood sugar and recorded how much they were moving. At the beginning of the study some of the volunteers were concerned that they would be unable to stand so much, but they were pleasantly surprised&mdash;and one woman even said that her back hurt less after standing during work hours.</p>\n<p>The results of the study were astonishing. Blood sugar levels fell back to normal levels after a meal far more quickly on the days when the volunteers stood than when they sat. The heart rate monitors also showed that by standing the volunteers were burning more calories.</p>`,

                    b2_reading: `<p>Many of us love to eat a good piece of chocolate now and again. Unfortunately, chocolate is expected to become more expensive in the next few years. There are not enough cocoa trees in the world right now to meet the demand for chocolate.</p>\n<p>As economies in countries like China become stronger, more people are buying and eating chocolate. This makes the price of chocolate go up. Even if Central and South American cocoa bean farmers planted more cocoa trees today, the trees would not be ready to produce cocoa beans for ten years.</p>\n<p>Some people might stop buying chocolate if it gets too expensive. Others like Greg Johnson who just bought boxes of chocolate for all his employees, will not stop even if the price rises. "I will continue to buy chocolate. I might just buy smaller boxes. Chocolate is a wonderful gift because almost everyone smiles when they get a box of chocolates," he said outside of a Godiva chocolate store.</p>\n<p>Big chocolate companies will either raise the cost of a chocolate bar or make the candy bars a bit smaller for the same price.</p>\n<p>Either way, if you are a chocolate fan like me, you might want to buy now before the prices rise.</p>`,

                    c1_reading: `<p>Modern technology has transformed the way people communicate, collaborate, and access information. Smartphones, instant messaging, and social media platforms have made it possible to remain connected with colleagues, friends, and family at virtually every moment of the day. While this constant connectivity has undoubtedly improved efficiency and convenience, it has also created unexpected challenges that are only now becoming fully understood.</p>\n<p>One of the most significant consequences is the gradual disappearance of clear boundaries between work and personal life. Employees often feel obligated to answer emails long after working hours have ended, fearing that delayed responses may be interpreted as a lack of commitment. As a result, many professionals report feeling mentally exhausted even after spending an evening at home, since they are never completely disconnected from their responsibilities.</p>\n<p>Researchers have also observed that the continuous flow of notifications may reduce people's ability to concentrate on complex tasks. Every interruption forces the brain to redirect its attention, and although each distraction may seem insignificant, their cumulative effect can substantially reduce productivity. Ironically, tools designed to help people accomplish more often encourage fragmented attention rather than sustained focus.</p>\n<p>This does not necessarily mean that technology itself is harmful. Instead, many experts argue that the problem lies in how society has chosen to use it. Organizations that establish clear expectations regarding digital communication often report higher employee satisfaction and lower levels of stress. Likewise, individuals who intentionally schedule periods without digital interruptions frequently experience greater creativity and improved decision-making.</p>\n<p>Ultimately, the challenge is not to reject technology but to develop healthier habits that allow people to benefit from its advantages without becoming controlled by it. Achieving this balance may prove to be one of the defining skills of the modern workplace.</p>`
                },

                levelColors: {
                    A1: '#2D6A4F', A2: '#40916C', B1: '#5B8DEF',
                    B2: '#9B6FE8', C1: '#E86F8A'
                },
                levelNames: {
                    A1: 'Beginner', A2: 'Elementary',
                    B1: 'Intermediate', B2: 'Upper-Intermediate',
                    C1: 'Advanced'
                },

                get currentQuestion() {
                    return this.questions[this.currentIndex] || this.questions[0];
                },

                get currentLevel() {
                    return this.currentQuestion.level;
                },

                get showLevelHeader() {
                    if (this.currentIndex === 0) return true;
                    return this.currentQuestion.level !== this.questions[this.currentIndex - 1].level;
                },

                get isFirstOfPassage() {
                    if (!this.currentQuestion.passage) return false;
                    const pKey = this.currentQuestion.passage;
                    for (let i = this.currentIndex - 1; i >= 0; i--) {
                        if (this.questions[i].passage === pKey) return false;
                    }
                    return true;
                },

                get answeredCount() {
                    return Object.keys(this.answers).length;
                },

                startTest() {
                    this.phase = 'test';
                    this.timerInterval = setInterval(() => {
                        if (this.timeLeft > 0) {
                            this.timeLeft--;
                        } else {
                            clearInterval(this.timerInterval);
                        }
                    }, 1000);
                },

                selectAnswer(qId, optIdx) {
                    this.answers[qId] = optIdx;
                },

                nextQuestion() {
                    if (this.currentIndex < this.total - 1) {
                        this.currentIndex++;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },

                prevQuestion() {
                    if (this.currentIndex > 0) {
                        this.currentIndex--;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },

                formatTime(seconds) {
                    if (seconds <= 0) return "00:00";
                    const m = Math.floor(seconds / 60);
                    const s = seconds % 60;
                    return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                },

                submitTest() {
                    if (this.timerInterval) clearInterval(this.timerInterval);
                    document.getElementById('placement-form').submit();
                }
            };
        }

        document.addEventListener('keydown', (e) => {
            const scope = document.querySelector('[x-data]');
            if (!scope || !scope.__x) return;
            const data = scope.__x.$data;
            if (data.phase !== 'test') return;

            if (e.key === 'ArrowRight') data.nextQuestion();
            if (e.key === 'ArrowLeft') data.prevQuestion();
        });
    </script>
</x-guest-layout>
