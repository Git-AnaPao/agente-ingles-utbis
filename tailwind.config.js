import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['selector', '[data-theme="dark"]'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans:    ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                mono:    ['"JetBrains Mono"', 'Menlo', 'Monaco', 'Courier New', 'monospace'],
            },
            colors: {
                brand: {
                    verde:      '#059669',
                    aprendizaje:'#10B981',
                    mint:       '#34D399',
                    amarillo:   '#F59E0B',
                    naranja:    '#F97316',
                    purple:     '#8B5CF6',
                    indigo:     '#6366F1',
                    cyan:       '#06B6D4',
                },
                lumina: {
                    50:  '#ECFDF5',
                    100: '#D1FAE5',
                    200: '#A7F3D0',
                    300: '#6EE7B7',
                    400: '#34D399',
                    500: '#10B981',
                    600: '#059669',
                    700: '#047857',
                    800: '#065F46',
                    900: '#064E3B',
                },
                ai: {
                    50:  '#EEF2FF',
                    100: '#E0E7FF',
                    200: '#C7D2FE',
                    300: '#A5B4FC',
                    400: '#818CF8',
                    500: '#6366F1',
                    600: '#4F46E5',
                    700: '#4338CA',
                    800: '#3730A3',
                    900: '#312E81',
                },
                surface: {
                    light:   '#FFFFFF',
                    canvas:  '#F8FAFC',
                    subtle:  '#F1F5F9',
                    dark:    '#0B0F19',
                    card:    '#111827',
                    elevated:'#1E293B',
                    border:  '#334155',
                },
                ui: {
                    blanco: '#FFFFFF',
                    gris:   '#F8FAFC',
                    dark:   '#0B0F19',
                },
            },
            boxShadow: {
                'glow-sm': '0 0 15px -3px rgba(16, 185, 129, 0.2)',
                'glow':    '0 0 25px -5px rgba(16, 185, 129, 0.3)',
                'glow-lg': '0 0 35px -5px rgba(16, 185, 129, 0.4)',
                'glow-ai': '0 0 25px -5px rgba(99, 102, 241, 0.35)',
                'glow-amber': '0 0 25px -5px rgba(245, 158, 11, 0.35)',
                'card-light': '0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.02)',
                'card-hover': '0 12px 30px -4px rgba(15, 23, 42, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.03)',
                'card-dark': '0 8px 30px rgba(0, 0, 0, 0.35)',
            },
            backgroundImage: {
                'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                'gradient-conic':  'conic-gradient(from 180deg at 50% 50%, var(--tw-gradient-stops))',
                'hero-glow':       'radial-gradient(circle at 50% 0%, rgba(16, 185, 129, 0.15) 0%, rgba(99, 102, 241, 0.08) 50%, transparent 80%)',
            },
            animation: {
                'float':        'float 4s ease-in-out infinite',
                'float-slow':   'float 6s ease-in-out infinite',
                'pulse-soft':   'pulse-soft 2.5s ease-in-out infinite',
                'pulse-glow':   'pulse-glow 2s ease-in-out infinite',
                'shimmer':      'shimmer 2.5s linear infinite',
                'fade-up':      'fade-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                'slide-up':     'slide-up 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                'scale-in':     'scale-in 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                'sparkle':      'sparkle 2s ease-in-out infinite',
            },
            keyframes: {
                float: {
                    '0%, 100%': { transform: 'translateY(0px)' },
                    '50%':      { transform: 'translateY(-6px)' },
                },
                'pulse-soft': {
                    '0%, 100%': { opacity: '1' },
                    '50%':      { opacity: '0.75' },
                },
                'pulse-glow': {
                    '0%, 100%': { filter: 'drop-shadow(0 0 8px rgba(16, 185, 129, 0.4))' },
                    '50%':      { filter: 'drop-shadow(0 0 18px rgba(16, 185, 129, 0.7))' },
                },
                shimmer: {
                    '0%':    { backgroundPosition: '-200% 0' },
                    '100%':  { backgroundPosition: '200% 0' },
                },
                'fade-up': {
                    '0%':   { opacity: '0', transform: 'translateY(16px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'slide-up': {
                    '0%':   { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'scale-in': {
                    '0%':   { transform: 'scale(0.95)', opacity: '0' },
                    '100%': { transform: 'scale(1)', opacity: '1' },
                },
                sparkle: {
                    '0%, 100%': { opacity: '1', transform: 'scale(1)' },
                    '50%':      { opacity: '0.4', transform: 'scale(0.85)' },
                },
            },
        },
    },

    plugins: [forms],
};
