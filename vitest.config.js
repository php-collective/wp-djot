import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    environment: 'jsdom',
    globals: true,
    include: ['tests/js/**/*.test.js'],
    alias: {
      // Map CDN imports to npm packages for testing
      'https://esm.sh/@tiptap/core@2': '@tiptap/core',
      'https://esm.sh/@tiptap/starter-kit@2': '@tiptap/starter-kit',
      'https://esm.sh/@tiptap/extension-link@2': '@tiptap/extension-link',
      'https://esm.sh/@tiptap/extension-image@2': '@tiptap/extension-image',
      'https://esm.sh/@tiptap/extension-table@2': '@tiptap/extension-table',
      'https://esm.sh/@tiptap/extension-table-row@2': '@tiptap/extension-table-row',
      'https://esm.sh/@tiptap/extension-table-cell@2': '@tiptap/extension-table-cell',
      'https://esm.sh/@tiptap/extension-table-header@2': '@tiptap/extension-table-header',
      'https://esm.sh/@tiptap/extension-task-list@2': '@tiptap/extension-task-list',
      'https://esm.sh/@tiptap/extension-task-item@2': '@tiptap/extension-task-item',
      'https://esm.sh/@tiptap/extension-highlight@2': '@tiptap/extension-highlight',
      'https://esm.sh/@tiptap/extension-subscript@2': '@tiptap/extension-subscript',
      'https://esm.sh/@tiptap/extension-superscript@2': '@tiptap/extension-superscript',
      'https://esm.sh/@tiptap/extension-code-block@2': '@tiptap/extension-code-block',
      'https://esm.sh/@tiptap/extension-bullet-list@2': '@tiptap/extension-bullet-list',
      'https://esm.sh/@tiptap/extension-ordered-list@2': '@tiptap/extension-ordered-list',
      'https://esm.sh/@tiptap/extension-list-item@2': '@tiptap/extension-list-item',
    },
  },
});
