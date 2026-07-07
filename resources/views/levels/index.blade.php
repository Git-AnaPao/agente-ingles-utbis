<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white leading-tight">
            🌍 Mapa de Avance — English Journey
        </h2>
    </x-slot>

    @php
        $user = Auth::user();
        $totalXp = $user->progress()->where('student_current_status', 'completed')->sum('student_xp_earned');
        $completedIds = $user->progress()->where('student_current_status', 'completed')->pluck('lesson_id')->toArray();
        $totalLessons = 0;
        foreach ($levels as $lvl) { $totalLessons += $lvl['total']; }
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Stats bar --}}
            <div class="rounded-2xl p-4 mb-6 flex items-center justify-around gap-4 shadow-sm text-white text-sm font-semibold"
                 style="background: linear-gradient(135deg, var(--color-primary), var(--color-accent));">
                <div class="text-center">
                    <span class="block text-2xl">{{ $totalXp }}</span>
                    <span class="text-white/80 text-xs">XP</span>
                </div>
                <div class="text-center">
                    <span class="block text-2xl">{{ count($completedIds) }}</span>
                    <span class="text-white/80 text-xs">Completadas</span>
                </div>
                <div class="text-center">
                    <span class="block text-2xl">{{ count($levels) }}</span>
                    <span class="text-white/80 text-xs">Niveles</span>
                </div>
                <div class="text-center">
                    <span class="block text-2xl">{{ $totalLessons }}</span>
                    <span class="text-white/80 text-xs">Lecciones</span>
                </div>
            </div>

            {{-- Horizontal path container --}}
            <div class="relative overflow-x-auto pb-4" style="scroll-behavior: smooth; scrollbar-width: thin; scrollbar-color: var(--color-accent) var(--color-border);">
                <div class="flex items-start gap-0 min-w-max px-4">

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
                                // future levels locked, but first incomplete is current
                            }
                        @endphp

                        {{-- Level station --}}
                        <div class="flex flex-col items-center relative mx-2 first:ml-0 last:mr-0">

                            {{-- Level node --}}
                            <div class="relative">
                                <div class="relative group">
                                    <div class="w-24 h-24 rounded-full flex items-center justify-center text-4xl shadow-lg transition-all duration-300
                                        @if ($levelStatus === 'completed')
                                            shadow-md"
                                            style="background: linear-gradient(135deg, var(--color-accent), var(--color-primary)); border: 5px solid var(--color-warning);"
                                        @elseif ($levelStatus === 'current' || $levelStatus === 'started')
                                            shadow-lg"
                                            style="background: linear-gradient(135deg, var(--color-highlight), var(--color-warning)); border: 5px solid var(--color-card);"
                                        @else
                                            shadow-sm"
                                            style="background: #D1D5DB; border: 5px solid #9CA3AF;"
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
                                             style="background: var(--color-highlight);"></div>
                                    @endif
                                </div>

                                {{-- Level label below node --}}
                                <div class="text-center mt-2 w-28">
                                    <h3 class="font-display font-bold text-sm truncate" style="color: var(--color-primary);">
                                        {{ $level['cefr'] }}
                                    </h3>
                                    <span class="inline-block mt-0.5 text-[10px] font-semibold px-1.5 py-0.5 rounded-full"
                                          @if ($levelStatus === 'completed')
                                              style="background: color-mix(in srgb, var(--color-accent) 20%, transparent); color: var(--color-accent);"
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

                            {{-- Lessons row --}}
                            <div class="flex items-center gap-2 mt-3">
                                @foreach ($levelLessons as $subIndex => $lesson)
                                    @php
                                        $subCompleted = in_array($lesson->lesson_id, $completedIds);
                                        $subLocked = $levelStatus === 'locked' && !$subCompleted;
                                    @endphp

                                    <div class="relative flex flex-col items-center">
                                        <a href="{{ $subCompleted || (!$subLocked && $levelStatus !== 'locked') ? route('lessons.learn', $lesson) : '#' }}"
                                           class="block w-12 h-12 rounded-full flex items-center justify-center text-base font-bold shadow-sm transition-all duration-200 hover:scale-110
                                                @if ($subCompleted)
                                                    shadow-sm cursor-pointer"
                                                    style="background: linear-gradient(135deg, var(--color-accent), var(--color-primary)); color: white; border: 3px solid var(--color-warning);"
                                                @elseif (!$subLocked && $levelStatus !== 'locked')
                                                    shadow-sm cursor-pointer"
                                                    style="background: var(--color-card); color: var(--color-primary); border: 3px solid var(--color-highlight);"
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
                                                <span class="text-[10px]">{{ $subIndex + 1 }}</span>
                                            @endif
                                        </a>

                                        <span class="text-center mt-1 leading-tight max-14">
                                            <span class="block text-[9px] font-semibold truncate"
                                                  style="color: @if ($subCompleted) var(--color-accent) @elseif ($subLocked) #9CA3AF @else var(--color-primary) @endif"
                                            >{{ $lesson->lesson_skill_type }}</span>
                                        </span>
                                    </div>

                                    {{-- Connector between lessons --}}
                                    @if (!$loop->last)
                                        <div class="w-2 h-0.5 rounded-full" style="background: var(--color-border); margin-top: -1.25rem;"></div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        {{-- Connector between level stations --}}
                        @if (!$loop->last)
                            <div class="flex items-center self-center mx-1" style="margin-top: -2.5rem;">
                                <svg width="32" height="8" viewBox="0 0 32 8" fill="none"
                                     style="stroke: {{ $levelStatus === 'locked' ? '#D1D5DB' : 'var(--color-accent)' }};">
                                    <path d="M0 4H28" stroke-width="2" stroke-dasharray="4 3"/>
                                    <polygon points="28,1 32,4 28,7" fill="{{ $levelStatus === 'locked' ? '#D1D5DB' : 'var(--color-accent)' }}"/>
                                </svg>
                            </div>
                        @endif
                    @endforeach

                </div>
            </div>

            {{-- Trophy footer --}}
            <div class="flex justify-center mt-6">
                <div class="text-center">
                    <span class="text-4xl">🏆</span>
                    <p class="mt-1 text-sm font-semibold" style="color: var(--color-primary);">
                        {{ count($completedIds) >= $totalLessons ? '¡Completaste todo el curso! 🎉' : '¡Sigue así! Cada lección cuenta.' }}
                    </p>
                </div>
            </div>

        </div>
    </div>

    {{-- Success toast --}}
    @if (session('success'))
        <div class="fixed bottom-6 right-6 z-50 animate-bounce">
            <div class="rounded-xl px-5 py-3 shadow-lg text-white text-sm font-semibold"
                 style="background: linear-gradient(135deg, var(--color-accent), var(--color-primary));">
                {{ session('success') }}
            </div>
        </div>
    @endif
</x-app-layout>
