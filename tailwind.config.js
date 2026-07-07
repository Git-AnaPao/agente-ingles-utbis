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
                // Style Guide §Tipografía
                sans:    ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Style Guide §Colores de Marca
                brand: {
                    verde:      '#27594B',
                    aprendizaje:'#518C4F',
                    amarillo:   '#F2B950',
                    naranja:    '#F28729',
                },
                // Style Guide §Colores de Interfaz
                ui: {
                    blanco: '#FFFFFF',
                    gris:   '#F2F2F2',
                },
            },
        },
    },

    plugins: [forms],
};
