<x-app-layout title="Catálogo de Listening">
    @php
        $currentLevel = $allLevels[$level];
        $levelDescriptions = [
            'A1' => 'Principiante · Breakthrough',
            'A2' => 'Elemental · Waystage',
            'B1' => 'Intermedio · Threshold',
            'B2' => 'Intermedio Alto · Vantage',
            'C1' => 'Avanzado · Effective Proficiency',
            'C2' => 'Maestría · Mastery',
        ];
        $subLevelNames = [
            1 => 'Fundamentos de Escucha',
            2 => 'Desarrollo Auditivo',
            3 => 'Consolidación & Fluidez',
            4 => 'Profundización & Matices',
            5 => 'Maestría de Comprensión',
        ];
        $availableLevels = collect($allLevels)->where('unlocked', true)->count();
    @endphp

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
            
            {{-- Header del Catálogo EF English Studio --}}
            <header class="ef-unit-card border-2 flex flex-col justify-between gap-6 sm:flex-row sm:items-center animate-fade-up"
                    style="border-color: color-mix(in srgb, var(--color-blue) 30%, var(--color-border));">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 border-2 shadow-sm"
                         style="background: rgba(2, 132, 199, 0.12); border-color: rgba(2, 132, 199, 0.3); color: #0284C7;">
                        <x-icon name="headphones" class="w-7 h-7 text-sky-500" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="ef-skill-pill ef-skill-listening text-[10px]">Listening Studio</span>
                            <span class="text-xs font-bold font-mono uppercase text-sky-600 dark:text-sky-400">UTBIS Campus</span>
                        </div>
                        <h1 class="mt-1 font-display text-2xl sm:text-3xl font-black tracking-tight" style="color: var(--color-text);">
                            Catálogo de Listening
                        </h1>
                        <p class="text-xs sm:text-sm mt-0.5" style="color: var(--color-text-secondary);">
                            Práctica de comprensión auditiva interactiva con audios nativos, transcripciones y síntesis vocal.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('levels.index') }}" class="btn-duo btn-duo-outline text-xs py-2 px-3.5">
                        ← Mapa CEFR
                    </a>
                </div>
            </header>

            {{-- Métricas de Catálogo Duolingo + EF --}}
            <section class="grid grid-cols-3 gap-3 sm:gap-4" aria-label="Resumen del catálogo">
                <div class="solid-card p-4 sm:p-5 text-center border" style="border-color: var(--color-card-border);">
                    <span class="block font-display text-2xl sm:text-3xl font-black text-emerald-500">{{ collect($allLevels)->sum('total') }}</span>
                    <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider" style="color: var(--color-text-secondary);">Actividades Totales</span>
                </div>
                <div class="solid-card p-4 sm:p-5 text-center border" style="border-color: var(--color-card-border);">
                    <span class="block font-display text-2xl sm:text-3xl font-black text-sky-500">{{ collect($allLevels)->sum('with_audio') }}</span>
                    <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider" style="color: var(--color-text-secondary);">Con Audio Nativo</span>
                </div>
                <div class="solid-card p-4 sm:p-5 text-center border" style="border-color: var(--color-card-border);">
                    <span class="block font-display text-2xl sm:text-3xl font-black text-amber-500">{{ $availableLevels }}</span>
                    <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider" style="color: var(--color-text-secondary);">Niveles Abiertos</span>
                </div>
            </section>

            {{-- Selector Táctil 3D de Nivel CEFR (Duolingo Style) --}}
            <nav class="grid grid-cols-3 gap-2.5 sm:grid-cols-6" aria-label="Niveles CEFR">
                @foreach ($allLevels as $cefr => $data)
                    @if ($data['unlocked'])
                        <a href="{{ route('listening.index', ['level' => $cefr]) }}"
                           class="btn-duo {{ $level === $cefr ? 'btn-duo-blue' : 'btn-duo-outline' }} p-3 text-center flex-col gap-0.5 rounded-2xl h-auto"
                           @if ($level === $cefr) aria-current="page" @endif>
                            <span class="block font-display text-base font-black font-mono">{{ $cefr }}</span>
                            <span class="block text-[10px] {{ $level === $cefr ? 'text-white/90' : 'text-slate-400' }}">{{ $data['total'] }} act.</span>
                            @if ($data['placement_entry'])
                                <span class="mt-1 block text-[8px] font-bold uppercase tracking-wider bg-white/20 rounded px-1">Placement</span>
                            @endif
                        </a>
                    @else
                        <div class="btn-duo btn-duo-outline p-3 text-center flex-col gap-0.5 rounded-2xl h-auto opacity-40 cursor-not-allowed">
                            <span class="block font-display text-base font-black font-mono">{{ $cefr }}</span>
                            <span class="block text-[10px] text-slate-400">Bloqueado</span>
                        </div>
                    @endif
                @endforeach
            </nav>

            {{-- Resumen del Nivel Activo EF English --}}
            <section class="ef-unit-card border p-6 sm:p-7" style="border-color: var(--color-card-border);">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl font-display font-mono text-xl font-black shrink-0 border-2"
                             style="background: rgba(2, 132, 199, 0.12); color: #0284C7; border-color: rgba(2, 132, 199, 0.3);">
                            {{ $level }}
                        </div>
                        <div>
                            <h2 class="font-display text-lg sm:text-xl font-black" style="color: var(--color-text);">{{ $levelDescriptions[$level] }}</h2>
                            <p class="text-xs sm:text-sm" style="color: var(--color-text-secondary);">{{ $currentLevel['total'] }} actividades formativas · {{ $currentLevel['with_audio'] }} con archivo de audio</p>
                        </div>
                    </div>
                    @if ($currentLevel['placement_entry'])
                        <span class="rounded-full px-3.5 py-1 text-xs font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
                            <x-icon name="target" class="w-3.5 h-3.5" />
                            <span>Nivel Recomendado por Diagnóstico</span>
                        </span>
                    @endif
                </div>
            </section>

            {{-- Cuadrícula de Actividades por Subnivel --}}
            @forelse ($groupedLessons as $subLevel => $subLevelLessons)
                <section class="space-y-4">
                    <header class="flex items-end justify-between gap-3">
                        <div>
                            <span class="text-xs font-mono font-bold uppercase text-sky-600 dark:text-sky-400">{{ $level }}.{{ $subLevel }}</span>
                            <h3 class="font-display text-lg font-extrabold" style="color: var(--color-text);">{{ $subLevelNames[$subLevel] ?? 'Subnivel '.$subLevel }}</h3>
                        </div>
                        <span class="text-xs font-mono text-slate-400">{{ $subLevelLessons->count() }} actividades</span>
                    </header>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($subLevelLessons as $activity)
                            @php
                                $questionCount = count($activity->questions_data ?? []);
                                $hasAudio = (bool) $activity->audio_url;
                            @endphp
                            <a href="{{ route('listening.show', $activity) }}" 
                               class="group solid-card relative overflow-hidden p-5 transition-all duration-200 hover:-translate-y-1 hover:shadow-md hover:border-sky-500/50 flex flex-col justify-between border"
                               style="border-color: var(--color-card-border);">
                                <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $hasAudio ? 'from-sky-400 to-blue-600' : 'from-emerald-400 to-emerald-600' }}"></span>
                                
                                <div>
                                    <div class="mb-3 flex items-start justify-between gap-2">
                                        <span class="rounded-lg px-2 py-0.5 text-xs font-mono font-bold" style="background: var(--color-bg); color: var(--color-text-secondary);">#{{ $activity->sort_order }}</span>
                                        <span class="ef-skill-pill {{ $hasAudio ? 'ef-skill-listening' : 'ef-skill-reading' }} text-[10px] inline-flex items-center gap-1">
                                            <x-icon :name="$hasAudio ? 'headphones' : 'book-open'" class="w-3 h-3" />
                                            <span>{{ $hasAudio ? 'Audio Studio' : 'Guion / TTS' }}</span>
                                        </span>
                                    </div>
                                    <h4 class="font-display text-sm font-bold leading-snug group-hover:text-sky-500 transition duration-200" lang="en" style="color: var(--color-text);">{{ $activity->title }}</h4>
                                    @if ($activity->description)
                                        <p class="mt-2 line-clamp-2 text-xs leading-relaxed" lang="en" style="color: var(--color-text-secondary);">{{ $activity->description }}</p>
                                    @endif
                                </div>

                                <div class="mt-4 flex items-center justify-between border-t pt-3" style="border-color: var(--color-border);">
                                    <span class="text-xs font-mono" style="color: var(--color-text-secondary);">{{ $questionCount }} preguntas</span>
                                    <span class="text-sm font-bold transition-transform duration-200 group-hover:translate-x-1" style="color: var(--color-primary);">→</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @empty
                <section class="solid-card p-12 text-center border" style="border-color: var(--color-card-border);">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-sky-500/10 text-sky-500">
                        <x-icon name="headphones" class="w-8 h-8" />
                    </div>
                    <h3 class="font-display text-lg font-bold" style="color: var(--color-text);">Sin actividades disponibles</h3>
                    <p class="mt-2 text-sm max-w-sm mx-auto" style="color: var(--color-text-secondary);">El nivel {{ $level }} se encuentra abierto pero todavía no contiene grabaciones publicadas.</p>
                </section>
            @endforelse
        </div>
    </div>
</x-app-layout>
