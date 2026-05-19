import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

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
                sans: ['"Open Sans"', ...defaultTheme.fontFamily.sans],
                heading: ['"Exo 2"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', 'Consolas', ...defaultTheme.fontFamily.mono],
            },
        },
    },

    plugins: [forms],
};
