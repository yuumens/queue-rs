/** @type {import('tailwindcss').Config} */
// NOTE: This project uses Tailwind CSS v4 which uses CSS-first configuration
// (see resources/css/app.css @theme block). This file is kept for editor
// tooling / IntelliSense and documents the brand color override.
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                // Primary brand color — light blue (blue-500)
                brand: '#3b82f6',
            },
        },
    },
    plugins: [],
};
