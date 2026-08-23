<x-app-layout title="Tutor IA Copilot">
    @php
        $storageKey = 'agente-ingles:chat:' . auth()->id();
        $greeting = $cefrLevel
            ? "¡Hola! Soy Búho, tu tutora inteligente de inglés en UTBIS. He adaptado nuestra sesión a tu nivel CEFR {$cefrLevel}. ¿En qué deseas enfocarte hoy? (Conversación libre, práctica de una situación real, gramática o dudas de vocabulario)."
            : '¡Hola! Soy Búho, tu tutora inteligente de inglés en UTBIS. Puedo ayudarte con práctica conversacional, explicaciones gramaticales, vocabulario y corrección de frases en tiempo real. ¿Qué te gustaría practicar hoy?';

        $scenarios = [
            [
                'icon' => 'briefcase',
                'title' => 'Job Interview',
                'prompt' => 'Let\'s practice a short job interview for an IT / engineering role. Ask me one question at a time and correct my mistakes.'
            ],
            [
                'icon' => 'coffee',
                'title' => 'Coffee Shop & Food',
                'prompt' => 'Let\'s do a roleplay where you are a barista in London and I am ordering breakfast. Start by greeting me.'
            ],
            [
                'icon' => 'lightbulb',
                'title' => 'Past Simple vs Present Perfect',
                'prompt' => 'Can you explain the difference between Past Simple and Present Perfect with 3 practical examples and a quick quiz?'
            ],
            [
                'icon' => 'conversation',
                'title' => 'Daily Routine & Fluency',
                'prompt' => 'Let\'s have a casual conversation about daily routines and hobbies. Ask me a question to get started.'
            ],
        ];
    @endphp

    <div class="py-6 sm:py-8" x-data="chat()">
        <div class="max-w-4xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">
            
            {{-- Header del Copiloto (EF English + Duolingo) --}}
            <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 animate-fade-up">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl shrink-0 shadow-glow-ai"
                         style="background: linear-gradient(135deg, #6366F1, #8B5CF6); color: white;">
                        <x-icon name="bot" class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="font-display font-black text-xl sm:text-2xl leading-tight" style="color: var(--color-text);">
                                Tutor IA Gemini
                            </h1>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Voz Femenina Activa</span>
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm mt-0.5" style="color: var(--color-text-secondary);">
                            Copiloto pedagógico adaptativo{{ $cefrLevel ? " · Nivel CEFR {$cefrLevel}" : '' }} · UTBIS AI Campus
                        </p>
                    </div>
                </div>

                {{-- Controles de Voz & Navegación --}}
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Selector de Voz --}}
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button"
                                @click="open = !open; loadVoices()"
                                class="btn-duo btn-duo-outline text-xs py-2 px-2.5 inline-flex items-center gap-1.5"
                                title="Seleccionar voz de IA">
                            <x-icon name="settings" class="w-3.5 h-3.5" />
                            <span class="hidden sm:inline">Voz</span>
                        </button>
                        
                        <div x-show="open"
                             x-cloak
                             class="absolute right-0 mt-2 w-72 p-3 rounded-2xl border shadow-xl z-50 animate-fade-up text-xs space-y-2.5"
                             style="background: var(--color-card); border-color: var(--color-border); color: var(--color-text);">
                            <div class="flex items-center justify-between font-bold border-b pb-1.5" style="border-color: var(--color-border);">
                                <span>Ajustes de Voz IA</span>
                                <button type="button" @click="testVoice()" class="text-[11px] text-emerald-500 font-extrabold hover:underline">
                                    ▶ Probar voz
                                </button>
                            </div>
                            <div>
                                <label for="voice-select" class="text-[11px] block font-bold text-slate-400 mb-1">Motor de voz:</label>
                                <select id="voice-select"
                                        x-model="selectedVoice"
                                        @change="changeVoice($event.target.value)"
                                        class="w-full rounded-xl border p-2 text-xs"
                                        style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text);">
                                    <option value="">✨ Auto-Natural (Recomendado)</option>
                                    <template x-for="v in availableVoices" :key="v.voiceURI || v.name">
                                        <option :value="v.voiceURI || v.name" x-text="v.name + ' (' + v.lang + ')'"></option>
                                    </template>
                                </select>
                            </div>
                            <p class="text-[10px] text-slate-400 leading-tight">
                                Las voces marcadas como "Natural" o "Google" ofrecen pronunciación humana de alta fidelidad.
                            </p>
                        </div>
                    </div>

                    {{-- Toggle Auto Voz --}}
                    <button type="button"
                            @click="toggleAutoVoice()"
                            class="btn-duo text-xs py-2 px-3 inline-flex items-center gap-1.5 transition-all"
                            :class="autoVoice ? 'btn-duo-green' : 'btn-duo-outline'"
                            :title="autoVoice ? 'Voz automática activada (reproduce las respuestas de la IA)' : 'Activar voz automática'">
                        <x-icon name="speaker" class="w-4 h-4" />
                        <span x-text="autoVoice ? 'Auto-Voz: ON' : 'Auto-Voz: OFF'"></span>
                    </button>

                    <a href="{{ route('dashboard') }}" class="btn-duo btn-duo-outline text-xs py-2 px-3.5 hidden sm:inline-flex">
                        ← Volver
                    </a>
                </div>
            </header>

            {{-- Chat Shell Card (Duolingo Style Dialogues + EF Situational Prompts) --}}
            <section class="ef-unit-card p-0 overflow-hidden flex flex-col border shadow-xl animate-fade-up"
                     style="height: calc(100vh - 230px); min-height: 520px; max-height: 800px; border-color: var(--color-card-border);"
                     aria-labelledby="chat-panel-title">
                
                {{-- Barra Superior del Chat --}}
                <div class="px-5 py-3.5 border-b flex items-center justify-between gap-3"
                     style="border-color: var(--color-border); background: color-mix(in srgb, var(--color-indigo) 6%, var(--color-card));">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center p-1.5 border-2 relative transition-transform duration-200"
                             :class="isCurrentlySpeaking ? 'scale-105 ring-2 ring-emerald-400/60' : ''"
                             style="background: color-mix(in srgb, var(--color-primary) 15%, var(--color-card)); border-color: var(--color-primary);">
                            <img src="{{ asset('img/buho.png') }}" alt="Búho tutora" class="w-full h-full object-contain"
                                 :class="isCurrentlySpeaking ? 'animate-bounce' : ''">
                            <span x-show="isCurrentlySpeaking" class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"></span>
                            </span>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <h2 id="chat-panel-title" class="text-sm font-display font-bold" style="color: var(--color-text);">Búho UTBIS</h2>
                                <template x-if="isCurrentlySpeaking">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 animate-pulse">
                                        <img src="{{ asset('img/soundwave.svg') }}" class="w-3.5 h-3" alt="Ondas de voz">
                                        <span>Hablando...</span>
                                    </span>
                                </template>
                                <template x-if="!isCurrentlySpeaking">
                                    <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-indigo-500/10 text-indigo-500">Natural Voice</span>
                                </template>
                            </div>
                            <p class="text-[11px] font-mono truncate" style="color: var(--color-text-secondary);">
                                {{ $cefrLevel ? "Adaptado a Nivel CEFR {$cefrLevel}" : 'Nivel general dinámico' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        {{-- Botón Detener Voz si está hablando --}}
                        <button type="button"
                                x-show="isCurrentlySpeaking"
                                @click="stopVoice()"
                                class="btn-duo btn-duo-orange text-xs py-1.5 px-3 font-bold inline-flex items-center gap-1.5 animate-pulse">
                            <x-icon name="volume-x" class="w-3.5 h-3.5" />
                            <span>Silenciar</span>
                        </button>

                        <button type="button"
                                @click="newChat()"
                                :disabled="loading"
                                class="btn-duo btn-duo-outline text-xs py-1.5 px-3 font-bold inline-flex items-center gap-1.5"
                                :class="loading ? 'opacity-50 cursor-not-allowed' : ''">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            <span>Nueva sesión</span>
                        </button>
                    </div>
                </div>

                {{-- Historial de Mensajes --}}
                <div x-ref="messages"
                     class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4"
                     style="background: var(--color-bg);"
                     role="log"
                     aria-live="polite"
                     aria-relevant="additions"
                     aria-label="Historial de la conversación">
                    
                    <template x-for="message in messages" :key="message.id">
                        <article class="flex items-end gap-2.5" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                            
                            {{-- Avatar del Tutor para mensajes entrantes --}}
                            <template x-if="message.role !== 'user'">
                                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 mb-1 p-1 border shadow-xs transition-transform duration-150"
                                     :class="speakingMessageId === message.id ? 'scale-110 ring-2 ring-emerald-400' : ''"
                                     style="background: color-mix(in srgb, var(--color-primary) 12%, var(--color-card)); border-color: var(--color-primary);">
                                    <img src="{{ asset('img/buho.png') }}" alt="" class="w-full h-full object-contain"
                                         :class="speakingMessageId === message.id ? 'animate-pulse' : ''">
                                </div>
                            </template>

                            <div class="max-w-[85%] sm:max-w-[78%] rounded-2xl px-4 py-3 text-sm leading-relaxed shadow-sm transition-all relative group"
                                 :class="speakingMessageId === message.id ? 'ring-2 ring-emerald-500/50 shadow-glow' : ''"
                                 :style="message.role === 'user'
                                    ? 'background: linear-gradient(135deg, #10B981, #059669); color: white; border-bottom-right-radius: 4px; box-shadow: 0 4px 14px -2px rgba(16,185,129,0.3);'
                                    : 'background: var(--color-card); border: 2px solid var(--color-border); color: var(--color-text); border-bottom-left-radius: 4px;'">
                                <span class="sr-only" x-text="message.role === 'user' ? 'Tú dijiste:' : 'El tutor respondió:'"></span>
                                <p class="whitespace-pre-wrap selection:bg-indigo-500 selection:text-white" x-text="message.content"></p>

                                {{-- Botón de Reproducción de Voz Femenina para la IA --}}
                                <template x-if="message.role !== 'user'">
                                    <div class="mt-2.5 pt-2 border-t flex items-center justify-between gap-2"
                                         style="border-color: var(--color-border);">
                                        <button type="button"
                                                @click="speakMessage(message)"
                                                class="text-xs font-bold inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl transition-all hover:bg-indigo-500/10"
                                                :class="speakingMessageId === message.id ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'text-indigo-600 dark:text-indigo-400'">
                                            <template x-if="speakingMessageId === message.id">
                                                <span class="inline-flex items-center gap-1.5">
                                                    <img src="{{ asset('img/soundwave.svg') }}" class="w-4 h-3.5 inline-block" alt="Reproduciendo">
                                                    <span class="text-[11px]">Hablando...</span>
                                                </span>
                                            </template>
                                            <template x-if="speakingMessageId !== message.id">
                                                <span class="inline-flex items-center gap-1">
                                                    <x-icon name="speaker" class="w-3.5 h-3.5" />
                                                    <span class="text-[11px]">Escuchar voz</span>
                                                </span>
                                            </template>
                                        </button>
                                        <span class="text-[10px] font-mono text-slate-400">Voz Femenina</span>
                                    </div>
                                </template>
                            </div>
                        </article>
                    </template>

                    {{-- Indicador de Pensando / Cargando --}}
                    <div x-show="loading" x-cloak class="flex items-end gap-2.5 justify-start">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 mb-1 p-1 border"
                             style="background: color-mix(in srgb, var(--color-primary) 12%, var(--color-card)); border-color: var(--color-primary);">
                            <img src="{{ asset('img/buho.png') }}" alt="" class="w-full h-full object-contain">
                        </div>
                        <div class="max-w-[85%] rounded-2xl px-4 py-3 text-sm border-2 shadow-sm"
                             style="background: var(--color-card); border-color: var(--color-border); color: var(--color-text-secondary);">
                            <div class="flex items-center gap-2.5" role="status">
                                <span class="flex gap-1">
                                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce" style="animation-delay: 0s"></span>
                                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce" style="animation-delay: 0.15s"></span>
                                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce" style="animation-delay: 0.3s"></span>
                                </span>
                                <span class="text-xs font-semibold">Búho está pensando su respuesta...</span>
                            </div>
                            <button type="button"
                                    @click="cancelGeneration()"
                                    class="mt-2 text-xs font-bold text-red-500 hover:underline inline-block">
                                Cancelar respuesta
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Prompts Sugeridos / Escenarios EF Situational English con Iconos Vectoriales --}}
                <div class="px-4 py-2.5 border-t flex items-center gap-2 overflow-x-auto no-scrollbar text-xs"
                     style="border-color: var(--color-border); background: var(--color-card);"
                     x-show="messages.length <= 2 && !loading">
                    <span class="font-bold text-slate-400 shrink-0 text-[10px] uppercase">Escenarios:</span>
                    @foreach ($scenarios as $item)
                        <button type="button" @click="input = @js($item['prompt']); sendMessage()"
                                class="shrink-0 px-3 py-1.5 rounded-xl border font-semibold transition-all hover:border-emerald-500 text-xs inline-flex items-center gap-1.5"
                                style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text);">
                            <x-icon :name="$item['icon']" class="w-3.5 h-3.5 text-indigo-500" />
                            <span>{{ $item['title'] }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- Entrada de Texto y Acciones Duolingo 3D --}}
                <div class="p-3 sm:p-4 border-t" style="border-color: var(--color-border); background: var(--color-card);">
                    <div x-show="errorMessage"
                         x-cloak
                         class="mb-3 rounded-2xl p-3 text-xs font-semibold border"
                         style="background: var(--color-error-surface); border-color: var(--color-error-border); color: var(--color-error-text);"
                         role="alert">
                        <p x-text="errorMessage"></p>
                        <button type="button"
                                x-show="retryAvailable"
                                @click="retryLastMessage()"
                                :disabled="loading"
                                class="mt-1 font-bold underline disabled:opacity-50">
                            Reintentar enviar mensaje
                        </button>
                    </div>

                    <p x-show="statusMessage"
                       x-cloak
                       class="sr-only"
                       role="status"
                       aria-live="polite"
                       x-text="statusMessage"></p>

                    <form @submit.prevent="sendMessage()" data-submit-state="off">
                        <label for="chat-input" class="sr-only">Mensaje para el tutor de inglés</label>
                        <div class="flex flex-col gap-2.5 sm:flex-row sm:items-end">
                            <textarea id="chat-input"
                                      x-ref="input"
                                      x-model="input"
                                      rows="2"
                                      maxlength="2000"
                                      placeholder="Escribe tu duda o responde en inglés..."
                                      @keydown.enter.exact.prevent="if (!$event.isComposing) sendMessage()"
                                      :disabled="loading"
                                      :aria-busy="loading.toString()"
                                      aria-describedby="chat-input-help chat-character-count"
                                      class="min-h-[52px] flex-1 resize-y rounded-2xl border-2 px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
                                      style="border-color: var(--color-border); background: var(--color-bg); color: var(--color-text);"></textarea>
                            <button type="submit"
                                    class="btn-duo btn-duo-green text-sm px-6 py-3 disabled:opacity-50 disabled:cursor-not-allowed shadow-md shrink-0 inline-flex items-center gap-2"
                                    :disabled="loading || !input.trim()"
                                    :aria-disabled="(loading || !input.trim()).toString()">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                <span x-text="loading ? 'Enviando...' : 'Enviar'"></span>
                            </button>
                        </div>
                        <div class="mt-2 flex items-start justify-between gap-3 text-[11px] font-mono" style="color: var(--color-text-secondary);">
                            <p id="chat-input-help">Enter envía · Shift + Enter salto de línea</p>
                            <p id="chat-character-count" class="shrink-0"><span x-text="input.length"></span> / 2000</p>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            function chat() {
                const storageKey = @js($storageKey);
                const initialGreeting = @js($greeting);
                let requestController = null;
                let requestSequence = 0;
                let activeRequestId = 0;

                return {
                    input: '',
                    messages: [],
                    loading: false,
                    errorMessage: '',
                    statusMessage: '',
                    retryAvailable: false,
                    retryContent: '',
                    sequence: 0,
                    autoVoice: true,
                    isCurrentlySpeaking: false,
                    speakingMessageId: null,

                    availableVoices: [],
                    selectedVoice: '',

                    init() {
                        const savedVoice = window.appStorage?.get('agente-ingles:auto-voice');
                        if (savedVoice !== null) {
                            this.autoVoice = savedVoice === 'true';
                        }

                        this.selectedVoice = window.appStorage?.get('agente-ingles:selected-voice') || '';
                        this.loadVoices();

                        const saved = window.appStorage?.get(storageKey);

                        if (saved) {
                            try {
                                const parsed = JSON.parse(saved);
                                if (Array.isArray(parsed)) {
                                    this.messages = parsed
                                        .filter((message) =>
                                            message
                                            && ['user', 'assistant'].includes(message.role)
                                            && typeof message.content === 'string'
                                            && message.content.trim().length > 0
                                            && message.content.length <= 2000
                                        )
                                        .slice(-12)
                                        .map((message) => ({
                                            id: this.nextId(),
                                            role: message.role,
                                            content: message.content,
                                        }));
                                }
                            } catch (error) {
                                window.appStorage?.remove(storageKey);
                            }
                        }

                        if (this.messages.length === 0) {
                            this.resetConversation();
                        }

                        this.scrollToBottom();
                    },

                    loadVoices() {
                        if (window.AIVoice) {
                            this.availableVoices = window.AIVoice.getAvailableFemaleVoices();
                        }
                    },

                    changeVoice(voiceURI) {
                        this.selectedVoice = voiceURI;
                        if (voiceURI) {
                            window.appStorage?.set('agente-ingles:selected-voice', voiceURI);
                        } else {
                            window.appStorage?.remove('agente-ingles:selected-voice');
                        }
                        this.testVoice();
                    },

                    testVoice() {
                        window.AIVoice?.speak('¡Hola! Soy tu tutora de inglés. Te ayudaré a mejorar tu fluidez día a día.');
                    },

                    toggleAutoVoice() {
                        this.autoVoice = !this.autoVoice;
                        window.appStorage?.set('agente-ingles:auto-voice', this.autoVoice ? 'true' : 'false');
                        if (!this.autoVoice) {
                            this.stopVoice();
                        }
                    },

                    speakMessage(message) {
                        if (this.speakingMessageId === message.id && this.isCurrentlySpeaking) {
                            this.stopVoice();
                            return;
                        }

                        this.stopVoice();
                        this.speakingMessageId = message.id;
                        this.isCurrentlySpeaking = true;

                        window.AIVoice?.speak(message.content, {
                            onStart: () => {
                                this.isCurrentlySpeaking = true;
                            },
                            onEnd: () => {
                                this.isCurrentlySpeaking = false;
                                this.speakingMessageId = null;
                            },
                            onError: () => {
                                this.isCurrentlySpeaking = false;
                                this.speakingMessageId = null;
                            }
                        });
                    },

                    stopVoice() {
                        window.AIVoice?.stop();
                        this.isCurrentlySpeaking = false;
                        this.speakingMessageId = null;
                    },

                    nextId() {
                        this.sequence += 1;
                        return `message-${Date.now()}-${this.sequence}`;
                    },

                    resetConversation() {
                        this.messages = [{
                            id: this.nextId(),
                            role: 'assistant',
                            content: initialGreeting,
                        }];
                        this.persist();
                    },

                    persist() {
                        const history = this.messages.slice(-12).map(({ role, content }) => ({ role, content }));
                        window.appStorage?.set(storageKey, JSON.stringify(history));
                    },

                    newChat() {
                        if (this.loading) {
                            return;
                        }

                        const hasUserMessages = this.messages.some((message) => message.role === 'user');
                        if (hasUserMessages && !window.confirm('¿Iniciar una conversación nueva? Se borrará este historial.')) {
                            return;
                        }

                        this.stopVoice();
                        window.appStorage?.remove(storageKey);
                        this.input = '';
                        this.errorMessage = '';
                        this.retryAvailable = false;
                        this.retryContent = '';
                        this.statusMessage = 'Se inició una conversación nueva.';
                        this.resetConversation();
                        this.scrollToBottom();
                        this.$nextTick(() => this.$refs.input?.focus());
                    },

                    scrollToBottom() {
                        this.$nextTick(() => {
                            if (this.$refs.messages) {
                                this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
                            }
                        });
                    },

                    cancelGeneration() {
                        if (requestController) {
                            requestController.abort();
                            requestController = null;
                        }
                        this.loading = false;
                        this.statusMessage = 'Generación cancelada.';
                    },

                    async sendMessage(overrideContent = null) {
                        const text = (overrideContent ?? this.input).trim();
                        if (!text || this.loading) return;

                        this.stopVoice();
                        this.errorMessage = '';
                        this.retryAvailable = false;
                        this.retryContent = text;

                        if (!overrideContent) {
                            this.input = '';
                        }

                        const userMessageId = this.nextId();
                        this.messages.push({
                            id: userMessageId,
                            role: 'user',
                            content: text,
                        });
                        this.persist();
                        this.scrollToBottom();

                        this.loading = true;
                        this.statusMessage = 'Búho está escribiendo su respuesta...';

                        requestSequence += 1;
                        const currentRequestId = requestSequence;
                        activeRequestId = currentRequestId;

                        if (requestController) {
                            requestController.abort();
                        }
                        requestController = new AbortController();

                        try {
                            const payloadMessages = this.messages.slice(-12).map(({ role, content }) => ({ role, content }));
                            const response = await fetch('{{ route('chat.send') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify({
                                    messages: payloadMessages,
                                }),
                                signal: requestController.signal,
                            });

                            if (activeRequestId !== currentRequestId) return;

                            const data = await response.json();

                            if (!response.ok) {
                                throw new Error(data.error || data.message || 'Error al procesar la respuesta.');
                            }

                            const assistantMsg = {
                                id: this.nextId(),
                                role: 'assistant',
                                content: data.reply,
                            };

                            this.messages.push(assistantMsg);
                            this.persist();
                            this.retryAvailable = false;
                            this.retryContent = '';
                            this.statusMessage = 'Respuesta recibida.';

                            // Reproducción automática de voz femenina si está habilitado
                            if (this.autoVoice) {
                                this.speakMessage(assistantMsg);
                            }
                        } catch (err) {
                            if (err.name === 'AbortError') return;
                            this.errorMessage = err.message || 'No se pudo conectar con el tutor.';
                            this.retryAvailable = true;
                            this.statusMessage = 'Error al enviar el mensaje.';
                        } finally {
                            if (activeRequestId === currentRequestId) {
                                this.loading = false;
                                this.scrollToBottom();
                                this.$nextTick(() => this.$refs.input?.focus());
                            }
                        }
                    },

                    retryLastMessage() {
                        if (!this.retryContent || this.loading) return;
                        this.sendMessage(this.retryContent);
                    }
                };
            }
        </script>
    @endpush
</x-app-layout>
