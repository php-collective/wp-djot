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
                parseHTML: element => {
                    // First check data-djot-class, then fall back to class attribute
                    const djotClass = element.getAttribute('data-djot-class');
                    if (djotClass) return djotClass;
                    // Extract class from className, filtering out djot-span
                    const className = element.className || '';
                    return className.replace('djot-span', '').trim() || 'custom';
                },
                renderHTML: attributes => {
                    return { 'data-djot-class': attributes.class };
                },
            },
        };
    },

    parseHTML() {
        return [
            { tag: 'span[data-djot-class]' },
            // Also match spans with class attributes from PHP renderer
            {
                tag: 'span[class]',
                getAttrs: element => {
                    // Skip spans that are part of code highlighting or other editor elements
                    const className = element.className || '';
                    // Skip token spans (Phiki/Torchlight syntax highlighting)
                    if (className.includes('token') || className.includes('phiki') ||
                        className.includes('torchlight') || className.includes('ProseMirror')) {
                        return false;
                    }
                    // Skip if inside a pre or code element
                    if (element.closest('pre') || element.closest('code')) {
                        return false;
                    }
                    // Match spans with simple classes (likely from Djot [text]{.class})
                    if (/^[a-zA-Z][a-zA-Z0-9_-]*$/.test(className)) {
                        return {};
                    }
                    return false;
                },
            },
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
