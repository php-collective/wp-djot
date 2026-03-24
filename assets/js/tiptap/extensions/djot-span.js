import { Mark, mergeAttributes } from 'https://esm.sh/@tiptap/core@2';

/**
 * Djot Span mark extension for Tiptap
 *
 * Renders as [text]{.class} in Djot markup
 *
 * @example
 * ```js
 * import { DjotSpan } from 'djot-grammars/tiptap'
 *
 * const editor = new Editor({
 *   extensions: [DjotSpan],
 * })
 *
 * // Apply span with class
 * editor.chain().focus().setDjotSpan({ class: 'highlight' }).run()
 * ```
 */
export const DjotSpan = Mark.create({
    name: 'djotSpan',

    addAttributes() {
        return {
            class: {
                default: 'custom',
                parseHTML: element => element.getAttribute('data-djot-class') || 'custom',
                renderHTML: attributes => {
                    return { 'data-djot-class': attributes.class };
                },
            },
        };
    },

    parseHTML() {
        return [
            { tag: 'span[data-djot-class]' },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        const className = HTMLAttributes['data-djot-class'] || 'custom';
        return ['span', mergeAttributes(HTMLAttributes, {
            class: `djot-span ${className}`,
            'data-djot-class': className,
        }), 0];
    },

    addCommands() {
        return {
            setDjotSpan: (attributes) => ({ commands }) => {
                return commands.setMark(this.name, attributes);
            },
            toggleDjotSpan: (attributes) => ({ commands }) => {
                return commands.toggleMark(this.name, attributes);
            },
            unsetDjotSpan: () => ({ commands }) => {
                return commands.unsetMark(this.name);
            },
        };
    },
});

export default DjotSpan;
