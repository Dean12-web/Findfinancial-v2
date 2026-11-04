import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                primary: {
                    DEFAULT: '#ff5500',
                    50: '#fff5f0',
                    100: '#ffe8dc',
                    200: '#ffd2be',
                    300: '#ffb292',
                    400: '#ff8654',
                    500: '#ff5500',
                    600: '#ea4e00',
                    700: '#c23e00',
                    800: '#9b3300',
                    900: '#7c2c04',
                },
                secondary: {
                    DEFAULT: '#00e599',
                    50: '#e6fcf5',
                    100: '#ccf9eb',
                    200: '#99f3d7',
                    300: '#66edc3',
                    400: '#33e7af',
                    500: '#00e599',
                    600: '#00cc88',
                    700: '#00a36d',
                    800: '#007a52',
                    900: '#005237',
                },
                tertiary: {
                    DEFAULT: '#ffb800',
                    50: '#fffbf0',
                    100: '#fff6db',
                    200: '#ffecb8',
                    300: '#ffe294',
                    400: '#ffd24d',
                    500: '#ffb800',
                    600: '#e6a600',
                    700: '#b88500',
                    800: '#8f6700',
                    900: '#705100',
                },
                neutral: {
                    DEFAULT: '#0f1115',
                    50: '#f8f9fa',
                    100: '#f1f3f5',
                    200: '#e9ecef',
                    300: '#dee2e6',
                    400: '#ced4da',
                    500: '#8c939d',
                    600: '#5c636e',
                    700: '#383e48',
                    800: '#232731',
                    850: '#1c2027',
                    900: '#16191f',
                    950: '#0f1115',
                },
            },
        },
    },

    plugins: [forms],
};
