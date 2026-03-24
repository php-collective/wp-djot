import { Node, mergeAttributes } from 'https://esm.sh/@tiptap/core@2';

/**
 * Djot Footnote node extension for Tiptap
 *
 * Renders as [^label] in Djot markup
 *
 * @example
 * ```js
 * import { DjotFootnote } from 'djot-grammars/tiptap'
 *
 * const editor = new Editor({
 *   extensions: [DjotFootnote],
 * })
 *
 * // Insert a footnote reference
 * editor.chain().focus().insertDjotFootnote({ label: 'note1' }).run()
 * ```
 */
export const DjotFootnote = Node.create({
    name: 'djotFootnote',

    group: 'inline',

    inline: true,

    atom: true,

    addAttributes() {
        return {
            label: {
                default: 'note',
                parseHTML: element => element.getAttribute('data-footnote-label') || element.textContent?.replace(/[[\]^]/g, '') || 'note',
                renderHTML: attributes => {
                    return { 'data-footnote-label': attributes.label };
                },
            },
        };
    },

    parseHTML() {
        return [
            { tag: 'sup.djot-footnote' },
            { tag: 'span.djot-footnote-ref' },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        const label = HTMLAttributes['data-footnote-label'] || 'note';
        return ['sup', mergeAttributes(HTMLAttributes, {
            class: 'djot-footnote',
            'data-footnote-label': label,
            contenteditable: 'false',
        }), `[^${label}]`];
    },

    addCommands() {
        return {
            insertDjotFootnote: (attributes) => ({ commands }) => {
                return commands.insertContent({
                    type: this.name,
                    attrs: attributes,
                });
            },
        };
    },
});

export default DjotFootnote;
