import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class', '.ocean'],

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Lato', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    500: '#0d6efd',
                    600: '#0b5ed7',
                },
            },
        },
    },

    plugins: [forms],
};
