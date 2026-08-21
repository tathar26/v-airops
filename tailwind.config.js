import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                vops: {
                    navy: '#0A1835',
                    'navy-dark': '#060E22',
                    'navy-light': '#0F224A',
                    dark: '#0A1835', // Deep Navy/Dark Blue background
                    card: '#0F224A', // Slightly lighter for cards
                    teal: '#21A19D', // Vibrant Teal / Cyan
                    accent: '#21A19D', // Primary button / CTA accent
                    primary: '#21A19D', // Vibrant Teal primary
                    secondary: '#6F3B84', // Purple secondary / subtle accent
                    purple: '#6F3B84', // Purple
                    success: '#10B981', // Emerald
                }
            },
            animation: {
                'gradient-x': 'gradient-x 15s ease infinite',
                'float': 'float 6s ease-in-out infinite',
                'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },
            keyframes: {
                'gradient-x': {
                    '0%, 100%': {
                        'background-size': '200% 200%',
                        'background-position': 'left center'
                    },
                    '50%': {
                        'background-size': '200% 200%',
                        'background-position': 'right center'
                    },
                },
                'float': {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-10px)' },
                }
            }
        },
    },

    plugins: [forms, typography],
};
