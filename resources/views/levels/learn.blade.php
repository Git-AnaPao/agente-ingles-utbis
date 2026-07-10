<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white leading-tight tracking-tight">
            📖 {{ $lesson->lesson_cefr_level }} · {{ $lesson->lesson_prompt_payload['topic'] ?? 'Lección' }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Back link --}}
            <a href="{{ route('levels.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold hover:underline group transition-all duration-200"
               style="color: var(--color-primary);">
                <svg class="w-4 h-4 transition-transform duration-200 group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Volver al mapa
            </a>

            {{-- Content card --}}
            <div class="solid-card p-8 animate-fade-up">
                <div class="flex items-center gap-5 mb-8 pb-6 border-b" style="border-color: var(--color-border);">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-3xl shrink-0"
                         style="background: linear-gradient(135deg, var(--color-primary), var(--color-purple));">
                        📝
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-2xl" style="color: var(--color-text);">
                            {{ $lesson->lesson_prompt_payload['topic'] ?? 'Lección' }}
                        </h3>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-sm" style="color: var(--color-text-secondary);">
                                Nivel {{ $lesson->lesson_cefr_level }} · Subnivel {{ $lesson->lesson_sub_level }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Lesson content --}}
                <div class="space-y-4 mb-8">
                    <div class="rounded-xl p-6" style="background: var(--color-bg); border: 1px solid var(--color-border);">
                        <p class="text-base leading-relaxed" style="color: var(--color-text);">
                            {{ $lesson->lesson_prompt_payload['prompt'] ?? 'El contenido está siendo preparado por el búho tutor.' }}
                        </p>
                    </div>

                    <div class="rounded-xl p-5 flex items-start gap-3"
                         style="background: color-mix(in srgb, var(--color-primary) 8%, transparent); border: 1px solid color-mix(in srgb, var(--color-primary) 20%, transparent);">
                        <span class="text-lg shrink-0 mt-0.5">💡</span>
                        <div>
                            <p class="text-sm font-semibold" style="color: var(--color-primary);">
                                Practica diciendo las frases en voz alta para mejorar tu pronunciación.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Complete button --}}
                <form method="POST" action="{{ route('lessons.complete', $lesson) }}">
                    @csrf
                    <button type="submit" class="btn-primary w-full text-base py-3.5">
                        ✅ Marcar como completado
                    </button>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
