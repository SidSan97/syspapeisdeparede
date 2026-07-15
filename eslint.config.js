import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';
import skipFormatting from '@vue/eslint-config-prettier/skip-formatting';
import importPlugin from 'eslint-plugin-import';

export default [
  {
    ignores: ['public/build/**', 'node_modules/**', 'vendor/**'],
  },

  js.configs.recommended,

  ...pluginVue.configs['flat/strongly-recommended'],

  {
    files: ['**/*.{vue,js,mjs,jsx}'],

    plugins: {
      import: importPlugin,
    },

    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',

      globals: {
        ...globals.browser,
        ...globals.node,
      },
    },

    rules: {
      'no-unused-vars': [
        'warn',
        {
          argsIgnorePattern: '^_',
          varsIgnorePattern: '^_',
        },
      ],

      'import/no-unused-modules': 'error',

      'import/order': [
        'error',
        {
          groups: ['builtin', 'external', 'internal', 'parent', 'sibling', 'index'],
          // alphabetize: { order: 'asc', caseInsensitive: true },
        },
      ],
    },
  },

  skipFormatting,
];
