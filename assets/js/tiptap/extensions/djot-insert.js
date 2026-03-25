import { Mark, mergeAttributes } from 'https://esm.sh/@tiptap/core@2';

/**
 * Djot Insert mark extension for Tiptap
 *
 * Renders as {+text+} in Djot markup
 *
 * @example
 * ```js
 * import { DjotInsert } from 'djot-grammars/tiptap'
 *
 * const editor = new Editor({
 *   extensions: [DjotInsert],
 * })
 *
 * // Toggle insert mark
 * editor.chain().focus().toggleDjotInsert().run()
 * ```
 */
export const DjotInsert = Mark.create({
    name: 'djotInsert',

    parseHTML() {
        return [
            { tag: 'ins' },
            { tag: 'span.djot-insert' },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return ['span', mergeAttributes(HTMLAttributes, { class: 'djot-insert' }), 0];
    },

    addCommands() {
        return {
            toggleDjotInsert: () => ({ commands }) => commands.toggleMark(this.name),
            setDjotInsert: () => ({ commands }) => commands.setMark(this.name),
            unsetDjotInsert: () => ({ commands }) => commands.unsetMark(this.name),
        };
    },

    addKeyboardShortcuts() {
        return {
            'Mod-Shift-i': () => this.editor.commands.toggleDjotInsert(),
        };
    },
});

export default DjotInsert;
