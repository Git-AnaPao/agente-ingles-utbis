<x-app-layout title="Panel del Profesor">
    <div class="py-8 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            {{-- Header Docente --}}
            <header class="glass-card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center border"
                    style="border-color: var(--color-glass-border);">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shrink-0 shadow-sm"
                         style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(99, 102, 241, 0.05)); border: 1px solid rgba(99, 102, 241, 0.3); color: #6366F1;">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold font-mono uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Portal Académico</span>
                        <h1 class="font-display text-2xl sm:text-3xl font-black tracking-tight" style="color: var(--color-text);">
                            Panel Docente UTBIS
                        </h1>
                        <p class="text-xs sm:text-sm mt-0.5" style="color: var(--color-text-secondary);">Seguimiento del progreso individual e institucional de los estudiantes.</p>
                    </div>
                </div>

                <div class="rounded-2xl px-4 py-2 border text-xs font-semibold" style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text-secondary);">
                    Vista global institucional
                </div>
            </header>

            {{-- Métricas Bento Institucionales --}}
            <section aria-labelledby="institutional-metrics-title" class="animate-fade-up">
                <h2 id="institutional-metrics-title" class="sr-only">Métricas institucionales</h2>
                <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <div class="solid-card p-5 text-center relative overflow-hidden group">
                        <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-emerald-400 to-teal-600"></div>
                        <dd class="text-3xl sm:text-4xl font-extrabold font-display" style="color: var(--color-primary);">{{ $totalStudents }}</dd>
                        <dt class="mt-1 text-xs font-bold uppercase tracking-wider" style="color: var(--color-text-secondary);">Estudiantes Activos</dt>
                    </div>
                    <div class="solid-card p-5 text-center relative overflow-hidden group">
                        <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-sky-400 to-blue-600"></div>
                        <dd class="text-3xl sm:text-4xl font-extrabold font-display" style="color: var(--color-blue);">{{ $totalLessons }}</dd>
                        <dt class="mt-1 text-xs font-bold uppercase tracking-wider" style="color: var(--color-text-secondary);">Lecciones Activas</dt>
                        <p class="mt-1 text-[11px] font-mono" style="color: var(--color-text-secondary);">{{ $totalCompleted }} finalizaciones</p>
                    </div>
                    <div class="solid-card p-5 text-center relative overflow-hidden group">
                        <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-amber-400 to-orange-600"></div>
                        <dd class="text-3xl sm:text-4xl font-extrabold font-display text-amber-500">{{ number_format($totalXp) }}</dd>
                        <dt class="mt-1 text-xs font-bold uppercase tracking-wider" style="color: var(--color-text-secondary);"><span lang="en">XP</span> Institucional</dt>
                    </div>
                    <div class="solid-card p-5 text-center relative overflow-hidden group">
                        <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-indigo-400 to-purple-600"></div>
                        <dd class="text-3xl sm:text-4xl font-extrabold font-display text-indigo-500">{{ $approvalRate === null ? '–' : $approvalRate.'%' }}</dd>
                        <dt class="mt-1 text-xs font-bold uppercase tracking-wider" style="color: var(--color-text-secondary);">Tasa de aprobación de intentos</dt>
                    </div>
                </dl>
            </section>

            {{-- Tabla de Estudiantes --}}
            <section class="glass-card p-6 sm:p-8 border shadow-xl animate-fade-up" style="border-color: var(--color-glass-border);" aria-labelledby="students-title">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-6">
                    <div>
                        <span class="text-xs font-mono font-bold uppercase text-indigo-600 dark:text-indigo-400">Directorio Estudiantil</span>
                        <h2 id="students-title" class="font-display font-bold text-xl" style="color: var(--color-text);">
                            Progreso de Estudiantes
                        </h2>
                        <p class="mt-0.5 text-xs sm:text-sm" style="color: var(--color-text-secondary);">
                            {{ $students->total() }} {{ $students->total() === 1 ? 'estudiante registrado' : 'estudiantes registrados' }}{{ $search !== '' ? ' para la búsqueda actual' : '' }}.
                        </p>
                    </div>

                    <form method="GET" action="{{ route('professor.dashboard') }}" class="flex w-full gap-2 sm:max-w-md" role="search">
                        <div class="min-w-0 flex-1">
                            <label for="student-search" class="sr-only">Buscar por nombre o correo</label>
                            <input id="student-search"
                                   name="q"
                                   type="search"
                                   value="{{ $search }}"
                                   maxlength="100"
                                   placeholder="Nombre o correo institucional..."
                                   class="w-full rounded-2xl border px-4 py-2.5 text-sm"
                                   style="border-color: var(--color-border); background: var(--color-bg); color: var(--color-text);">
                        </div>
                        <button type="submit" class="btn-lumina btn-3d-green text-xs px-5 py-2.5 font-bold" data-loading-text="Buscando...">Buscar</button>
                        @if ($search !== '')
                            <a href="{{ route('professor.dashboard') }}" class="btn-secondary text-xs px-3 py-2.5 font-semibold">
                                Limpiar
                            </a>
                        @endif
                    </form>
                </div>

                @if ($students->isEmpty())
                    <div class="p-12 text-center border rounded-2xl" style="border-color: var(--color-border); background: var(--color-bg);">
                        <p class="text-sm font-semibold" style="color: var(--color-text-secondary);" role="status">
                            {{ $search !== '' ? 'No se encontraron estudiantes que coincidan con la búsqueda.' : 'No hay estudiantes registrados en este momento.' }}
                        </p>
                    </div>
                @else
                    <div class="table-shell">
                        <table class="w-full min-w-[900px] text-sm">
                            <caption class="sr-only">Progreso, experiencia y tasa de aprobación de estudiantes institucionales</caption>
                            <thead>
                                <tr class="text-left border-b" style="border-color: var(--color-border);">
                                    <th scope="col" class="p-4 font-bold text-xs uppercase tracking-wider" style="color: var(--color-text-secondary);">Estudiante</th>
                                    <th scope="col" class="p-4 font-bold text-xs uppercase tracking-wider" style="color: var(--color-text-secondary);">Correo</th>
                                    <th scope="col" class="p-4 font-bold text-xs uppercase tracking-wider text-center" style="color: var(--color-text-secondary);"><span lang="en">XP</span></th>
                                    <th scope="col" class="p-4 font-bold text-xs uppercase tracking-wider text-center" style="color: var(--color-text-secondary);">Avance de Ruta</th>
                                    <th scope="col" class="p-4 font-bold text-xs uppercase tracking-wider text-center" style="color: var(--color-text-secondary);">Aprobación</th>
                                    <th scope="col" class="p-4 font-bold text-xs uppercase tracking-wider text-right" style="color: var(--color-text-secondary);">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color: var(--color-border);">
                                @foreach ($students as $student)
                                    @php
                                        $displayName = trim(implode(' ', array_filter([
                                            $student->user_name,
                                            $student->user_last_name,
                                            $student->user_middle_name,
                                        ])));
                                         $progressPercent = $totalLessons > 0
                                             ? min(100, round(($student->completed_lessons_count / $totalLessons) * 100))
                                             : 0;
                                        $studentApproval = $student->attempts_count > 0
                                            ? round(($student->passed_attempts_count / $student->attempts_count) * 100, 1)
                                            : null;
                                    @endphp
                                    <tr class="hover:bg-slate-500/5 transition">
                                        <th scope="row" class="p-4 text-left font-bold" style="color: var(--color-text);">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs"
                                                     style="background: color-mix(in srgb, var(--color-primary) 12%, transparent); color: var(--color-primary);">
                                                    {{ strtoupper(substr($student->user_name, 0, 1)) }}
                                                </div>
                                                <span>{{ $displayName }}</span>
                                            </div>
                                        </th>
                                        <td class="p-4 font-mono text-xs" style="color: var(--color-text-secondary);">{{ $student->user_email }}</td>
                                        <td class="p-4 text-center font-mono font-bold text-amber-500">{{ number_format($student->xp ?? 0) }}</td>
                                        <td class="p-4 text-center">
                                            <div class="inline-flex items-center gap-2.5">
                                                <div class="progress-bar w-24 h-2"
                                                     role="progressbar"
                                                     aria-label="Progreso de {{ $displayName }}"
                                                     aria-valuenow="{{ $progressPercent }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                    <div class="progress-bar-fill" style="width: {{ $progressPercent }}%;"></div>
                                                </div>
                                                <span class="text-xs font-mono font-semibold" style="color: var(--color-text-secondary);">
                                                     {{ $student->completed_lessons_count }}/{{ $totalLessons }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="p-4 text-center" style="color: var(--color-text-secondary);">
                                            @if ($studentApproval === null)
                                                <span class="text-xs text-slate-400">Sin intentos</span>
                                            @else
                                                <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400">{{ $studentApproval }}%</span>
                                                <span class="block text-[11px] text-slate-400">{{ $student->passed_attempts_count }}/{{ $student->attempts_count }} eval.</span>
                                            @endif
                                        </td>
                                        <td class="p-4 text-right">
                                            <a href="{{ route('professor.student-progress', $student) }}"
                                               class="btn-secondary text-xs px-3.5 py-1.5 font-bold inline-flex items-center gap-1"
                                               aria-label="Ver progreso de {{ $displayName }}">
                                                <span>Detalle</span>
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $students->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
