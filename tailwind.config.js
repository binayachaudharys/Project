import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Manrope', ...defaultTheme.fontFamily.sans],
                display: ['Fraunces', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                rose: {
                    50: '#FBEFEE',
                    100: '#F6DBD8',
                    200: '#EDB8B2',
                    300: '#E1928B',
                    400: '#CE6B65',
                    500: '#B24A47',
                    600: '#8F3634',
                    700: '#6E2827',
                    800: '#4B1B1B',
                },
                blush: {
                    50: '#FFF9F5',
                    100: '#FDF0E9',
                    200: '#FBE3D6',
                },
                charcoal: {
                    50: '#EFEDEC',
                    100: '#D9D4D2',
                    300: '#8A7E7A',
                    500: '#4A3F3B',
                    700: '#2C2422',
                    900: '#1B1513',
                },
            },
        },
    },

    plugins: [forms],
};
