<x-app-layout title="Ruta de Aprendizaje CEFR">
    @php
        $levelDescriptions = [
            'A1' => [
                'name' => 'Principiante · Breakthrough',
                'description' => 'Comprender y utilizar expresiones cotidianas de uso muy frecuente y frases sencillas.',
                'color' => '#10B981',
            ],
            'A2' => [
                'name' => 'Elemental · Waystage',
                'description' => 'Comprender frases y expresiones de uso frecuente relacionadas con áreas de relevancia inmediata.',
                'color' => '#06B6D4',
            ],
            'B1' => [
                'name' => 'Intermedio · Threshold',
                'description' => 'Comprender los puntos principales de textos claros si tratan sobre temas que le son conocidos.',
                'color' => '#3B82F6',
            ],
            'B2' => [
                'name' => 'Intermedio Alto · Vantage',
                'description' => 'Entender las ideas principales de textos complejos que traten de temas tanto concretos como abstractos.',
                'color' => '#6366F1',
            ],
            'C1' => [
                'name' => 'Avanzado · Effective Proficiency',
                'description' => 'Expresarse de forma fluida y espontánea para fines sociales, académicos y profesionales con flexibilidad y precisión.',
                'color' => '#8B5CF6',
            ],
            'C2' => [
                'name' => 'Maestría · Mastery',
                'description' => 'Comprender con facilidad prácticamente todo lo que oye o lee y reconstruir información y argumentos con coherencia.',
                'color' => '#EC4899',
            ],
        ];

        $skillMeta = [
            'reading' => [
                'label' => 'Reading',
                'icon' => 'book-open',
                'pill_class' => 'ef-skill-reading',
            ],
            'listening' => [
                'label' => 'Listening',
                'icon' => 'headphones',
                'pill_class' => 'ef-skill-listening',
            ],
            'speaking' => [
                'label' => 'Speaking',
                'icon' => 'mic',
                'pill_class' => 'ef-skill-speaking',
            ],
        ];
        $allSubLevels = collect($levels)->pluck('sub_levels')->flatten(1);
        $totalNodes = $allSubLevels->count();
        $completedNodes = $allSubLevels->where('completed', true)->count();
        $completionPct = $totalNodes > 0 ? (int) round(($completedNodes / $totalNodes) * 100) : 0;
        $totalXp = $gamification['total_xp'] ?? (auth()->user()->xp ?? 0);
    @endphp

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
            
            {{-- ══════════════════════════════════════════════════════════
                 HÉROE DEL MAPA CEFR (EF ENGLISH + DUOLINGO STATS)
                 ══════════════════════════════════════════════════════════ --}}
            <section class="ef-unit-card border-2 relative overflow-hidden animate-fade-up"
                     style="border-color: color-mix(in srgb, var(--color-primary) 30%, var(--color-border));">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="ef-cefr-badge">Ruta CEFR Internacional</span>
                            <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400">UTBIS English Framework</span>
                        </div>
                        <h1 class="font-display font-black text-2xl sm:text-4xl tracking-tight" style="color: var(--color-text);">
                            Tu Camino de Aprendizaje
                        </h1>
                        <p class="max-w-2xl text-xs sm:text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                            Punto de entrada asignado: 
                            <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-md">
                                Nivel {{ $placement?->result_level ?? 'A1' }}
                            </span>. 
                            Avanza subnivel a subnivel dominando lectura, comprensión auditiva y conversación con IA.
                        </p>
                    </div>

                    {{-- Mini Resumen Gamificado Duolingo --}}
                    <div class="grid grid-cols-3 gap-2.5 shrink-0 min-w-[260px] text-center">
                        <div class="p-3 rounded-2xl border" style="background: var(--color-bg); border-color: var(--color-border);">
                            <span class="block font-display font-black text-lg text-amber-500">{{ number_format($totalXp) }}</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--color-text-secondary);">XP Total</span>
                        </div>
                        <div class="p-3 rounded-2xl border" style="background: var(--color-bg); border-color: var(--color-border);">
                            <span class="font-display font-black text-lg text-orange-500 flex items-center justify-center gap-1">
                                <span>{{ $gamification['current_streak'] ?? 0 }}</span>
                                <x-icon name="flame" class="w-4 h-4 text-orange-500 animate-pulse" />
                            </span>
                            <span class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--color-text-secondary);">Racha</span>
                        </div>
                        <div class="p-3 rounded-2xl border" style="background: var(--color-bg); border-color: var(--color-border);">
                            <span class="block font-display font-black text-lg text-emerald-500">{{ $completedNodes }}/{{ $totalNodes }}</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--color-text-secondary);">Nodos</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t flex items-center justify-between gap-4" style="border-color: var(--color-border);">
                    <div class="flex-1">
                        <div class="flex justify-between text-xs font-bold mb-1.5">
                            <span style="color: var(--color-text);">Progreso Total de la Carrera</span>
                            <span class="font-mono text-emerald-600 dark:text-emerald-400">{{ $completionPct }}%</span>
                        </div>
                        <div class="progress-bar h-3" role="progressbar" aria-valuenow="{{ $completionPct }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar-fill" style="width: {{ $completionPct }}%;"></div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ══════════════════════════════════════════════════════════
                 LISTA DE UNIDADES CEFR & NODOS DE LECCIÓN DUOLINGO
                 ══════════════════════════════════════════════════════════ --}}
            <div class="space-y-10">
                @foreach ($levels as $level)
                    @php
                        $isLocked = $level['status'] === 'locked';
                        $levelPct = $level['total'] > 0 ? (int) round(($level['completed'] / $level['total']) * 100) : 0;
                        $cefrMeta = $levelDescriptions[$level['cefr']] ?? [
                            'name' => 'Nivel '.$level['cefr'],
                            'description' => 'Módulo formativo de inglés.',
                            'color' => '#10B981',
                        ];
                    @endphp

                    <section id="level-{{ $level['cefr'] }}" 
                             class="ef-unit-card border-2 {{ $level['placement_entry'] ? 'ring-2 ring-emerald-500/40 shadow-glow' : '' }}"
                             @if ($isLocked) style="opacity: 0.75;" @endif>
                        
                        {{-- Cabecera de la Unidad EF English --}}
                        <header class="ef-unit-header">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl font-display font-black text-xl font-mono shrink-0 border-2 shadow-sm"
                                     style="background: color-mix(in srgb, var(--color-primary) 12%, var(--color-card)); border-color: var(--color-primary); color: var(--color-primary);">
                                    {{ $level['cefr'] }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="font-display text-lg sm:text-xl font-black" style="color: var(--color-text);">
                                            Nivel {{ $level['cefr'] }} · {{ $cefrMeta['name'] }}
                                        </h2>
                                        @if ($level['placement_entry'])
                                            <span class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase px-2.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                                🎯 Punto de entrada asignado
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs mt-1 leading-relaxed" style="color: var(--color-text-secondary);">
                                        {{ $cefrMeta['description'] }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                <div class="text-right">
                                    <span class="text-xs font-mono font-black block" style="color: var(--color-text);">{{ $level['completed'] }}/{{ $level['total'] }} Hitos</span>
                                    <span class="text-[10px] uppercase font-bold" style="color: var(--color-text-secondary);">{{ $levelPct }}% Dominado</span>
                                </div>
                                <div class="w-20 sm:w-28 progress-bar h-2.5">
                                    <div class="progress-bar-fill" style="width: {{ $levelPct }}%;"></div>
                                </div>
                            </div>
                        </header>

                        {{-- Lista de Nodos / Subniveles --}}
                        @if (empty($level['sub_levels']))
                            <div class="py-12 text-center text-xs sm:text-sm" style="color: var(--color-text-secondary);">
                                📚 Contenido formativo en preparación para este nivel.
                            </div>
                        @else
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($level['sub_levels'] as $node)
                                    @php
                                        $lesson = $node['lesson'];
                                        $topic = $node['topics'][0] ?? ($lesson->lesson_prompt_payload['topic'] ?? ('Unidad '.$level['cefr'].'.'.$node['sub_level']));
                                    @endphp

                                    <article class="solid-card p-5 border flex flex-col justify-between transition-all duration-300 {{ $node['completed'] ? 'border-emerald-500/30 bg-emerald-500/5' : '' }} {{ $isLocked ? 'opacity-60' : 'hover:-translate-y-1 hover:shadow-md' }}"
                                             style="border-color: var(--color-card-border);">
                                        <div>
                                            {{-- Indicador de Subnivel --}}
                                            <div class="flex items-center justify-between gap-2 mb-3">
                                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-mono font-bold"
                                                      style="background: var(--color-bg); color: var(--color-text-secondary);">
                                                    Subnivel {{ $node['sub_level'] }}
                                                </span>

                                                {{-- Estado del Nodo Duolingo --}}
                                                @if ($node['completed'])
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                                        ✓ Dominado
                                                    </span>
                                                @elseif ($isLocked)
                                                    <span class="text-xs text-slate-400 font-bold flex items-center gap-1">
                                                        🔒 Bloqueado
                                                    </span>
                                                @elseif ($node['attempted'])
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-extrabold px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                                        En progreso
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                                        Disponible
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Título y Tema --}}
                                            <div class="mb-4">
                                                <span class="text-[10px] font-bold uppercase tracking-wider block" style="color: var(--color-text-secondary);">
                                                    {{ $node['completed'] ? 'Hito Académico' : 'Objetivo de Estudio' }}
                                                </span>
                                                <h3 class="font-display font-extrabold text-sm sm:text-base mt-0.5 line-clamp-2" style="color: var(--color-text);" title="{{ $topic }}">
                                                    {{ $topic }}
                                                </h3>
                                            </div>

                                            {{-- Pestañas de Habilidades EF English con Iconos Vectoriales --}}
                                            <div class="space-y-1.5 mb-4">
                                                @foreach ($skillMeta as $skill => $meta)
                                                    @php $state = $node['skills'][$skill]; @endphp
                                                    <div class="flex items-center justify-between p-2 rounded-xl text-xs font-semibold border"
                                                         style="background: var(--color-card); border-color: var(--color-border);">
                                                        <span class="flex items-center gap-2">
                                                            <x-icon :name="$meta['icon']" class="w-3.5 h-3.5 text-slate-400" />
                                                            <span style="color: var(--color-text);">{{ $meta['label'] }}</span>
                                                        </span>
                                                        @if ($state['mastered'])
                                                            <span class="text-emerald-600 dark:text-emerald-400 font-bold text-[11px]">✓ Listo</span>
                                                        @elseif ($isLocked || ! $state['available'])
                                                            <span class="text-slate-400 text-[11px]">{{ $isLocked ? 'Bloqueado' : 'Opcional' }}</span>
                                                        @else
                                                            <span class="text-amber-500 font-bold text-[11px]">Pendiente</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        {{-- Botón de Acción Táctil 3D Duolingo --}}
                                        <div class="mt-2 pt-3 border-t" style="border-color: var(--color-border);">
                                            @if ($isLocked)
                                                <button type="button" disabled class="btn-duo btn-duo-outline w-full text-xs py-2 opacity-50">
                                                    🔒 Bloqueado
                                                </button>
                                            @elseif ($lesson)
                                                @php
                                                    $activeSkill = collect(['reading', 'listening', 'speaking'])->first(fn ($s) => ! in_array($s, $node['mastered_skills'], true)) ?? 'reading';
                                                @endphp
                                                <a href="{{ route('lessons.learn', ['lesson' => $lesson, 'tab' => $activeSkill]) }}"
                                                   class="btn-duo {{ $node['completed'] ? 'btn-duo-outline' : 'btn-duo-green' }} w-full text-xs py-2.5">
                                                    <span>{{ $node['completed'] ? 'Repasar Lección' : 'Comenzar Lección' }}</span>
                                                    <span>→</span>
                                                </a>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            {{-- Hito Final de la Unidad (Cofre / Certificado) --}}
                            <div class="mt-6 p-4 rounded-2xl border flex items-center justify-between gap-4"
                                 style="background: color-mix(in srgb, var(--color-primary) 6%, var(--color-card)); border-color: color-mix(in srgb, var(--color-primary) 25%, transparent);">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl flex items-center justify-center bg-amber-500/10 text-amber-500">
                                        <x-icon name="trophy" class="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h4 class="font-display font-bold text-xs sm:text-sm" style="color: var(--color-text);">
                                            Hito de Certificación: Nivel {{ $level['cefr'] }}
                                        </h4>
                                        <p class="text-[11px]" style="color: var(--color-text-secondary);">
                                            {{ $levelPct === 100 ? '¡Unidad dominada con éxito!' : 'Completa todos los subniveles para desbloquear la maestría de este nivel.' }}
                                        </p>
                                    </div>
                                </div>
                                <span class="text-xs font-mono font-extrabold px-3 py-1.5 rounded-xl border {{ $levelPct === 100 ? 'bg-emerald-500 text-white' : 'bg-card text-slate-400' }}"
                                      style="border-color: var(--color-border);">
                                    {{ $levelPct === 100 ? '✓ Nivel Superado' : $levelPct . '%' }}
                                </span>
                            </div>
                        @endif

                    </section>
                @endforeach
            </div>

        </div>
    </div>
</x-app-layout>
