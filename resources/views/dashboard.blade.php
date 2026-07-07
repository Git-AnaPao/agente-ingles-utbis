<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @php
        $user = Auth::user();
        $totalXp = $user->progress()->where('student_current_status', 'completed')->sum('student_xp_earned');
        $completedCount = $user->progress()->where('student_current_status', 'completed')->count();
        $totalLessons = \App\Models\Lesson::count();
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Stats bar --}}
            <div class="rounded-2xl p-4 flex items-center justify-around gap-4 shadow-sm text-white text-sm font-semibold"
                 style="background: linear-gradient(135deg, var(--color-primary), var(--color-accent));">
                <div class="text-center">
                    <span class="block text-2xl">{{ $totalXp }}</span>
                    <span class="text-white/80 text-xs">XP total</span>
                </div>
                <div class="text-center">
                    <span class="block text-2xl">{{ $completedCount }}/{{ $totalLessons }}</span>
                    <span class="text-white/80 text-xs">Lecciones</span>
                </div>
            </div>

            {{-- Bienvenida con búho --}}
            <div class="rounded-2xl p-6 flex items-center gap-5 shadow-sm"
                 style="background-color: var(--color-primary);">
                <span class="text-5xl shrink-0" role="img" aria-label="Búho tutor">🦉</span>
                <div>
                    <h3 class="font-display font-bold text-xl text-white">
                        ¡Hola, {{ $user->name }}! 👋
                    </h3>
                    <p class="mt-1 text-sm text-white/80" style="font-family:Inter,sans-serif;">
                        ¡Hoy es un gran día para aprender! Completa una lección y mejora tu inglés.
                    </p>
                </div>
            </div>

            {{-- Tarjetas de acceso rápido --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                <a href="{{ route('levels.index') }}"
                   class="group rounded-2xl p-5 flex items-start gap-4 cursor-pointer transition hover:shadow-md"
                   style="background-color: var(--color-card); border-left: 4px solid var(--color-highlight); box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);">
                    <span class="text-3xl shrink-0" role="img" aria-label="Chat con IA">💬</span>
                    <div>
                        <h4 class="font-display font-bold text-base" style="color: var(--color-primary);">Chat con la IA</h4>
                        <p class="mt-0.5 text-xs" style="color: var(--color-text-secondary); font-family:Inter,sans-serif;">
                            Conversa libremente con el búho tutor.
                        </p>
                    </div>
                </a>

                <a href="{{ route('levels.index') }}"
                   class="group rounded-2xl p-5 flex items-start gap-4 cursor-pointer transition hover:shadow-md"
                   style="background-color: var(--color-card); border-left: 4px solid var(--color-accent); box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);">
                    <span class="text-3xl shrink-0" role="img" aria-label="Lecciones">📚</span>
                    <div>
                        <h4 class="font-display font-bold text-base" style="color: var(--color-primary);">Lecciones</h4>
                        <p class="mt-0.5 text-xs" style="color: var(--color-text-secondary); font-family:Inter,sans-serif;">
                            Grammar, Vocabulary y más.
                        </p>
                    </div>
                </a>

                <a href="{{ route('levels.index') }}"
                   class="group rounded-2xl p-5 flex items-start gap-4 cursor-pointer transition hover:shadow-md"
                   style="background-color: var(--color-card); border-left: 4px solid var(--color-warning); box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);">
                    <span class="text-3xl shrink-0" role="img" aria-label="Progreso">📊</span>
                    <div>
                        <h4 class="font-display font-bold text-base" style="color: var(--color-primary);">Tu progreso</h4>
                        <p class="mt-0.5 text-xs" style="color: var(--color-text-secondary); font-family:Inter,sans-serif;">
                            Estadísticas y nivel actual.
                        </p>
                    </div>
                </a>
            </div>

            {{-- Friendly Tip --}}
            <div class="friendly-tip">
                <div class="friendly-tip-header">
                    ✨ Friendly Tip
                </div>
                <div class="friendly-tip-body">
                    <strong>Good try!</strong> Let's look at this rule: usa <em>"I am"</em> para el presente simple del verbo "to be" en primera persona singular.
                    <br><span class="text-xs mt-1 block" style="color: var(--color-text-secondary);">Ejemplo: "I am a student at UTBIS."</span>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
