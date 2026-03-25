import { Mark, mergeAttributes } from 'https://esm.sh/@tiptap/core@2';

/**
 * Djot Delete mark extension for Tiptap
 *
 * Renders as {-text-} in Djot markup
 *
 * @example
 * ```js
 * import { DjotDelete } from 'djot-grammars/tiptap'
 *
 * const editor = new Editor({
 *   extensions: [DjotDelete],
 * })
 *
 * // Toggle delete mark
 * editor.chain().focus().toggleDjotDelete().run()
 * ```
 */
export const DjotDelete = Mark.create({
    name: 'djotDelete',

    parseHTML() {
        return [
            { tag: 'del' },
            { tag: 'span.djot-delete' },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return ['span', mergeAttributes(HTMLAttributes, { class: 'djot-delete' }), 0];
    },

    addCommands() {
        return {
            toggleDjotDelete: () => ({ commands }) => commands.toggleMark(this.name),
            setDjotDelete: () => ({ commands }) => commands.setMark(this.name),
            unsetDjotDelete: () => ({ commands }) => commands.unsetMark(this.name),
        };
    },

    addKeyboardShortcuts() {
        return {
            'Mod-Shift-d': () => this.editor.commands.toggleDjotDelete(),
        };
    },
});

export default DjotDelete;
