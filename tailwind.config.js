import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import colors from 'tailwindcss/colors';

export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/**/*.js',
        './app/Livewire/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary:  colors.emerald,
                accent:   colors.cyan,
                success:  colors.green,
                error:    colors.red,
                warning:  colors.amber,
                neutral:  colors.gray,
                surface: {
                    page:   'var(--surface-page)',
                    card:   'var(--surface-card)',
                    raised: 'var(--surface-raised)',
                },
            },
        },
    },
    plugins: [forms],
};