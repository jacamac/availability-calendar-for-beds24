import js from '@eslint/js';
import globals from 'globals';

export default [
    js.configs.recommended,
    // Global tweak: allow _ as intentionally-unused catch binding in all files
    {
        rules: {
            'no-unused-vars': ['error', { caughtErrorsIgnorePattern: '^_' }],
        },
    },
    {
        files: ['assets/calendar.js'],
        languageOptions: {
            ecmaVersion: 2020,
            sourceType: 'script',
            globals: globals.browser,
        },
        rules: {
            // Both empty catches are intentional: sessionStorage quota and Intl fallback
            'no-empty': ['error', { allowEmptyCatch: true }],
        },
    },
    {
        files: ['tests/**/*.js'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: globals.node,
        },
    },
    {
        files: ['assets/elementor-preview.js'],
        languageOptions: {
            ecmaVersion: 5,
            sourceType: 'script',
            globals: {
                ...globals.browser,
                jQuery: 'readonly',
                elementorModules: 'readonly',
                elementorFrontend: 'readonly',
                AvailCalendar: 'readonly',
            },
        },
    },
];
