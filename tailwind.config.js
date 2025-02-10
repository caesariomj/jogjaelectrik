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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    DEFAULT: '#fd5722',
                    50: '#fff4ed',
                    100: '#ffe6d4',
                    200: '#ffc9a9',
                    300: '#ffa272',
                    400: '#fe7139',
                    500: '#fd5722',
                    600: '#ee3008',
                    700: '#c52009',
                    800: '#9c1c10',
                    900: '#7e1a10',
                    950: '#440906',
                },
                black: {
                    DEFAULT: '#0C0C0C',
                },
            },
        },
    },
    plugins: [forms],
};
