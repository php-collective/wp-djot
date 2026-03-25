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
            // Also match common container classes rendered by djot-php
            { tag: 'div.note' },
            { tag: 'div.tip' },
            { tag: 'div.warning' },
            { tag: 'div.danger' },
            { tag: 'div.info' },
            // Match any div with a single class (likely a ::: container)
            {
                tag: 'div[class]',
                getAttrs: element => {
                    // Only match divs with a simple class (not complex component divs)
                    const className = element.className;
                    // Skip if it looks like a WordPress/editor component
                    if (className.includes('wp-') || className.includes('block-') ||
                        className.includes('editor-') || className.includes('is-')) {
                        return false;
                    }
                    // Skip Torchlight code block line divs
                    if (className === 'line' || className.includes('line-')) {
                        return false;
                    }
                    // Skip if inside a pre or code element (Torchlight highlighting)
                    if (element.closest('pre') || element.closest('code')) {
                        return false;
                    }
                    // Accept single-word classes or djot-div
                    if (/^[a-z-]+$/i.test(className) || className.includes('djot-div')) {
                        return {};
                    }
                    return false;
                },
            },
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
