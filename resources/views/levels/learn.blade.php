<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white leading-tight">
            📖 {{ $lesson->lesson_cefr_level }} · {{ $lesson->lesson_prompt_payload['topic'] ?? 'Lección' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Back link --}}
            <a href="{{ route('levels.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold mb-6 hover:underline"
               style="color: var(--color-primary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Volver al mapa
            </a>

            {{-- Content card --}}
            <div class="rounded-2xl p-8 shadow-sm" style="background-color: var(--color-card);">
                <div class="flex items-center gap-4 mb-6">
                    <span class="text-4xl">
                        @if ($lesson->lesson_skill_type === 'speaking') 🗣️
                        @else 📝
                        @endif
                    </span>
                    <div>
                        <h3 class="font-display font-bold text-xl" style="color: var(--color-primary);">
                            {{ $lesson->lesson_prompt_payload['topic'] ?? 'Lección' }}
                        </h3>
                        <p class="text-sm" style="color: var(--color-text-secondary);">
                            Nivel {{ $lesson->lesson_cefr_level }} · Subnivel {{ $lesson->lesson_sub_level }}
                        </p>
                        <span class="inline-block mt-1 text-xs font-semibold px-2 py-0.5 rounded-full"
                              @if ($lesson->lesson_skill_type === 'speaking')
                                  style="background: #fff3cd; color: #856404;"
                              @else
                                  style="background: #e2e3f1; color: #3a3b7b;"
                              @endif
                        >
                            {{ ucfirst($lesson->lesson_skill_type) }}
                        </span>
                    </div>
                </div>

                {{-- Lesson placeholder content --}}
                <div class="space-y-4 mb-8">
                    <div class="rounded-xl p-5" style="background: var(--color-bg); border: 1px solid var(--color-border);">
                        <p class="text-sm" style="color: var(--color-text);">
                            {{ $lesson->lesson_prompt_payload['prompt'] ?? 'El contenido está siendo preparado por el búho tutor.' }}
                        </p>
                    </div>

                    <div class="rounded-xl p-5" style="background: color-mix(in srgb, var(--color-accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--color-accent) 30%, transparent);">
                        <p class="text-sm font-semibold" style="color: var(--color-primary);">
                            💡 Practica diciendo las frases en voz alta para mejorar tu pronunciación.
                        </p>
                    </div>
                </div>

                {{-- Mark as complete button --}}
                <form method="POST" action="{{ route('lessons.complete', $lesson) }}">
                    @csrf
                    <button type="submit" class="w-full rounded-xl py-3 font-bold text-white text-sm shadow-md hover:shadow-lg transition-all duration-200 hover:scale-[1.02]"
                            style="background: linear-gradient(135deg, var(--color-accent), var(--color-primary));">
                        ✅ Marcar como completado
                    </button>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
