import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';
import skipFormatting from '@vue/eslint-config-prettier/skip-formatting'

export default [
  {
    ignores: ['public/build/**', 'node_modules/**', 'vendor/**'],
  },

  js.configs.recommended,

  ...pluginVue.configs['flat/strongly-recommended'],

  {
    files: ['**/*.{vue,js,mjs,jsx}'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...globals.browser,
        ...globals.node,
      },
    },
    rules: {
      'vue/no-unused-vars': 'error',
      'no-unused-vars': 'warn',
    },
  },

  skipFormatting,
];
