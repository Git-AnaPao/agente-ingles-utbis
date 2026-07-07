<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white leading-tight tracking-tight">
            🌍 Mapa de Avance — English Journey
        </h2>
    </x-slot>

    @php
        $user = Auth::user();
        $totalXp = $user->progress()->where('student_current_status', 'completed')->sum('student_xp_earned');
        $completedIds = $user->progress()->where('student_current_status', 'completed')->pluck('lesson_id')->toArray();
        $totalLessons = 0;
        foreach ($levels as $lvl) { $totalLessons += $lvl['total']; }
        $completionPct = $totalLessons > 0 ? round((count($completedIds) / $totalLessons) * 100) : 0;
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Stats bar gamificada --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                <div class="solid-card p-4 text-center animate-fade-up">
                    <div class="text-2xl font-bold gradient-text font-display">{{ $totalXp }}</div>
                    <div class="text-xs font-medium mt-0.5" style="color: var(--color-text-secondary);">XP</div>
                </div>
                <div class="solid-card p-4 text-center animate-fade-up" style="animation-delay: 0.05s;">
                    <div class="text-2xl font-bold font-display" style="color: var(--color-primary);">{{ count($completedIds) }}</div>
                    <div class="text-xs font-medium mt-0.5" style="color: var(--color-text-secondary);">Completadas</div>
                </div>
                <div class="solid-card p-4 text-center animate-fade-up" style="animation-delay: 0.1s;">
                    <div class="text-2xl font-bold font-display" style="color: var(--color-accent);">{{ count($levels) }}</div>
                    <div class="text-xs font-medium mt-0.5" style="color: var(--color-text-secondary);">Niveles</div>
                </div>
                <div class="solid-card p-4 text-center animate-fade-up" style="animation-delay: 0.15s;">
                    <div class="text-2xl font-bold font-display" style="color: var(--color-purple);">{{ $totalLessons }}</div>
                    <div class="text-xs font-medium mt-0.5" style="color: var(--color-text-secondary);">Lecciones</div>
                </div>
            </div>

            {{-- Barra de progreso global --}}
            <div class="solid-card p-5 mb-8 animate-fade-up">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-semibold" style="color: var(--color-text);">Progreso global</span>
                    <span class="text-xs font-bold gradient-text">{{ $completionPct }}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: {{ $completionPct }}%;"></div>
                </div>
            </div>

            {{-- Horizontal path container --}}
            <div class="relative overflow-x-auto pb-6 animate-fade-up">
                <div class="flex items-start gap-0 min-w-max px-2">

                    @php
                        $allCompleted = true;
                    @endphp

                    @foreach ($levels as $levelIndex => $level)
                        @php
                            $levelLessons = $level['lessons'];
                            $levelLessonIds = $levelLessons->pluck('lesson_id')->toArray();
                            $doneInLevel = count(array_intersect($levelLessonIds, $completedIds));

                            $levelCompleted = $level['total'] > 0 && $doneInLevel >= $level['total'];
                            $levelStarted = $doneInLevel > 0;
                            $isCurrent = $allCompleted && !$levelCompleted;
                            $levelStatus = $levelCompleted ? 'completed' : ($levelStarted ? 'started' : ($allCompleted ? 'current' : 'locked'));

                            if ($levelCompleted) {
                                // keep allCompleted true
                            } elseif ($levelStarted && !$levelCompleted) {
                                $allCompleted = false;
                            } elseif (!$levelStarted) {
                                // future levels locked
                            }
                        @endphp

                        {{-- Level station --}}
                        <div class="flex flex-col items-center relative mx-3 first:ml-0 last:mr-0">

                            {{-- Level node --}}
                            <div class="relative">
                                <div class="relative group">
                                    <div class="w-24 h-24 rounded-full flex items-center justify-center text-4xl shadow-lg transition-all duration-500 hover:scale-105
                                        @if ($levelStatus === 'completed')
                                            shadow-md animate-glow"
                                            style="background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light)); border: 4px solid var(--color-warning);"
                                        @elseif ($levelStatus === 'current' || $levelStatus === 'started')
                                            shadow-lg"
                                            style="background: linear-gradient(135deg, var(--color-accent), var(--color-warning)); border: 4px solid var(--color-card);"
                                        @else
                                            shadow-sm"
                                            style="background: #D1D5DB; border: 4px solid #9CA3AF;"
                                        @endif
                                    >
                                        @if ($levelStatus === 'locked')
                                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        @else
                                            <span class="text-3xl font-bold" style="color: white;">{{ $level['cefr'] }}</span>
                                        @endif
                                    </div>

                                    @if ($levelStatus === 'current' || $levelStatus === 'started')
                                        <div class="absolute inset-0 w-24 h-24 rounded-full animate-ping opacity-20"
                                             style="background: var(--color-accent);"></div>
                                    @endif

                                    @if ($levelStatus === 'completed')
                                        <div class="absolute -top-1 -right-1 w-6 h-6 rounded-full flex items-center justify-center shadow-md animate-bounce-in"
                                             style="background: var(--color-warning);">
                                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                    @endif
                                </div>

                                {{-- Level label --}}
                                <div class="text-center mt-3 w-28">
                                    <h3 class="font-display font-bold text-sm truncate" style="color: var(--color-text);">
                                        {{ $level['cefr'] }}
                                    </h3>
                                    <span class="inline-flex items-center gap-1 mt-1 text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                          @if ($levelStatus === 'completed')
                                              style="background: color-mix(in srgb, var(--color-primary) 15%, transparent); color: var(--color-primary);"
                                          @elseif ($levelStatus === 'locked')
                                              style="background: #e5e7eb; color: #9CA3AF;"
                                          @else
                                              style="background: #fff3cd; color: #856404;"
                                          @endif
                                    >
                                        @if ($levelStatus === 'completed') ✓
                                        @elseif ($levelStatus === 'locked') 🔒
                                        @else 🎯
                                        @endif
                                        {{ $doneInLevel }}/{{ $level['total'] }}
                                    </span>
                                </div>
                            </div>

                            {{-- Sub-level lessons --}}
                            <div class="flex items-center gap-2.5 mt-4">
                                @foreach ($levelLessons as $subIndex => $lesson)
                                    @php
                                        $subCompleted = in_array($lesson->lesson_id, $completedIds);
                                        $subLocked = $levelStatus === 'locked' && !$subCompleted;
                                    @endphp

                                    <div class="relative flex flex-col items-center">
                                        <a href="{{ $subCompleted || (!$subLocked && $levelStatus !== 'locked') ? route('lessons.learn', $lesson) : '#' }}"
                                           class="block w-12 h-12 rounded-full flex items-center justify-center text-base font-bold shadow-sm transition-all duration-300 hover:scale-110 hover:-translate-y-1
                                                @if ($subCompleted)
                                                    shadow-sm cursor-pointer"
                                                    style="background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light)); color: white; border: 3px solid var(--color-warning);"
                                                @elseif (!$subLocked && $levelStatus !== 'locked')
                                                    shadow-sm cursor-pointer"
                                                    style="background: var(--color-card); color: var(--color-primary); border: 3px solid var(--color-accent);"
                                                @else
                                                    shadow-sm cursor-not-allowed"
                                                    style="background: #E5E7EB; color: #9CA3AF; border: 3px solid #D1D5DB;"
                                                @endif
                                        >
                                            @if ($subCompleted)
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            @elseif ($subLocked)
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            @else
                                                <span class="text-[10px] font-bold">{{ $subIndex + 1 }}</span>
                                            @endif
                                        </a>

                                        <span class="text-center mt-1.5 leading-tight max-14">
                                            <span class="block text-[9px] font-semibold truncate"
                                                  style="color: @if ($subCompleted) var(--color-primary) @elseif ($subLocked) #9CA3AF @else var(--color-text-secondary) @endif"
                                            >{{ $lesson->lesson_skill_type }}</span>
                                        </span>
                                    </div>

                                    @if (!$loop->last)
                                        <div class="w-3 h-0.5 rounded-full" style="background: var(--color-border); margin-top: -1.5rem;"></div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        {{-- Connector between levels --}}
                        @if (!$loop->last)
                            <div class="flex items-center self-center mx-2" style="margin-top: -2.5rem;">
                                <svg width="36" height="8" viewBox="0 0 36 8" fill="none"
                                     style="stroke: {{ $levelStatus === 'locked' ? '#D1D5DB' : 'var(--color-primary)' }};">
                                    <path d="M0 4H32" stroke-width="2" stroke-dasharray="4 3"/>
                                    <polygon points="32,1 36,4 32,7" fill="{{ $levelStatus === 'locked' ? '#D1D5DB' : 'var(--color-primary)' }}"/>
                                </svg>
                            </div>
                        @endif
                    @endforeach

                </div>
            </div>

            {{-- Trophy footer --}}
            <div class="flex justify-center mt-4 animate-fade-up">
                <div class="text-center solid-card p-6 inline-flex items-center gap-4">
                    <span class="text-4xl">🏆</span>
                    <div class="text-start">
                        <p class="text-sm font-bold" style="color: var(--color-text);">
                            {{ count($completedIds) >= $totalLessons ? '¡Completaste todo el curso! 🎉' : '¡Sigue así! Cada lección cuenta.' }}
                        </p>
                        <p class="text-xs mt-0.5" style="color: var(--color-text-secondary);">
                            {{ count($completedIds) }}/{{ $totalLessons }} lecciones completadas
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Success toast --}}
    @if (session('success'))
        <div class="xp-toast">
            <span class="flex items-center gap-2">
                <span>✅</span>
                <span>{{ session('success') }}</span>
            </span>
        </div>
    @endif
</x-app-layout>
