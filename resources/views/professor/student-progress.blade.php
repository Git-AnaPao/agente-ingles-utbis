<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white leading-tight">
            📊 Progreso de {{ $user->name }}
        </h2>
    </x-slot>

    @php
        $totalXp = $progress->where('student_current_status', 'completed')->sum('student_xp_earned');
        $completedCount = $progress->where('student_current_status', 'completed')->count();
        $totalLessons = $lessons->count();
        $attemptCount = $user->attemptLogs()->count();
        $correctAttempts = $user->attemptLogs()->where('passed', true)->count();
        $accuracy = $attemptCount > 0 ? round(($correctAttempts / $attemptCount) * 100, 1) : 0;

        $cefrOrder = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
        $levels = [];
        foreach ($cefrOrder as $lvl) {
            $lvlLessons = $lessons->where('lesson_cefr_level', $lvl);
            $doneInLevel = $completedIds ? count(array_intersect($lvlLessons->pluck('lesson_id')->toArray(), $completedIds)) : 0;
            $levels[] = [
                'cefr' => $lvl,
                'lessons' => $lvlLessons,
                'total' => $lvlLessons->count(),
                'completed' => $doneInLevel,
            ];
        }
    @endphp

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('professor.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold mb-4 hover:underline"
               style="color: #27594B;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Volver al panel
            </a>

            {{-- Stats --}}
            <div class="rounded-2xl p-4 flex items-center justify-around gap-4 shadow-sm text-white text-sm font-semibold"
                 style="background: linear-gradient(135deg, #27594B, #518C4F);">
                <div class="text-center">
                    <span class="block text-2xl">{{ $totalXp }}</span>
                    <span class="text-white/80 text-xs">XP</span>
                </div>
                <div class="text-center">
                    <span class="block text-2xl">{{ $completedCount }}/{{ $totalLessons }}</span>
                    <span class="text-white/80 text-xs">Completadas</span>
                </div>
                <div class="text-center">
                    <span class="block text-2xl">{{ $attemptCount }}</span>
                    <span class="text-white/80 text-xs">Intentos</span>
                </div>
                <div class="text-center">
                    <span class="block text-2xl">{{ $accuracy }}%</span>
                    <span class="text-white/80 text-xs">Precisión</span>
                </div>
            </div>

            {{-- Levels map --}}
            <div class="rounded-2xl bg-white shadow-sm p-6">
                <h3 class="font-display font-bold text-lg mb-4" style="color: #27594B;">Niveles</h3>

                <div class="space-y-4">
                    @foreach ($levels as $level)
                        @php
                            $pct = $level['total'] > 0 ? round(($level['completed'] / $level['total']) * 100) : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-semibold" style="color: #374151;">
                                    {{ $level['cefr'] }}
                                </span>
                                <span class="text-xs font-semibold" style="color: #6B7280;">
                                    {{ $level['completed'] }}/{{ $level['total'] }}
                                </span>
                            </div>
                            <div class="w-full h-2.5 rounded-full" style="background: #E5E7EB;">
                                <div class="h-full rounded-full transition-all" style="background: #518C4F; width: {{ $pct }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
