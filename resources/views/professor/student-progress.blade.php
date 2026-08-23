<x-app-layout :title="'Progreso de ' . $user->user_name">
    @php
        $totalLessons = $lessons->count();
        $totalXp = (int) ($user->xp ?? 0);
        $displayName = trim(implode(' ', array_filter([
            $user->user_name,
            $user->user_last_name,
            $user->user_middle_name,
        ])));

        $cefrOrder = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
        $levels = [];
        foreach ($cefrOrder as $levelName) {
            $levelLessons = $lessons->where('lesson_cefr_level', $levelName);
            $doneInLevel = count(array_intersect($levelLessons->pluck('lesson_id')->all(), $completedIds));
            $levels[] = [
                'cefr' => $levelName,
                'total' => $levelLessons->count(),
                'completed' => $doneInLevel,
            ];
        }
    @endphp

    <div class="py-8 sm:py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            {{-- Header de Ficha de Estudiante --}}
            <header class="glass-card p-6 sm:p-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border"
                    style="border-color: var(--color-glass-border);">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl font-bold font-display shrink-0 border shadow-sm"
                         style="background: color-mix(in srgb, var(--color-indigo) 12%, transparent); border-color: color-mix(in srgb, var(--color-indigo) 30%, transparent); color: #6366F1;">
                        {{ strtoupper(substr($user->user_name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <span class="text-xs font-bold font-mono uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Expediente de Aprendizaje</span>
                        <h1 class="font-display font-black text-xl sm:text-2xl leading-tight truncate" style="color: var(--color-text);">
                            {{ $displayName }}
                        </h1>
                        <p class="text-xs sm:text-sm font-mono mt-0.5 text-slate-400 truncate">{{ $user->user_email }}</p>
                    </div>
                </div>

                <a href="{{ route('professor.dashboard') }}" class="btn-secondary text-xs px-4 py-2.5 inline-flex items-center gap-1.5 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span>Volver al panel docente</span>
                </a>
            </header>

            {{-- Métricas del Estudiante --}}
            <section aria-labelledby="student-metrics-title" class="animate-fade-up">
                <h2 id="student-metrics-title" class="sr-only">Métricas del estudiante</h2>
                <dl class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                    <div class="solid-card p-5 text-center">
                        <dd class="text-2xl sm:text-3xl font-extrabold font-display text-amber-500">{{ number_format($totalXp) }}</dd>
                        <dt class="text-xs font-bold uppercase tracking-wider mt-1" style="color: var(--color-text-secondary);"><span lang="en">XP</span> Totales</dt>
                    </div>
                    <div class="solid-card p-5 text-center">
                        <dd class="text-2xl sm:text-3xl font-extrabold font-display text-emerald-500">{{ $completedCount }}/{{ $totalLessons }}</dd>
                        <dt class="text-xs font-bold uppercase tracking-wider mt-1" style="color: var(--color-text-secondary);">Lecciones</dt>
                    </div>
                    <div class="solid-card p-5 text-center">
                        <dd class="text-2xl sm:text-3xl font-extrabold font-display text-sky-500">{{ $attemptCount }}</dd>
                        <dt class="text-xs font-bold uppercase tracking-wider mt-1" style="color: var(--color-text-secondary);">Evaluaciones</dt>
                    </div>
                    <div class="solid-card p-5 text-center">
                        <dd class="text-2xl sm:text-3xl font-extrabold font-display text-indigo-500">{{ $approvalRate === null ? '–' : $approvalRate.'%' }}</dd>
                        <dt class="text-xs font-bold uppercase tracking-wider mt-1" style="color: var(--color-text-secondary);">Tasa de aprobación</dt>
                    </div>
                    <div class="solid-card col-span-2 p-5 text-center lg:col-span-1 border-indigo-500/30">
                        <dd class="text-2xl sm:text-3xl font-extrabold font-display font-mono text-purple-500">{{ $currentCefr ?: 'A1' }}</dd>
                        <dt class="text-xs font-bold uppercase tracking-wider mt-1" style="color: var(--color-text-secondary);">Nivel CEFR</dt>
                    </div>
                </dl>
            </section>

            {{-- Avance por Nivel CEFR --}}
            <section class="glass-card p-6 sm:p-8 border shadow-xl animate-fade-up" style="border-color: var(--color-glass-border);" aria-labelledby="levels-progress-title">
                <h2 id="levels-progress-title" class="font-display font-extrabold text-lg sm:text-xl mb-6" style="color: var(--color-text);">
                    Desglose de Avance por Nivel CEFR
                </h2>

                <div class="space-y-6">
                    @foreach ($levels as $level)
                        @php
                            $percent = $level['total'] > 0
                                ? min(100, round(($level['completed'] / $level['total']) * 100))
                                : 0;
                        @endphp
                        <div class="p-4 rounded-2xl border" style="background: var(--color-bg); border-color: var(--color-border);">
                            <div class="flex items-center justify-between gap-3 mb-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-xl flex items-center justify-center font-mono font-bold text-xs"
                                          style="background: color-mix(in srgb, var(--color-primary) 15%, transparent); color: var(--color-primary);">
                                        {{ $level['cefr'] }}
                                    </span>
                                    <span class="text-sm font-bold" style="color: var(--color-text);">Nivel {{ $level['cefr'] }}</span>
                                </div>
                                <span class="text-xs font-mono font-bold" style="color: var(--color-text-secondary);">
                                    {{ $level['completed'] }}/{{ $level['total'] }} lecciones ({{ $percent }}%)
                                </span>
                            </div>
                            <div class="progress-bar h-2.5"
                                 role="progressbar"
                                 aria-label="Progreso en nivel {{ $level['cefr'] }}"
                                 aria-valuenow="{{ $percent }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                                <div class="progress-bar-fill" style="width: {{ $percent }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
