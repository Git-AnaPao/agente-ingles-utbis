import './bootstrap';

import Alpine from 'alpinejs';

const storage = {
    get(key) {
        try {
            return window.localStorage.getItem(key);
        } catch (error) {
            return null;
        }
    },
    set(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch (error) {
            // The application remains usable when storage is unavailable.
        }
    },
    remove(key) {
        try {
            window.localStorage.removeItem(key);
        } catch (error) {
            // Nothing else is required if storage is unavailable.
        }
    },
};

/**
 * Motor de Voz Femenina de IA con Web Speech API
 * - Limpieza profunda de formato Markdown para dicción humana fluida
 * - Prioridad estricta para voces Neural / Natural / Google sobre sintetizadores Desktop
 * - Detección inteligente de idioma (Español / Inglés)
 * - Persistencia de voz preferida y eventos de animación de audio
 */
const AIVoice = {
    cachedVoices: [],
    _speaking: false,

    getVoices() {
        if (!window.speechSynthesis) return [];
        const voices = window.speechSynthesis.getVoices();
        if (voices && voices.length > 0) {
            this.cachedVoices = voices;
        }
        return this.cachedVoices;
    },

    /**
     * Limpia markdown, emojis, asteriscos y sintaxis para que el TTS suene como un humano
     */
    cleanTextForSpeech(text) {
        if (!text || typeof text !== 'string') return '';

        return text
            // Remover bloques de código
            .replace(/```[\s\S]*?```/g, '')
            // Remover código inline
            .replace(/`([^`]+)`/g, '$1')
            // Remover enlaces [texto](url) -> texto
            .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1')
            // Remover encabezados markdown # ## ###
            .replace(/^#{1,6}\s+/gm, '')
            // Remover negritas y cursivas **texto** o *texto*
            .replace(/(\*\*|__)(.*?)\1/g, '$2')
            .replace(/(\*|_)(.*?)\1/g, '$2')
            // Remover listas con viñetas o numeradas al inicio de línea
            .replace(/^\s*[-*+]\s+/gm, '')
            .replace(/^\s*\d+\.\s+/gm, '')
            // Remover emojis y símbolos especiales que causan pausas o artefactos
            .replace(/[\u{1F600}-\u{1F64F}\u{1F300}-\u{1F5FF}\u{1F680}-\u{1F6FF}\u{1F1E0}-\u{1F1FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}\u{1F900}-\u{1F9FF}\u{1FA70}-\u{1FAFF}]/gu, '')
            // Limpiar saltos de línea repetidos y espacios
            .replace(/\n+/g, '. ')
            .replace(/\s+/g, ' ')
            .trim();
    },

    /**
     * Detecta si el texto está en español o en inglés de forma precisa
     */
    detectLanguage(text) {
        if (!text) return 'en-US';
        const clean = text.toLowerCase();

        // Indicadores inequívocos de español
        if (/[áéíóúüñ¿¡]/.test(clean)) {
            return 'es-MX';
        }

        const spanishWords = /\b(el|la|los|las|un|una|unos|unas|de|del|en|para|por|con|es|son|esta|estan|que|como|hola|buenos|dias|tardes|noches|gracias|puedes|ejercicio|leccion|nivel|aprender|ingles|tutor|bienvenido|practica|respuesta|correcta|intenta|pregunta|muy|bien|tambien|aqui|donde|cuando|porque|pero|mas|tu|te|ti|mi|me|nos|ellos|ella|usted|ustedes|significa|oracion|palabra)\b/gi;
        const englishWords = /\b(the|is|are|was|were|and|to|in|of|for|with|you|your|have|has|this|that|lesson|read|listen|speak|practice|welcome|hello|good|morning|great|question|answer|right|wrong|try|again|please|because|about|from|they|them|we|our|sentence|word|mean)\b/gi;

        const esMatches = (clean.match(spanishWords) || []).length;
        const enMatches = (clean.match(englishWords) || []).length;

        if (esMatches > enMatches) return 'es-MX';
        if (enMatches > esMatches) return 'en-US';
        return esMatches > 0 ? 'es-MX' : 'en-US';
    },

    /**
     * Obtiene y clasifica las mejores voces naturales disponibles
     */
    findFemaleVoice(lang = 'en-US') {
        const voices = this.getVoices();
        if (!voices || voices.length === 0) return null;

        // Comprobar preferencia guardada
        const savedVoiceURI = window.appStorage?.get('agente-ingles:selected-voice');
        if (savedVoiceURI) {
            const saved = voices.find((v) => v.voiceURI === savedVoiceURI || v.name === savedVoiceURI);
            if (saved) return saved;
        }

        const isSpanish = lang.startsWith('es');
        const langVoices = voices.filter((v) => isSpanish ? v.lang.startsWith('es') : v.lang.startsWith('en'));
        const pool = langVoices.length > 0 ? langVoices : voices;

        // Clasificar voces: Priorizar Natural/Online/Google y penalizar Desktop legacy
        const scoreVoice = (v) => {
            const name = v.name.toLowerCase();
            let score = 0;

            // Puntuación por calidad moderna
            if (name.includes('online (natural)') || name.includes('natural')) score += 100;
            if (name.includes('neural')) score += 90;
            if (name.includes('google')) score += 80;
            if (name.includes('enhanced') || name.includes('premium')) score += 70;

            // Puntuación por género femenino
            if (name.includes('female') || name.includes('mujer') || name.includes('woman')) score += 30;
            if (name.includes('dalia') || name.includes('sabina') || name.includes('paulina') || name.includes('jenny') || name.includes('aria') || name.includes('samantha') || name.includes('victoria') || name.includes('monica') || name.includes('sofia') || name.includes('lucia') || name.includes('elena') || name.includes('laura')) score += 40;

            // Penalizar sintetizadores antiguos de escritorio que suenan robóticos
            if (name.includes('desktop') || name.includes('sapi') || name.includes('mobile')) score -= 40;
            if (name.includes('david') || name.includes('mark') || name.includes('george') || name.includes('jorge') || name.includes('raul') || name.includes('male') || name.includes('hombre')) score -= 50;

            return score;
        };

        const sorted = [...pool].sort((a, b) => scoreVoice(b) - scoreVoice(a));
        return sorted[0] || null;
    },

    getAvailableFemaleVoices() {
        const voices = this.getVoices();
        return voices.filter((v) => {
            const name = v.name.toLowerCase();
            return (v.lang.startsWith('es') || v.lang.startsWith('en')) &&
                !name.includes('david') &&
                !name.includes('male') &&
                !name.includes('george') &&
                !name.includes('raul') &&
                !name.includes('mark');
        });
    },

    speak(text, options = {}) {
        if (!window.speechSynthesis) {
            if (typeof options.onError === 'function') options.onError('Speech synthesis no disponible');
            return null;
        }

        window.speechSynthesis.cancel();

        const cleanText = this.cleanTextForSpeech(text);
        if (!cleanText) return null;

        const detectedLang = options.lang || this.detectLanguage(cleanText);
        const utterance = new SpeechSynthesisUtterance(cleanText);
        utterance.lang = detectedLang;

        // Tono y velocidad naturales (1.0 = tono original no distorsionado)
        utterance.rate = options.rate || 0.95;
        utterance.pitch = options.pitch || 1.0;

        const voice = options.voice || this.findFemaleVoice(detectedLang);
        if (voice) {
            utterance.voice = voice;
        }

        this._speaking = true;
        window.dispatchEvent(new CustomEvent('aivoice:start', { detail: { text: cleanText, lang: detectedLang, voice: voice?.name } }));

        utterance.onstart = () => {
            this._speaking = true;
            window.dispatchEvent(new CustomEvent('aivoice:start', { detail: { text: cleanText, lang: detectedLang, voice: voice?.name } }));
            if (typeof options.onStart === 'function') options.onStart();
        };

        const handleEnd = () => {
            this._speaking = false;
            window.dispatchEvent(new CustomEvent('aivoice:end'));
            if (typeof options.onEnd === 'function') options.onEnd();
        };

        const handleError = (e) => {
            this._speaking = false;
            window.dispatchEvent(new CustomEvent('aivoice:error', { detail: e }));
            if (typeof options.onError === 'function') options.onError(e);
        };

        utterance.onend = handleEnd;
        utterance.onerror = handleError;

        window.speechSynthesis.speak(utterance);
        return utterance;
    },

    stop() {
        if (window.speechSynthesis) {
            window.speechSynthesis.cancel();
        }
        this._speaking = false;
        window.dispatchEvent(new CustomEvent('aivoice:end'));
    },

    isSpeaking() {
        return this._speaking || (window.speechSynthesis ? window.speechSynthesis.speaking : false);
    }
};

if (typeof window !== 'undefined' && window.speechSynthesis) {
    window.speechSynthesis.onvoiceschanged = () => {
        AIVoice.getVoices();
    };
    AIVoice.getVoices();
}

window.AIVoice = AIVoice;
window.appStorage = storage;
window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.data('theme', () => {
        const savedTheme = storage.get('theme');
        const followsSystem = savedTheme !== 'light' && savedTheme !== 'dark';

        return {
            theme: followsSystem
                ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : savedTheme,
            grayscale: storage.get('grayscale') === 'true',
            followsSystem,
            init() {
                this.applyTheme(this.theme);
                document.documentElement.setAttribute('data-grayscale', this.grayscale ? 'true' : 'false');

                const colorPreference = window.matchMedia('(prefers-color-scheme: dark)');
                const updateFromSystem = (event) => {
                    if (this.followsSystem) {
                        this.theme = event.matches ? 'dark' : 'light';
                    }
                };

                if (typeof colorPreference.addEventListener === 'function') {
                    colorPreference.addEventListener('change', updateFromSystem);
                }

                this.$watch('theme', (value) => this.applyTheme(value));
                this.$watch('grayscale', (value) => {
                    storage.set('grayscale', value ? 'true' : 'false');
                    document.documentElement.setAttribute('data-grayscale', value ? 'true' : 'false');
                });
            },
            applyTheme(value) {
                document.documentElement.setAttribute('data-theme', value);
                document.documentElement.style.colorScheme = value;
            },
            toggleTheme() {
                this.followsSystem = false;
                this.theme = this.theme === 'dark' ? 'light' : 'dark';
                storage.set('theme', this.theme);
            },
            toggleGrayscale() {
                this.grayscale = !this.grayscale;
            },
        };
    });
});

Alpine.start();

const hasClientSubmitHandler = (form) => form.dataset.submitState === 'off'
    || form.getAttributeNames().some((name) => name === '@submit'
        || name.startsWith('@submit.')
        || name === 'x-on:submit'
        || name.startsWith('x-on:submit.'));

const restoreSubmitState = (form) => {
    form.removeAttribute('aria-busy');
    delete form.dataset.submitProcessing;

    const status = form.querySelector('[data-submit-status]');
    if (status) {
        status.textContent = '';
    }

    form.querySelectorAll('[data-submit-loading="true"]').forEach((button) => {
        button.disabled = button.dataset.originalDisabled === 'true';

        if (button.dataset.hadAriaDisabled === 'true') {
            button.setAttribute('aria-disabled', button.dataset.originalAriaDisabled);
        } else {
            button.removeAttribute('aria-disabled');
        }

        if (button instanceof HTMLButtonElement && Object.hasOwn(button.dataset, 'originalContent')) {
            button.innerHTML = button.dataset.originalContent;
        } else if (button instanceof HTMLInputElement && Object.hasOwn(button.dataset, 'originalValue')) {
            button.value = button.dataset.originalValue;
        }

        delete button.dataset.submitLoading;
        delete button.dataset.originalDisabled;
        delete button.dataset.hadAriaDisabled;
        delete button.dataset.originalAriaDisabled;
        delete button.dataset.originalContent;
        delete button.dataset.originalValue;
    });
};

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || event.defaultPrevented || hasClientSubmitHandler(form)) {
        return;
    }

    if (form.dataset.submitProcessing === 'true') {
        event.preventDefault();
        return;
    }

    if (form.dataset.confirmMessage && !window.confirm(form.dataset.confirmMessage)) {
        event.preventDefault();
        return;
    }

    if (Object.hasOwn(form.dataset, 'clearChatHistory')) {
        const storageKey = document.body.dataset.chatStorageKey;
        if (storageKey) {
            storage.remove(storageKey);
        }
    }

    form.dataset.submitProcessing = 'true';
    form.setAttribute('aria-busy', 'true');

    const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
    submitButtons.forEach((button) => {
        button.dataset.submitLoading = 'true';
        button.dataset.originalDisabled = button.disabled ? 'true' : 'false';
        button.dataset.hadAriaDisabled = button.hasAttribute('aria-disabled') ? 'true' : 'false';
        button.dataset.originalAriaDisabled = button.getAttribute('aria-disabled') ?? '';
        button.disabled = true;
        button.setAttribute('aria-disabled', 'true');

        if (button instanceof HTMLButtonElement) {
            button.dataset.originalContent = button.innerHTML;
            button.textContent = button.dataset.loadingText || 'Procesando...';
        } else {
            button.dataset.originalValue = button.value;
            button.value = button.dataset.loadingText || 'Procesando...';
        }
    });

    let status = form.querySelector('[data-submit-status]');
    if (!status) {
        status = document.createElement('span');
        status.className = 'sr-only';
        status.dataset.submitStatus = '';
        status.setAttribute('role', 'status');
        status.setAttribute('aria-live', 'polite');
        form.append(status);
    }
    status.textContent = 'Procesando solicitud.';
});

window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[aria-busy="true"]').forEach((form) => {
        restoreSubmitState(form);
    });
});
