import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
const coloresDinamicos = ['red', 'green', 'yellow', 'blue', 'indigo', 'purple', 'gray', 'orange'];
const tonos = ['50', '100', '500', '600', '700', '800'];

const safelistDinamico = [];
coloresDinamicos.forEach((color) => {
    tonos.forEach((tono) => {
        safelistDinamico.push(`bg-${color}-${tono}`);
        safelistDinamico.push(`text-${color}-${tono}`);
        safelistDinamico.push(`border-${color}-${tono}`);
    });
});

export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: safelistDinamico,

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
