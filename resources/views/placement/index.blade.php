<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        (function() {
            var t = localStorage.getItem('theme');
            var g = localStorage.getItem('grayscale');
            if (t) document.documentElement.setAttribute('data-theme', t);
            if (g === 'true') document.documentElement.setAttribute('data-grayscale', 'true');
        })();
    </script>

    <title>Placement Test · Agente Inglés UTBIS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased" x-data="placementTest()">

    <button @click="toggleTheme()"
            class="fixed top-4 right-4 z-50 w-10 h-10 rounded-full flex items-center justify-center shadow-md transition-all duration-300 hover:scale-110"
            style="background: var(--color-glass); border: 1px solid var(--color-glass-border); backdrop-filter: blur(8px);"
            :title="theme === 'dark' ? 'Modo claro' : 'Modo oscuro'"
            aria-label="Cambiar tema">
        <span x-text="theme === 'dark' ? '&#9728;&#65039;' : '&#127769;'" class="text-lg"></span>
    </button>
    <button @click="toggleLang()"
            class="fixed top-16 right-4 z-50 px-3 py-1.5 rounded-full font-bold text-xs shadow-md transition-all duration-300 hover:scale-110 sm:hidden"
            style="background: var(--color-glass); border: 1px solid var(--color-glass-border); backdrop-filter: blur(8px); color: #fff;"
            aria-label="Cambiar idioma">
        <span x-text="lang === 'en' ? 'ES' : 'EN'"></span>
    </button>
    <button @click="toggleLang()"
            class="hidden sm:flex fixed top-4 right-16 z-50 px-4 py-2 rounded-full font-bold text-sm shadow-md transition-all duration-300 hover:scale-110 items-center gap-2"
            style="background: var(--color-glass); border: 1px solid var(--color-glass-border); backdrop-filter: blur(8px); color: #fff;"
            aria-label="Cambiar idioma">
        <span x-text="lang === 'en' ? '🇪🇸 Español' : '🇬🇧 English'"></span>
    </button>
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-8"
         style="background: linear-gradient(135deg, #27594B 0%, #518C4F 50%, #F2B950 100%);">

        <div class="w-full max-w-7xl">

            {{-- ═══════ RESULTS SCREEN ═══════ --}}
            <template x-if="phase === 'results'">
                <div class="animate-fade-up">
                    <div class="text-center mb-8">
                        <span class="text-6xl block mb-2">&#x1F3C6;</span>
                        <h1 class="font-display font-bold text-3xl text-white" x-text="t('testComplete')"></h1>
                    </div>

                    <div class="rounded-2xl bg-white p-8 sm:p-12 shadow-xl mb-4">
                        <div class="flex flex-col items-center mb-6">
                            <div class="relative w-32 h-32 mb-4">
                                <svg class="w-32 h-32 -rotate-90" viewBox="0 0 120 120">
                                    <circle cx="60" cy="60" r="52" fill="none" stroke="#e5e7eb" stroke-width="10"/>
                                    <circle cx="60" cy="60" r="52" fill="none" :stroke="levelColors[results.level]"
                                            stroke-width="10" stroke-linecap="round"
                                            :stroke-dasharray="2 * Math.PI * 52"
                                            :stroke-dashoffset="2 * Math.PI * 52 * (1 - results.score / 100)"
                                            class="transition-all duration-1000 ease-out"/>
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-3xl font-bold font-display" :style="'color:' + levelColors[results.level]"
                                          x-text="results.score + '%'"></span>
                                    <span class="text-xs text-gray-400" x-text="results.correct + '/' + results.total"></span>
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-500 mb-1" x-text="t('yourLevel')"></p>
                                <div class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-lg font-bold text-white"
                                     :style="'background:' + levelColors[results.level]">
                                    <span x-text="results.level"></span>
                                    <span class="text-white/70">&mdash;</span>
                                    <span x-text="levelNames[results.level]"></span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3 mb-6">
                            <h3 class="font-display font-bold text-sm" style="color: #374151;" x-text="t('scoreByLevel')"></h3>
                            <template x-for="lvl in ['A1','A2','B1','B2','C1']" :key="lvl">
                                <div class="flex items-center gap-3">
                                    <span class="w-10 text-xs font-bold" :style="'color:' + levelColors[lvl]" x-text="lvl"></span>
                                    <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-700 ease-out"
                                             :style="'width:' + (results.breakdown[lvl].correct / results.breakdown[lvl].total * 100) + '%; background:' + levelColors[lvl]"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-500 w-12 text-right"
                                          x-text="results.breakdown[lvl].correct + '/' + results.breakdown[lvl].total"></span>
                                </div>
                            </template>
                        </div>

                        <div class="rounded-xl p-4 mb-6" style="background: #f9fafb; border: 1px solid #e5e7eb;">
                            <p class="text-sm leading-relaxed" style="color: #374151;" x-text="levelDescriptions[results.level]"></p>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3">
                            <a href="{{ route('levels.index') }}"
                               class="flex-1 px-6 py-3 rounded-2xl font-bold text-sm text-white text-center shadow-lg transition-all duration-200 hover:-translate-y-0.5"
                                :style="'background: linear-gradient(135deg, ' + levelColors[results.level] + ', ' + levelColors[results.level] + 'aa)'">
                                <span x-text="t('startLearning')"></span>
                            </a>
                            <a href="{{ route('placement.index') }}"
                               class="px-6 py-3 rounded-2xl font-bold text-sm text-center transition-all duration-200"
                               style="background: #f3f4f6; color: #374151;">
                                <span x-text="t('retakeTest')"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </template>

            {{-- ═══════ INSTRUCTIONS SCREEN ═══════ --}}
            <template x-if="phase === 'intro'">
                <div class="animate-fade-up">
                    <div class="text-center mb-8">
                        <span class="text-6xl block mb-2">&#x1F989;</span>
                        <h1 class="font-display font-bold text-3xl text-white" x-text="t('title')"></h1>
                        <p class="mt-2 text-white/80 text-sm" x-text="t('subtitle')"></p>
                    </div>

                    <div class="rounded-2xl bg-white p-8 sm:p-12 shadow-xl">
                        <h2 class="font-display font-bold text-xl mb-4" style="color: #1f2937;" x-text="t('instructions')"></h2>
                        <ul class="space-y-3 text-sm mb-6" style="color: #374151;">
                            <li class="flex gap-2"><span class="text-brand-verde font-bold">1.</span> <span x-text="t('i1')"></span></li>
                            <li class="flex gap-2"><span class="text-brand-verde font-bold">2.</span> <span x-text="t('i2')"></span></li>
                            <li class="flex gap-2"><span class="text-brand-verde font-bold">3.</span> <span x-text="t('i3')"></span></li>
                            <li class="flex gap-2"><span class="text-brand-verde font-bold">4.</span> <span x-text="t('i4')"></span></li>
                            <li class="flex gap-2"><span class="text-brand-verde font-bold">5.</span> <span x-text="t('i5')"></span></li>
                            <li class="flex gap-2"><span class="text-brand-verde font-bold">6.</span> <span x-text="t('i6')"></span></li>
                            <li class="flex gap-2"><span class="text-brand-verde font-bold">7.</span> <span x-text="t('i7')"></span></li>
                        </ul>

                        <div class="rounded-xl p-4 mb-6" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                            <p class="text-sm font-semibold" style="color: #166534;" x-text="t('timeRec')"></p>
                            <p class="text-xs mt-1" style="color: #15803d;" x-text="t('timeInfo')"></p>
                        </div>

                        <button type="button" @click="startTest()"
                                class="w-full px-6 py-3 rounded-2xl font-bold text-sm text-white shadow-lg transition-all duration-200 hover:-translate-y-0.5"
                                style="background: linear-gradient(135deg, #2D6A4F, #40916C);">
                            <span x-text="t('startTest')"></span>
                        </button>
                    </div>

                    <div class="text-center mt-6">
                        <a href="{{ route('placement.skip') }}" class="text-sm text-white/60 hover:text-white/80 underline transition"
                           @click.prevent="if(confirm(t('skipConfirm'))) window.location.href='{{ route('placement.skip') }}'">
                            <span x-text="t('skip')"></span>
                        </a>
                    </div>
                </div>
            </template>

            {{-- ═══════ TEST SCREEN ═══════ --}}
            <div x-show="phase === 'test'" x-cloak>
                <div class="flex items-center justify-between mb-4">
                    <h1 class="font-display font-bold text-lg text-white">Placement Test</h1>
                    <span class="text-xs font-bold px-3 py-1.5 rounded-full"
                          :class="timeLeft < 300 ? 'bg-red-500 text-white animate-pulse' : 'bg-white/20 text-white'"
                          x-text="formatTime(timeLeft)"></span>
                </div>

                <div class="bg-white/20 rounded-full h-2 mb-6 overflow-hidden">
                    <div class="h-full rounded-full bg-white transition-all duration-300"
                         :style="'width:' + ((currentIndex + 1) / total * 100) + '%'"></div>
                </div>

                <form method="POST" action="{{ route('placement.submit') }}" id="placement-form">
                    @csrf

                    @foreach ($questions as $index => $q)
                        <div x-show="currentIndex === {{ $index }}">
                            {{-- Level header --}}
                            @php
                                $prevLevel = $index > 0 ? $questions[$index - 1]['level'] : null;
                                $showHeader = $q['level'] !== $prevLevel;
                            @endphp
                            @if ($showHeader)
                                <div class="rounded-xl p-4 mb-4 text-center" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(8px);">
                                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold text-white"
                                          style="background: {{ match($q['level']) {
                                              'A1' => '#2D6A4F', 'A2' => '#40916C', 'B1' => '#5B8DEF',
                                              'B2' => '#9B6FE8', 'C1' => '#E86F8A', default => '#2D6A4F'
                                          } }}">
                                        {{ $q['level'] }} &mdash; {{ match($q['level']) {
                                            'A1' => 'Beginner', 'A2' => 'Elementary',
                                            'B1' => 'Intermediate', 'B2' => 'Upper-Intermediate',
                                            'C1' => 'Advanced', default => ''
                                        } }}
                                    </span>
                                </div>
                            @endif

                            {{-- Reading passage at first question of each section --}}
                            @if (isset($q['passage']) && ($index === 0 || !isset($questions[$index - 1]['passage']) || $questions[$index - 1]['passage'] !== $q['passage']))
                                <div class="rounded-2xl bg-white p-8 sm:p-10 shadow-xl mb-4 border-l-4" style="border-color: #518C4F;">
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="text-sm">&#x1F4D6;</span>
                                        <span class="font-display font-bold text-sm" style="color: #27594B;">Reading Passage</span>
                                    </div>
                                    @if ($q['passage'] === 'b1_reading')
                                        <div class="text-sm leading-relaxed" style="color: #374151;">
                                            <p>How many hours do you think you spend sitting every day? A recent survey has shown that many people spend about twelve hours every day sitting in front of a computer, driving to and from work, and watching TV. Add seven hours of sleeping and the total is stunning: nineteen hours of hardly moving.</p>
                                            <p class="mt-3">Sitting for long stretches of time is not healthy. In fact, a study has shown that people who sit a lot typically live two years less than more active people. The findings show that extended periods of sitting are harmful regardless of other time spent exercising or playing sport. Scientists have discovered that extended sitting changes the way the body deals with sugar and, thus, the risk of getting diabetes or heart disease increases for those people who sit all the time.</p>
                                            <p class="mt-3">Scientists at the UK's University of Chester have conducted a simple experiment about the effects of sitting versus standing. They asked ten people who usually spend their days sitting at work to stand for at least three hours a day for a week at their workplace. They wore monitors that checked their heart rate and blood sugar and recorded how much they were moving. At the beginning of the study some of the volunteers were concerned that they would be unable to stand so much, but they were pleasantly surprised&mdash;and one woman even said that her back hurt less after standing during work hours.</p>
                                            <p class="mt-3">The results of the study were astonishing. Blood sugar levels fell back to normal levels after a meal far more quickly on the days when the volunteers stood than when they sat. The heart rate monitors also showed that by standing the volunteers were burning more calories.</p>
                                        </div>
                                    @elseif ($q['passage'] === 'b2_reading')
                                        <div class="text-sm leading-relaxed" style="color: #374151;">
                                            <p>Many of us love to eat a good piece of chocolate now and again. Unfortunately, chocolate is expected to become more expensive in the next few years. There are not enough cocoa trees in the world right now to meet the demand for chocolate.</p>
                                            <p class="mt-3">As economies in countries like China become stronger, more people are buying and eating chocolate. This makes the price of chocolate go up. Even if Central and South American cocoa bean farmers planted more cocoa trees today, the trees would not be ready to produce cocoa beans for ten years.</p>
                                            <p class="mt-3">Some people might stop buying chocolate if it gets too expensive. Others like Greg Johnson who just bought boxes of chocolate for all his employees, will not stop even if the price rises. "I will continue to buy chocolate. I might just buy smaller boxes. Chocolate is a wonderful gift because almost everyone smiles when they get a box of chocolates," he said outside of a Godiva chocolate store.</p>
                                            <p class="mt-3">Big chocolate companies will either raise the cost of a chocolate bar or make the candy bars a bit smaller for the same price.</p>
                                            <p class="mt-3">Either way, if you are a chocolate fan like me, you might want to buy now before the prices rise.</p>
                                        </div>
                                    @elseif ($q['passage'] === 'c1_reading')
                                        <div class="text-sm leading-relaxed" style="color: #374151;">
                                            <p>Modern technology has transformed the way people communicate, collaborate, and access information. Smartphones, instant messaging, and social media platforms have made it possible to remain connected with colleagues, friends, and family at virtually every moment of the day. While this constant connectivity has undoubtedly improved efficiency and convenience, it has also created unexpected challenges that are only now becoming fully understood.</p>
                                            <p class="mt-3">One of the most significant consequences is the gradual disappearance of clear boundaries between work and personal life. Employees often feel obligated to answer emails long after working hours have ended, fearing that delayed responses may be interpreted as a lack of commitment. As a result, many professionals report feeling mentally exhausted even after spending an evening at home, since they are never completely disconnected from their responsibilities.</p>
                                            <p class="mt-3">Researchers have also observed that the continuous flow of notifications may reduce people's ability to concentrate on complex tasks. Every interruption forces the brain to redirect its attention, and although each distraction may seem insignificant, their cumulative effect can substantially reduce productivity. Ironically, tools designed to help people accomplish more often encourage fragmented attention rather than sustained focus.</p>
                                            <p class="mt-3">This does not necessarily mean that technology itself is harmful. Instead, many experts argue that the problem lies in how society has chosen to use it. Organizations that establish clear expectations regarding digital communication often report higher employee satisfaction and lower levels of stress. Likewise, individuals who intentionally schedule periods without digital interruptions frequently experience greater creativity and improved decision-making.</p>
                                            <p class="mt-3">Ultimately, the challenge is not to reject technology but to develop healthier habits that allow people to benefit from its advantages without becoming controlled by it. Achieving this balance may prove to be one of the defining skills of the modern workplace.</p>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Question card --}}
                            <div class="rounded-2xl bg-white p-8 sm:p-12 shadow-xl mb-4">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold text-white"
                                          style="background: #27594B;">{{ $q['id'] }}</span>
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full"
                                          style="background: #e2e3f1; color: #3a3a7b;">{{ $q['level'] }}</span>
                                    <span class="text-xs text-gray-400 ml-auto">{{ $index + 1 }} / {{ count($questions) }}</span>
                                </div>

                                <p class="font-display font-bold text-lg mb-5" style="color: #1f2937;">{{ $q['question'] }}</p>

                                <div class="space-y-3">
                                    @foreach ($q['options'] as $optIndex => $option)
                                        <label class="option-label flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all duration-200 hover:scale-[1.01]"
                                               style="border-color: #e5e7eb; background: #f9fafb;"
                                               x-init="$el.style.borderColor = (answers[{{ $q['id'] }}] === {{ $optIndex }}) ? '#518C4F' : '#e5e7eb'; $el.style.background = (answers[{{ $q['id'] }}] === {{ $optIndex }}) ? '#f0fff4' : '#f9fafb'"
                                               @click="selectAnswer({{ $q['id'] }}, {{ $optIndex }}); $el.style.borderColor='#518C4F'; $el.style.background='#f0fff4'">
                                            <input type="radio"
                                                   name="answers[{{ $q['id'] }}]"
                                                   value="{{ $optIndex }}"
                                                   class="w-5 h-5"
                                                   style="accent-color: #518C4F;"
                                                   :checked="answers[{{ $q['id'] }}] === {{ $optIndex }}">
                                            <span class="text-sm font-medium" style="color: #374151;">{{ $option }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Navigation --}}
                    <div class="flex items-center justify-between mt-4">
                        <button type="button" @click="prevQuestion()"
                                class="px-6 py-3 rounded-xl font-bold text-sm transition-all duration-200"
                                style="background: #e5e7eb; color: #374151;"
                                :class="currentIndex === 0 ? 'opacity-40 pointer-events-none' : ''">
                            &larr; <span x-text="t('previous')"></span>
                        </button>

                        <span class="text-sm font-semibold text-white" x-text="(currentIndex + 1) + ' / ' + total"></span>

                        <button type="button" @click="nextQuestion()"
                                class="px-6 py-3 rounded-xl font-bold text-sm shadow-md hover:shadow-lg transition-all duration-200"
                                style="background: #F28729; color: white;"
                                x-show="currentIndex < total - 1">
                            <span x-text="t('next')"></span>
                        </button>

                        <button type="submit"
                                class="px-8 py-3 rounded-xl font-bold text-sm shadow-md hover:shadow-lg transition-all duration-200"
                                style="background: linear-gradient(135deg, #518C4F, #27594B); color: white;"
                                x-show="currentIndex === total - 1"
                                :disabled="answeredCount < total"
                                :class="answeredCount < total ? 'opacity-50 cursor-not-allowed' : ''">
                            <span x-text="t('submit')"></span> (<span x-text="answeredCount"></span>/<span x-text="total"></span>)
                        </button>
                    </div>
                </form>
            </div>

            {{-- ═══════ TIME UP OVERLAY ═══════ --}}
            <template x-if="timeLeft <= 0 && phase === 'test'">
                <div class="fixed inset-0 z-50 flex items-center justify-center" style="background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);">
                    <div class="rounded-2xl bg-white p-8 shadow-2xl text-center max-w-md mx-4">
                        <span class="text-5xl block mb-4">&#x23F0;</span>
                        <h2 class="font-display font-bold text-2xl mb-2" style="color: #1f2937;" x-text="t('timeUp')"></h2>
                        <p class="text-sm mb-6" style="color: #6b7280;" x-text="t('timeUpMsg')"></p>
                        <button type="button" @click="submitTest()"
                                class="px-8 py-3 rounded-2xl font-bold text-sm text-white shadow-lg"
                                style="background: linear-gradient(135deg, #2D6A4F, #40916C);">
                            <span x-text="t('submitNow')"></span>
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
            const sessionResults = @json($resultsData);
            return {
                lang: localStorage.getItem('placement_lang') || 'en',
                theme: localStorage.getItem('theme') || 'light',
                grayscale: localStorage.getItem('grayscale') === 'true',

                phase: sessionResults ? 'results' : 'intro',
                currentIndex: 0,
                total: total,
                answers: {},
                timeLeft: 55 * 60,
                timerInterval: null,
                questions: rawQuestions,

                results: sessionResults || {
                    level: 'A1', score: 0, correct: 0, total: 75,
                    breakdown: {
                        A1: {correct: 0, total: 11}, A2: {correct: 0, total: 19},
                        B1: {correct: 0, total: 13}, B2: {correct: 0, total: 14},
                        C1: {correct: 0, total: 19}
                    }
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
                levelDescriptions: {
                    A1: 'You are at the beginner level. You can understand and use basic everyday expressions. Your learning journey starts here!',
                    A2: 'You are at the elementary level. You can communicate in simple, routine tasks. Keep building your foundation!',
                    B1: 'You are at the intermediate level. You can deal with most situations while traveling and describe experiences. Great progress!',
                    B2: 'You are at the upper-intermediate level. You can interact with fluency and spontaneity. Excellent command of English!',
                    C1: 'You are at the advanced level. You can express yourself fluently and use language flexibly. Outstanding proficiency!'
                },

                get answeredCount() {
                    return Object.keys(this.answers).length;
                },

                toggleTheme() {
                    this.theme = this.theme === 'dark' ? 'light' : 'dark';
                    localStorage.setItem('theme', this.theme);
                    document.documentElement.setAttribute('data-theme', this.theme);
                },
                toggleGrayscale() {
                    this.grayscale = !this.grayscale;
                    localStorage.setItem('grayscale', this.grayscale);
                    document.documentElement.setAttribute('data-grayscale', this.grayscale);
                },

                toggleLang() {
                    this.lang = this.lang === 'en' ? 'es' : 'en';
                    localStorage.setItem('placement_lang', this.lang);
                },

                translations: {
                    en: {
                        title: 'Placement Test',
                        subtitle: 'English Level Assessment — CEFR A1–C1',
                        instructions: 'Test Instructions',
                        i1: 'Read each question carefully before answering.',
                        i2: 'Select only one answer for each question.',
                        i3: 'There is only one correct answer.',
                        i4: 'Do not use dictionaries, translators, or external resources.',
                        i5: 'There is no penalty for incorrect answers.',
                        i6: 'Once submitted, answers cannot be modified.',
                        i7: 'Answer independently without assistance.',
                        timeRec: '⏱ Recommended time: approximately 50–60 minutes',
                        timeInfo: '75 questions • Levels A1, A2, B1, B2, C1',
                        startTest: 'Start Test →',
                        skip: 'Skip — start from A1',
                        skipConfirm: 'Are you sure? You will start from the most basic level.',
                        testComplete: 'Test Complete!',
                        yourLevel: 'Your level is',
                        scoreByLevel: 'Score by level',
                        startLearning: 'Start Learning →',
                        retakeTest: 'Retake Test',
                        previous: '← Previous',
                        next: 'Next →',
                        submit: '✅ Submit',
                        timeUp: "Time's Up!",
                        timeUpMsg: 'The recommended time has elapsed. You can still submit your answers.',
                        submitNow: 'Submit Now',
                    },
                    es: {
                        title: 'Examen de Nivel',
                        subtitle: 'Evaluación de Nivel de Inglés — CEFR A1–C1',
                        instructions: 'Instrucciones del Examen',
                        i1: 'Lee cada pregunta con atención antes de responder.',
                        i2: 'Selecciona solo una respuesta por pregunta.',
                        i3: 'Solo hay una respuesta correcta.',
                        i4: 'No uses diccionarios, traductores ni recursos externos.',
                        i5: 'No hay penalización por respuestas incorrectas.',
                        i6: 'Una vez enviado, no se pueden modificar las respuestas.',
                        i7: 'Responde de forma independiente sin asistencia.',
                        timeRec: '⏱ Tiempo recomendado: aproximadamente 50–60 minutos',
                        timeInfo: '75 preguntas • Niveles A1, A2, B1, B2, C1',
                        startTest: 'Iniciar Examen →',
                        skip: 'Omitir — empezar desde A1',
                        skipConfirm: '¿Estás seguro? Empezarás desde el nivel más básico.',
                        testComplete: '¡Examen Completado!',
                        yourLevel: 'Tu nivel es',
                        scoreByLevel: 'Puntuación por nivel',
                        startLearning: 'Empezar a Aprender →',
                        retakeTest: 'Repetir Examen',
                        previous: '← Anterior',
                        next: 'Siguiente →',
                        submit: '✅ Enviar',
                        timeUp: '¡Se acabó el tiempo!',
                        timeUpMsg: 'Ha pasado el tiempo recomendado. Aún puedes enviar tus respuestas.',
                        submitNow: 'Enviar Ahora',
                    },
                },

                t(key) {
                    return this.translations[this.lang][key] || key;
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
    </script>
</body>
</html>
