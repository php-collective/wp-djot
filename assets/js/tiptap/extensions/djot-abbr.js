import { Mark, mergeAttributes } from 'https://esm.sh/@tiptap/core@2';

/**
 * Djot Abbreviation mark extension for Tiptap
 *
 * Renders as [text]{abbr="title"} in Djot markup
 * Parses <abbr title="..."> HTML elements from PHP renderer
 */
export const DjotAbbreviation = Mark.create({
    name: 'djotAbbreviation',

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
            { tag: 'abbr' },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return ['abbr', mergeAttributes(HTMLAttributes), 0];
    },

    addCommands() {
        return {
            setDjotAbbreviation: (attributes) => ({ commands }) => {
                return commands.setMark(this.name, attributes);
            },
            toggleDjotAbbreviation: (attributes) => ({ commands }) => {
                return commands.toggleMark(this.name, attributes);
            },
            unsetDjotAbbreviation: () => ({ commands }) => {
                return commands.unsetMark(this.name);
            },
        };
    },
});

export default DjotAbbreviation;
