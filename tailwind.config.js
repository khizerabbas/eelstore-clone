import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Theme colours set in the admin panel (Website → Branding & theme), see App\Support\Theme
            colors: {
                brand: 'rgb(var(--color-brand) / <alpha-value>)',
                accent: 'rgb(var(--color-accent) / <alpha-value>)',
                'on-accent': 'rgb(var(--color-on-accent) / <alpha-value>)',
                'accent-ink': 'rgb(var(--color-accent-ink) / <alpha-value>)',
                page: 'rgb(var(--color-page) / <alpha-value>)',
            },
        },
    },

    plugins: [forms, typography],
};
