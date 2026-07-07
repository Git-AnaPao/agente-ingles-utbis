import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.data('theme', () => ({
        theme: localStorage.getItem('theme') || 'light',
        grayscale: localStorage.getItem('grayscale') === 'true',
        init() {
            this.$watch('theme', val => {
                localStorage.setItem('theme', val);
                document.documentElement.setAttribute('data-theme', val);
            });
            this.$watch('grayscale', val => {
                localStorage.setItem('grayscale', val);
                document.documentElement.setAttribute('data-grayscale', val);
            });
        },
        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
        },
        toggleGrayscale() {
            this.grayscale = !this.grayscale;
        },
    }));
});

Alpine.start();
