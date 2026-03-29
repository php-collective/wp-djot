import { Mark, mergeAttributes } from 'https://esm.sh/@tiptap/core@2';

/**
 * Djot Definition mark extension for Tiptap
 *
 * Renders as [text]{dfn} or [text]{dfn="title"} in Djot markup
 * Parses <dfn> HTML elements from PHP renderer
 */
export const DjotDefinition = Mark.create({
    name: 'djotDefinition',

    addAttributes() {
        return {
            title: {
                default: '',
                parseHTML: element => element.getAttribute('title') || '',
                renderHTML: attributes => {
                    if (!attributes.title) return {};
                    return { title: attributes.title };
                },
            },
        };
    },

    parseHTML() {
        return [
            { tag: 'dfn' },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return ['dfn', mergeAttributes(HTMLAttributes), 0];
    },

    addCommands() {
        return {
            setDjotDefinition: (attributes) => ({ commands }) => {
                return commands.setMark(this.name, attributes);
            },
            toggleDjotDefinition: (attributes) => ({ commands }) => {
                return commands.toggleMark(this.name, attributes);
            },
            unsetDjotDefinition: () => ({ commands }) => {
                return commands.unsetMark(this.name);
            },
        };
    },
});

export default DjotDefinition;
