import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        'w-64', 'w-20', 'lg:w-64', 'lg:w-20',
        'pl-64', 'pl-20', 'lg:pl-64', 'lg:pl-20',
        'lg:left-64', 'lg:left-20',
        'lg:sticky', 'lg:self-start', 'lg:shrink-0', 'shrink-0', 'sticky'
    ],

    theme: {
        extend: {
            spacing: {
                '4.5': '1.125rem',
                '5.5': '1.375rem',
            },
            boxShadow: {
                '2xs': '0 1px 1px 0 rgb(0 0 0 / 0.04)',
                'xs': '0 1px 2px 0 rgb(0 0 0 / 0.05)',
            },
            backdropBlur: {
                'xs': '2px',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                '2xs': ['0.75rem', { lineHeight: '1.125rem' }],
                'xs': ['0.8125rem', { lineHeight: '1.25rem' }],
                'sm': ['0.875rem', { lineHeight: '1.375rem' }],
                'base': ['0.9375rem', { lineHeight: '1.5rem' }],
                'lg': ['1.0625rem', { lineHeight: '1.5rem' }],
                'xl': ['1.1875rem', { lineHeight: '1.625rem' }],
                '2xl': ['1.25rem', { lineHeight: '1.75rem' }],
                '3xl': ['1.5rem', { lineHeight: '2rem' }],
            },
            letterSpacing: {
                tighter: '-0.02em',
                tight: '-0.01em',
                normal: '0em',
                wide: '0.015em',
                wider: '0.025em',
                widest: '0.04em',
            },
            colors: {
                'brand-blue': {
                    50: '#EFF6FF',
                    100: '#DBEAFE',
                    200: '#BFDBFE',
                    500: '#3B82F6',
                    600: '#2563EB',
                    700: '#1D4ED8',
                    800: '#1E40AF',
                    900: '#1E3A8A',
                    950: '#172554',
                },
                'bg-primary': '#FFFFFF',
                'bg-subtle': '#F8FAFC',
                'surface-card': '#FFFFFF',
                'border-subtle': '#E2E8F0',
                'text-main': '#0F172A',
                'text-muted': '#64748B',
                'accent-primary': '#1E40AF',
                'accent-blue': '#2563EB',
                status: {
                    waiting: { bg: '#F1F5F9', text: '#334155' },
                    revision: { bg: '#FEF3C7', text: '#92400E' },
                    rejected: { bg: '#FEE2E2', text: '#991B1B' },
                    progress: { bg: '#E0F2FE', text: '#0369A1' },
                    completed: { bg: '#DCFCE7', text: '#166534' },
                },
            },
            borderRadius: {
                'card': '12px',
            },
        },
    },

    plugins: [forms],
};
