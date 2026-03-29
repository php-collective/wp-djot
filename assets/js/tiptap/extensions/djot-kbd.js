import { Mark, mergeAttributes } from 'https://esm.sh/@tiptap/core@2';

/**
 * Djot Keyboard mark extension for Tiptap
 *
 * Renders as [text]{kbd} in Djot markup
 * Parses <kbd> HTML elements from PHP renderer
 */
export const DjotKbd = Mark.create({
    name: 'djotKbd',

    parseHTML() {
        return [
            { tag: 'kbd' },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return ['kbd', mergeAttributes(HTMLAttributes), 0];
    },

    addCommands() {
        return {
            setDjotKbd: () => ({ commands }) => {
                return commands.setMark(this.name);
            },
            toggleDjotKbd: () => ({ commands }) => {
                return commands.toggleMark(this.name);
            },
            unsetDjotKbd: () => ({ commands }) => {
                return commands.unsetMark(this.name);
            },
        };
    },
});

export default DjotKbd;
