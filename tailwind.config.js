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
                    50: '#FFF0F7',
                    100: '#FFE0EF',
                    200: '#FFB8DB',
                    300: '#FF8AC4',
                    400: '#FF5AAE',
                    500: '#E91E8C',
                    600: '#C41876',
                    700: '#9A125C',
                    800: '#6E0D42',
                },
                blush: {
                    50: '#FFF7FB',
                    100: '#FFF0F7',
                    200: '#FFE4F1',
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
