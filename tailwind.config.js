/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
                mono: ['JetBrains Mono', 'Cascadia Code', 'monospace'],
            },
            colors: {
                parchment: {
                    DEFAULT: '#F5F0E8',
                    dark: '#F0EAE0',
                    alt: '#FAF7F2',
                },
                violet: { DEFAULT: '#7C5CFC', light: '#A78BFA', dark: '#5B3FD4' },
                mint: { DEFAULT: '#0ECFA4', light: '#0ECFA4', dark: '#0A8A6C' },
                amber: { DEFAULT: '#F5A623', light: '#F5A623', dark: '#92400E' },
                rose: { DEFAULT: '#F06292', light: '#F06292', dark: '#BE185D' },
                sky: { DEFAULT: '#38BDF8', light: '#38BDF8', dark: '#0369A1' },
            },
            borderRadius: {
                card: '11px',
                chip: '20px',
                button: '9px',
            },
        },
    },
    plugins: [],
};
