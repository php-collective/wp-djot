import { Node, mergeAttributes } from 'https://esm.sh/@tiptap/core@2';

/**
 * Djot Div container node extension for Tiptap
 *
 * Renders as ::: class in Djot markup
 *
 * @example
 * ```js
 * import { DjotDiv } from 'djot-grammars/tiptap'
 *
 * const editor = new Editor({
 *   extensions: [DjotDiv],
 * })
 *
 * // Wrap selection in a div container
 * editor.chain().focus().setDjotDiv({ class: 'warning' }).run()
 * ```
 */
export const DjotDiv = Node.create({
    name: 'djotDiv',

    group: 'block',

    content: 'block+',

    defining: true,

    addAttributes() {
        return {
            class: {
                default: null,
                parseHTML: element => element.getAttribute('data-djot-class') || element.className.replace('djot-div', '').trim() || null,
                renderHTML: attributes => {
                    if (!attributes.class) return {};
                    return { 'data-djot-class': attributes.class };
                },
            },
        };
    },

    parseHTML() {
        return [
            { tag: 'div.djot-div' },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        const classes = ['djot-div'];
        if (HTMLAttributes['data-djot-class']) {
            classes.push(HTMLAttributes['data-djot-class']);
        }
        return ['div', mergeAttributes(HTMLAttributes, { class: classes.join(' ') }), 0];
    },

    addCommands() {
        return {
            setDjotDiv: (attributes) => ({ commands }) => {
                return commands.wrapIn(this.name, attributes);
            },
            toggleDjotDiv: (attributes) => ({ commands }) => {
                return commands.toggleWrap(this.name, attributes);
            },
            unsetDjotDiv: () => ({ commands }) => {
                return commands.lift(this.name);
            },
        };
    },
});

export default DjotDiv;
