<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white leading-tight tracking-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @php
        $user = Auth::user();
        $totalXp = $user->progress()->count();
        $completedCount = $user->progress()->count();
        $totalLessons = \App\Models\Lesson::count();
        $completionPct = $totalLessons > 0 ? round(($completedCount / $totalLessons) * 100) : 0;
    @endphp

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Stats bar gamificada --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="solid-card p-5 text-center animate-fade-up">
                    <div class="text-3xl font-bold gradient-text font-display">{{ $totalXp }}</div>
                    <div class="text-xs font-medium mt-1" style="color: var(--color-text-secondary);">XP total</div>
                    <div class="mt-2">
                        <span class="xp-badge text-[10px]">+0 esta semana</span>
                    </div>
                </div>
                <div class="solid-card p-5 text-center animate-fade-up" style="animation-delay: 0.05s;">
                    <div class="text-3xl font-bold font-display" style="color: var(--color-primary);">{{ $completedCount }}</div>
                    <div class="text-xs font-medium mt-1" style="color: var(--color-text-secondary);">Lecciones</div>
                    <div class="mt-2 progress-bar max-w-[80%] mx-auto">
                        <div class="progress-bar-fill" style="width: {{ $completionPct }}%;"></div>
                    </div>
                </div>
                <div class="solid-card p-5 text-center animate-fade-up" style="animation-delay: 0.1s;">
                    <div class="text-3xl font-bold font-display" style="color: var(--color-accent);">{{ $totalLessons }}</div>
                    <div class="text-xs font-medium mt-1" style="color: var(--color-text-secondary);">Total lecciones</div>
                    <div class="mt-2">
                        <span class="stat-pill text-xs">
                            <span>🔥</span>
                            <span>Racha: 0 días</span>
                        </span>
                    </div>
                </div>
                <div class="solid-card p-5 text-center animate-fade-up" style="animation-delay: 0.15s;">
                    <div class="text-3xl font-bold font-display" style="color: var(--color-purple);">{{ $completionPct }}%</div>
                    <div class="text-xs font-medium mt-1" style="color: var(--color-text-secondary);">Completado</div>
                    <div class="mt-2">
                        <span class="achievement text-[10px]">
                            @if ($completionPct >= 100) 🏆 Maestro
                            @elseif ($completionPct >= 50) ⭐ Intermedio
                            @elseif ($completionPct > 0) 🌱 Principiante
                            @else 🚀 Empezar
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            {{-- Bienvenida con búho --}}
            <div class="solid-card p-6 sm:p-8 flex items-center gap-6 animate-fade-up">
                <div class="relative shrink-0">
                    <span class="text-6xl block animate-float" role="img" aria-label="Búho tutor">🦉</span>
                    <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-green-400 border-2 border-white"></div>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-display font-bold text-2xl" style="color: var(--color-text);">
                        ¡Hola, {{ $user->name }}! 👋
                    </h3>
                    <p class="mt-1.5 text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                        @if ($completedCount === 0)
                            ¡Bienvenido! Completa tu primera lección para empezar tu viaje en inglés.
                        @elseif ($completionPct < 50)
                            ¡Vas muy bien! Sigue completando lecciones para avanzar al siguiente nivel.
                        @elseif ($completionPct < 100)
                            ¡Estás a {{ 100 - $completionPct }}% de completar el curso! Sigue así.
                        @else
                            ¡Felicidades! Has completado todas las lecciones. Eres un maestro del inglés 🎉
                        @endif
                    </p>
                    <div class="flex items-center gap-3 mt-4">
                        <a href="{{ route('levels.index') }}" class="btn-primary text-xs px-5 py-2.5">
                            {{ $completedCount === 0 ? 'Comenzar' : 'Continuar' }} aprendizaje
                        </a>
                        @if ($completedCount > 0)
                            <span class="text-xs font-medium" style="color: var(--color-text-secondary);">
                                Última lección: —
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tarjetas de acceso rápido --}}
            <div class="grid gap-4 sm:grid-cols-3 animate-fade-up">
                <a href="{{ route('levels.index') }}"
                   class="solid-card p-5 flex items-start gap-4 group cursor-pointer transition-all duration-300 hover:translate-y-[-2px]">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl shrink-0"
                         style="background: linear-gradient(135deg, var(--color-accent-light), var(--color-accent));">
                        💬
                    </div>
                    <div>
                        <h4 class="font-display font-bold text-sm" style="color: var(--color-text);">Chat con la IA</h4>
                        <p class="mt-0.5 text-xs leading-relaxed" style="color: var(--color-text-secondary);">
                            Conversa libremente con el búho tutor.
                        </p>
                    </div>
                </a>

                <a href="{{ route('levels.index') }}"
                   class="solid-card p-5 flex items-start gap-4 group cursor-pointer transition-all duration-300 hover:translate-y-[-2px]">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl shrink-0"
                         style="background: linear-gradient(135deg, var(--color-primary-light), var(--color-primary));">
                        📚
                    </div>
                    <div>
                        <h4 class="font-display font-bold text-sm" style="color: var(--color-text);">Lecciones</h4>
                        <p class="mt-0.5 text-xs leading-relaxed" style="color: var(--color-text-secondary);">
                            Grammar, Vocabulary y más.
                        </p>
                    </div>
                </a>

                <a href="{{ route('levels.index') }}"
                   class="solid-card p-5 flex items-start gap-4 group cursor-pointer transition-all duration-300 hover:translate-y-[-2px]">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl shrink-0"
                         style="background: linear-gradient(135deg, var(--color-warning), var(--color-accent));">
                        📊
                    </div>
                    <div>
                        <h4 class="font-display font-bold text-sm" style="color: var(--color-text);">Tu progreso</h4>
                        <p class="mt-0.5 text-xs leading-relaxed" style="color: var(--color-text-secondary);">
                            Estadísticas y nivel actual.
                        </p>
                    </div>
                </a>
            </div>

            {{-- Friendly Tip --}}
            <div class="friendly-tip animate-fade-up">
                <div class="friendly-tip-header">
                    <span>✨</span>
                    <span>Friendly Tip</span>
                </div>
                <div class="friendly-tip-body">
                    <strong>Good try!</strong> Let's look at this rule: usa <em>"I am"</em> para el presente simple del verbo "to be" en primera persona singular.
                    <br><span class="text-xs mt-1.5 block" style="color: var(--color-text-secondary);">Ejemplo: "I am a student at UTBIS."</span>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
