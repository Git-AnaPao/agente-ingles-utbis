<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white leading-tight">
            📚 Panel del Profesor
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Stats --}}
            <div class="solid-card p-4 flex items-center justify-around gap-4 text-white text-sm font-semibold"
                 style="background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light)); border-color: transparent;">
                <div class="text-center">
                    <span class="block text-2xl">{{ $totalStudents }}</span>
                    <span class="text-white/80 text-xs">Estudiantes</span>
                </div>
                <div class="text-center">
                    <span class="block text-2xl">{{ \App\Models\Lesson::count() }}</span>
                    <span class="text-white/80 text-xs">Lecciones totales</span>
                </div>
                <div class="text-center">
                    <span class="block text-2xl">{{ $students->sum(fn($s) => $s->progress_count) }}</span>
                    <span class="text-white/80 text-xs">Lecciones completadas</span>
                </div>
            </div>

            {{-- Student list --}}
            <div class="solid-card p-6">
                <h3 class="font-display font-bold text-lg mb-4" style="color: var(--color-primary);">
                    Estudiantes
                </h3>

                @if ($students->isEmpty())
                    <p class="text-sm" style="color: var(--color-text-secondary);">No hay estudiantes registrados.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left border-b" style="border-color: var(--color-border);">
                                    <th class="pb-3 font-semibold" style="color: var(--color-text);">Nombre</th>
                                    <th class="pb-3 font-semibold" style="color: var(--color-text);">Email</th>
                                    <th class="pb-3 font-semibold text-center" style="color: var(--color-text);">Lecciones</th>
                                    <th class="pb-3 font-semibold text-center" style="color: var(--color-text);">Progreso</th>
                                    <th class="pb-3 font-semibold text-right" style="color: var(--color-text);">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($students as $student)
                                    @php
                                        $totalSubs = \App\Models\Lesson::count();
                                        $pct = $totalSubs > 0 ? round(($student->progress_count / $totalSubs) * 100) : 0;
                                    @endphp
                                    <tr class="border-b" style="border-color: var(--color-border);">
                                        <td class="py-3 font-medium" style="color: var(--color-text);">{{ $student->name }}</td>
                                        <td class="py-3" style="color: var(--color-text-secondary);">{{ $student->email }}</td>
                                        <td class="py-3 text-center" style="color: var(--color-text-secondary);">{{ $student->progress_count }}</td>
                                        <td class="py-3 text-center">
                                            <div class="inline-flex items-center gap-2">
                                                <div class="w-24 h-2 rounded-full" style="background: var(--color-border);">
                                                    <div class="h-full rounded-full" style="background: var(--color-primary-light); width: {{ $pct }}%;"></div>
                                                </div>
                                                <span class="text-xs font-semibold" style="color: var(--color-primary-light);">{{ $pct }}%</span>
                                            </div>
                                        </td>
                                        <td class="py-3 text-right">
                                            <a href="{{ route('professor.student-progress', $student) }}"
                                               class="inline-block px-3 py-1.5 rounded-lg text-xs font-bold text-white transition hover:scale-105"
                                               style="background: var(--color-primary);">
                                                Ver progreso
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
