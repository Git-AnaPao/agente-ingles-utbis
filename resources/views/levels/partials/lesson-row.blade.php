{{-- Fila compacta de una lección real dentro del nivel. --}}
@php
    $lessonLocked = $levelLocked || ! ($row['unlocked'] ?? true);
    $isCurrent = ! $row['completed'] && ! $lessonLocked;
    $listeningLesson = $row['listeningLesson'];
@endphp
<article class="solid-card p-3.5 sm:p-4 border flex items-center gap-3 sm:gap-4 transition-all duration-200 {{ $row['completed'] ? 'border-emerald-500/30 bg-emerald-500/5' : '' }} {{ $isCurrent ? 'ring-2 ring-emerald-500/40' : '' }} {{ $lessonLocked ? 'opacity-60' : 'hover:border-emerald-500/40' }}"
         style="border-color: var(--color-card-border);">
    <span class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center font-mono font-bold text-xs sm:text-sm border-2"
          style="background: var(--color-bg); border-color: {{ $row['completed'] ? '#10B981' : 'var(--color-border)' }}; color: {{ $row['completed'] ? '#10B981' : 'var(--color-text-secondary)' }};">
        @if ($row['completed'])
            <x-icon name="check" class="w-4 h-4" />
        @elseif ($lessonLocked)
            <x-icon name="lock" class="w-3.5 h-3.5" />
        @else
            {{ $row['number'] }}
        @endif
    </span>

    <div class="min-w-0 flex-1">
        <h3 class="font-display font-bold text-xs sm:text-sm truncate" style="color: var(--color-text);" title="{{ $listeningLesson->title }}">
            Lección {{ $row['number'] }} · {{ $listeningLesson->title }}
        </h3>
        <p class="text-[10px] sm:text-[11px] font-mono mt-0.5" style="color: var(--color-text-secondary);">
            Progreso: {{ $row['steps_done'] }}/{{ $row['steps_total'] }} pasos
            @if ($isCurrent)
                <span class="ml-1 font-bold text-amber-500">· En curso</span>
            @elseif ($lessonLocked)
                <span class="ml-1 font-bold text-slate-400">· Bloqueada</span>
            @endif
        </p>
    </div>

    <div class="shrink-0">
        @if ($lessonLocked)
            <button type="button" disabled class="btn-duo btn-duo-outline text-xs py-1.5 px-3 opacity-50 inline-flex items-center gap-1.5">
                <x-icon name="lock" class="w-3.5 h-3.5" />
                <span class="hidden sm:inline">Bloqueada</span>
            </button>
        @else
            <a href="{{ route('lessons.learn', $listeningLesson) }}"
               class="btn-duo {{ $row['completed'] ? 'btn-duo-outline' : 'btn-duo-green' }} text-xs py-1.5 px-3.5">
                {{ $row['completed'] ? 'Repasar' : 'Comenzar' }} →
            </a>
        @endif
    </div>
</article>
