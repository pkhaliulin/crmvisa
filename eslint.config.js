import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';

export default [
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    {
        files: ['resources/js/**/*.{js,vue}'],
        rules: {
            'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
            'no-console': 'warn',
            'vue/multi-word-component-names': 'off',
            'vue/no-v-html': 'off',
            'vue/require-default-prop': 'off',
            'vue/max-attributes-per-line': 'off',
            'vue/singleline-html-element-content-newline': 'off',
            'vue/html-self-closing': 'off',
            'vue/html-indent': 'off',
        },
    },
    {
        ignores: ['public/', 'vendor/', 'node_modules/', 'bootstrap/'],
    },
];
