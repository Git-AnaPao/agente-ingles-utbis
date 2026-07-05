<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white leading-tight">
            📚 Panel del Profesor
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Stats --}}
            <div class="rounded-2xl p-4 flex items-center justify-around gap-4 shadow-sm text-white text-sm font-semibold"
                 style="background: linear-gradient(135deg, #27594B, #518C4F);">
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
            <div class="rounded-2xl bg-white shadow-sm p-6">
                <h3 class="font-display font-bold text-lg mb-4" style="color: #27594B;">
                    Estudiantes
                </h3>

                @if ($students->isEmpty())
                    <p class="text-sm" style="color: #6B7280;">No hay estudiantes registrados.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left border-b" style="border-color: #E5E7EB;">
                                    <th class="pb-3 font-semibold" style="color: #374151;">Nombre</th>
                                    <th class="pb-3 font-semibold" style="color: #374151;">Email</th>
                                    <th class="pb-3 font-semibold text-center" style="color: #374151;">Lecciones</th>
                                    <th class="pb-3 font-semibold text-center" style="color: #374151;">Progreso</th>
                                    <th class="pb-3 font-semibold text-right" style="color: #374151;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($students as $student)
                                    @php
                                        $totalSubs = \App\Models\Lesson::count();
                                        $pct = $totalSubs > 0 ? round(($student->progress_count / $totalSubs) * 100) : 0;
                                    @endphp
                                    <tr class="border-b" style="border-color: #F3F4F6;">
                                        <td class="py-3 font-medium" style="color: #1f2937;">{{ $student->name }}</td>
                                        <td class="py-3" style="color: #6B7280;">{{ $student->email }}</td>
                                        <td class="py-3 text-center" style="color: #6B7280;">{{ $student->progress_count }}</td>
                                        <td class="py-3 text-center">
                                            <div class="inline-flex items-center gap-2">
                                                <div class="w-24 h-2 rounded-full" style="background: #E5E7EB;">
                                                    <div class="h-full rounded-full" style="background: #518C4F; width: {{ $pct }}%;"></div>
                                                </div>
                                                <span class="text-xs font-semibold" style="color: #518C4F;">{{ $pct }}%</span>
                                            </div>
                                        </td>
                                        <td class="py-3 text-right">
                                            <a href="{{ route('professor.student-progress', $student) }}"
                                               class="inline-block px-3 py-1.5 rounded-lg text-xs font-bold text-white transition hover:scale-105"
                                               style="background: #27594B;">
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
