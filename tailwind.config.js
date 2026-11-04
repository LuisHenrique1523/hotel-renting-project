import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/Enums/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Playfair Display"', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                night: {
                    50: '#F2F5F9', 100: '#E1E7EF', 200: '#C3CFDF', 300: '#93A6C2', 400: '#5E7699',
                    500: '#3D5679', 600: '#2B3F5C', 700: '#1F3049', 800: '#16243A', 900: '#0F1E33', 950: '#091424',
                },
                sand: {
                    50: '#FCFAF6', 100: '#F6F0E6', 200: '#E9DCC9', 300: '#DCC8AC', 400: '#C9AD87',
                },
                gold: {
                    50: '#FBF6EA', 100: '#F4E8C9', 200: '#E9D29A', 300: '#DCBA74', 400: '#D1AB64',
                    500: '#C8A15A', 600: '#A9823F', 700: '#876532', 800: '#664C27', 900: '#46341C',
                },
            },
            boxShadow: {
                soft: '0 10px 40px -12px rgba(15, 30, 51, 0.18)',
                glow: '0 0 0 1px rgba(200,161,90,.35), 0 12px 32px -8px rgba(200,161,90,.45)',
            },
            keyframes: {
                'fade-up': { '0%': { opacity: 0, transform: 'translateY(16px)' }, '100%': { opacity: 1, transform: 'translateY(0)' } },
                'slow-zoom': { '0%': { transform: 'scale(1.08)' }, '100%': { transform: 'scale(1)' } },
            },
            animation: {
                'fade-up': 'fade-up .7s cubic-bezier(.2,.7,.2,1) both',
                'slow-zoom': 'slow-zoom 12s ease-out both',
            },
        },
    },

    plugins: [forms],
};
