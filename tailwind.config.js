import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans:    ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    verde:      '#2D6A4F',
                    aprendizaje:'#40916C',
                    amarillo:   '#FFD166',
                    naranja:    '#FF8C6B',
                    purple:     '#7C6FE8',
                },
                ui: {
                    blanco: '#FFFFFF',
                    gris:   '#FAFAF8',
                    dark:   '#1A1D23',
                },
            },
            animation: {
                'float':       'float 3s ease-in-out infinite',
                'float-slow':  'float 5s ease-in-out infinite',
                'pulse-soft':  'pulse-soft 2s ease-in-out infinite',
                'shimmer':     'shimmer 2s linear infinite',
                'xp-float':    'xp-float 1s ease-out forwards',
                'bounce-in':   'bounce-in 0.5s ease-out',
                'fade-up':     'fade-up 0.6s ease-out',
                'slide-up':    'slide-up 0.4s ease-out',
                'glow':        'glow 2s ease-in-out infinite alternate',
                'confetti':    'confetti 1s ease-out forwards',
                'scale-in':    'scale-in 0.3s ease-out',
            },
            keyframes: {
                float: {
                    '0%, 100%': { transform: 'translateY(0px)' },
                    '50%':      { transform: 'translateY(-8px)' },
                },
                'pulse-soft': {
                    '0%, 100%': { opacity: '1' },
                    '50%':      { opacity: '0.7' },
                },
                shimmer: {
                    '0%':    { backgroundPosition: '-200% 0' },
                    '100%':  { backgroundPosition: '200% 0' },
                },
                'xp-float': {
                    '0%':   { opacity: '1', transform: 'translateY(0) scale(1)' },
                    '100%': { opacity: '0', transform: 'translateY(-60px) scale(1.3)' },
                },
                'bounce-in': {
                    '0%':   { transform: 'scale(0)', opacity: '0' },
                    '60%':  { transform: 'scale(1.1)' },
                    '100%': { transform: 'scale(1)', opacity: '1' },
                },
                'fade-up': {
                    '0%':   { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'slide-up': {
                    '0%':   { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                glow: {
                    '0%':   { boxShadow: '0 0 20px rgba(45, 106, 79, 0.2)' },
                    '100%': { boxShadow: '0 0 40px rgba(45, 106, 79, 0.4)' },
                },
                confetti: {
                    '0%':   { transform: 'translateY(0) rotate(0deg)', opacity: '1' },
                    '100%': { transform: 'translateY(-100px) rotate(720deg)', opacity: '0' },
                },
                'scale-in': {
                    '0%':   { transform: 'scale(0.9)', opacity: '0' },
                    '100%': { transform: 'scale(1)', opacity: '1' },
                },
            },
        },
    },

    plugins: [forms],
};
